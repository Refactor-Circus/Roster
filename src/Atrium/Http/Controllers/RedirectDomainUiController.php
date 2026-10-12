<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Atrium\RedirectDomains;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

/**
 * An organization's and a user's MCP redirect domains, kept by Cortex. Each
 * screen validates with Cortex's Action rules and calls that Action; the
 * domains are Roster's organization and user settings, so Roster's own
 * permissions guard them. Answers 404 while Cortex is not installed.
 */
final class RedirectDomainUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function storeForOrganization(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        $this->store($request, $model);

        return $this->backToOrganization($model, 'roster::roster.redirect_domain_added');
    }

    public function destroyForOrganization(string $organization, string $domain): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        $this->destroy($model, $domain);

        return $this->backToOrganization($model, 'roster::roster.redirect_domain_removed');
    }

    public function storeForUser(Request $request, string $user): RedirectResponse
    {
        $model = $this->user($user);
        $this->authorizeScreen('roster.users.update');

        $this->store($request, $model);

        return $this->backToUser($model, 'roster::roster.redirect_domain_added');
    }

    public function destroyForUser(string $user, string $domain): RedirectResponse
    {
        $model = $this->user($user);
        $this->authorizeScreen('roster.users.update');

        $this->destroy($model, $domain);

        return $this->backToUser($model, 'roster::roster.redirect_domain_removed');
    }

    private function store(Request $request, Model $owner): void
    {
        app(CreateRedirectDomainAction::class)->execute(CreateRedirectDomainData::from($request->all()), $owner);
    }

    /**
     * Only a domain the owner has: another owner's id is not found here.
     */
    private function destroy(Model $owner, string $domain): void
    {
        $model = RedirectDomainModel::query()->ownedBy($owner)->whereKey($domain)->firstOrFail();

        app(DeleteRedirectDomainAction::class)->execute($model);
    }

    private function organization(string $slug): OrganizationModel
    {
        abort_unless(RedirectDomains::available(), 404);

        return OrganizationModel::query()->where('slug', $slug)->firstOrFail();
    }

    private function user(string $key): Model
    {
        abort_unless(RedirectDomains::available(), 404);

        return $this->users->findOrFail($key);
    }

    private function backToOrganization(OrganizationModel $organization, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => 'mcp'])
            ->with('status', __($message));
    }

    private function backToUser(Model $user, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.users.show', $user->getRouteKey())
            ->with('status', __($message));
    }
}
