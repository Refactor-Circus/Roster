<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Roster\Domains\Scim\Services\ScimContext;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Events\UserCreatedEvent;
use RefactorCircus\Roster\Domains\User\Models\UserModel;
use RefactorCircus\Roster\Tests\Fixtures\Billing\InvoicePaidActionEvent;
use RefactorCircus\Roster\Tests\Fixtures\Billing\InvoicePayingActionEvent;

require_once __DIR__.'/helpers.php';

/**
 * Keen installed, as `KeenTestCase` boots it. `tests/Pest.php` already gives
 * every file under `tests/Feature` the base `TestCase`, and Pest refuses a
 * second test case, so this file adds Keen's provider with a trait instead.
 */
trait InstallsKeenForScimAudit
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            KeenServiceProvider::class,
        ];
    }
}

uses(InstallsKeenForScimAudit::class);

/**
 * The SCIM domain adds its token to Roster's audit entries itself, through
 * Keystone's AuditHooks; Roster's own audit hooks know nothing of SCIM.
 */
function scimAuditEntry(string $action): AuditEntryModel
{
    return AuditEntryModel::query()->where('action', $action)->latest('id')->firstOrFail();
}

function oktaToken(): ScimTokenModel
{
    scimOrg();

    return ScimTokenModel::query()->where('name', 'Okta')->sole();
}

it('records the SCIM token a change came through, with the same shape', function (): void {
    [, $this->scimToken] = scimOrg();
    $token = ScimTokenModel::query()->where('name', 'Okta')->sole();

    scim('POST', '/Users', oktaUser())->assertCreated();

    $entry = scimAuditEntry('user.created');

    expect($entry->surface)->toBe('scim')
        ->and($entry->context['scim_token']['name'])->toBe('Okta')
        ->and($entry->context['scim_token'])->toBe(['id' => $token->id, 'name' => 'Okta']);
});

it('adds the SCIM token to Roster entries only, not to other packages\' events', function (): void {
    app(PackageRegistry::class)->register(Package::make('billing', 'RefactorCircus\Roster\Tests\Fixtures\Billing'));
    $token = oktaToken();
    $ada = user(['name' => 'Ada']);
    organization($ada);

    app(ScimContext::class)->token = $token;

    event(new InvoicePayingActionEvent($ada));
    event(new InvoicePaidActionEvent($ada));
    app(SuspendUserAction::class)->execute($ada);

    $invoice = scimAuditEntry('invoice.paid');

    expect($invoice->source)->toBe('billing')
        ->and($invoice->context ?? [])->not->toHaveKey('scim_token')
        ->and(scimAuditEntry('user.suspended')->context['scim_token'])->toBe(['id' => $token->id, 'name' => 'Okta']);
});

it('writes the entry without the SCIM token, and reports why, when the SCIM context throws', function (): void {
    Exceptions::fake();
    $grace = user(['name' => 'Grace']);

    app()->bind(ScimContext::class, fn (): never => throw new RuntimeException('SCIM context unavailable'));

    app(SuspendUserAction::class)->execute($grace);

    $entry = scimAuditEntry('user.suspended');

    expect($entry->subject_id)->toBe((string) $grace->getKey())
        ->and($entry->context ?? [])->not->toHaveKey('scim_token');

    Exceptions::assertReported(fn (RuntimeException $e): bool => $e->getMessage() === 'SCIM context unavailable');
});

it('registers the SCIM audit context only while Roster audits', function (bool $enabled, array $expected): void {
    $token = oktaToken();
    config()->set('roster.audit.enabled', $enabled);

    // Boot the SCIM domain again, on fresh hooks and registries: booted
    // callbacks run at once on a booted application.
    app()->instance(AuditHooks::class, $hooks = new AuditHooks);

    foreach ([Resources::class, PermissionCatalogue::class, MembershipSources::class] as $registry) {
        app()->forgetInstance($registry);
    }

    (new ScimServiceProvider(app()))->boot();

    app(ScimContext::class)->token = $token;

    $context = $hooks->contextFor(new UserCreatedEvent(new UserModel), null);

    expect($context)->toBe(array_map(fn (string $name): array => ['id' => $token->id, 'name' => $name], $expected));
})->with([
    'auditing' => [true, ['scim_token' => 'Okta']],
    'roster.audit.enabled off' => [false, []],
]);
