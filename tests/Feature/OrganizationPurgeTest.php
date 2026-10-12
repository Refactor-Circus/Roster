<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationPurgedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Jobs\PurgeOrganizationJob;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\PurgeOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

beforeEach(function (): void {
    $this->globex = organization(attributes: ['name' => 'Globex']);
    app(DeleteOrganizationAction::class)->execute($this->globex);
});

it('queues the purge and answers 202 over HTTP', function (): void {
    Queue::fake();

    $this->deleteJson(route('roster.organizations.purge', 'globex'))
        ->assertAccepted()
        ->assertJsonPath('message', __('roster::roster.organization_purge_queued'));

    Queue::assertPushed(PurgeOrganizationJob::class, fn (PurgeOrganizationJob $job): bool => $job->organization->is($this->globex));

    expect(OrganizationModel::onlyTrashed()->where('slug', 'globex')->exists())->toBeTrue();
});

it('queues the purge over MCP', function (): void {
    Queue::fake();

    mcpTool(PurgeOrganizationTool::class, ['organization' => 'globex'])->assertOk()->assertSee(__('roster::roster.organization_purge_queued'));

    Queue::assertPushed(PurgeOrganizationJob::class);
});

it('refuses at once what it would refuse to purge, queueing nothing', function (): void {
    Queue::fake();
    organization(attributes: ['name' => 'Initech']);

    $this->deleteJson(route('roster.organizations.purge', 'initech'))->assertUnprocessable()->assertJsonValidationErrors('organization');

    Queue::assertNothingPushed();
});

it('purges for good when the job runs, firing the purge events', function (): void {
    Event::fake([OrganizationPurgedActionEvent::class]);

    app(PurgeOrganizationAction::class)->queue($this->globex);

    expect(OrganizationModel::withTrashed()->where('slug', 'globex')->exists())->toBeFalse();
    Event::assertDispatched(OrganizationPurgedActionEvent::class);
});

it('leaves an organization restored before the job runs, and skips one already gone', function (): void {
    $job = new PurgeOrganizationJob($this->globex);
    $this->globex->restore();

    $job->handle(app(PurgeOrganizationAction::class));

    expect(OrganizationModel::query()->where('slug', 'globex')->exists())->toBeTrue()
        ->and($job->deleteWhenMissingModels)->toBeTrue()
        ->and($job->uniqueId())->toBe((string) $this->globex->id);
});

it('queues each expired organization from roster:purge-deleted', function (): void {
    Queue::fake();
    $this->travel(40)->days();

    $this->artisan('roster:purge-deleted', ['--days' => 30])
        ->expectsOutputToContain('queued 1 organizations')
        ->assertSuccessful();

    Queue::assertPushed(PurgeOrganizationJob::class, 1);
});
