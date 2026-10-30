<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Policies\Policy as KeystonePolicy;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * Shared checks for the bundled policies.
 *
 * Core policies are registered from `roster.policies`, and package
 * resources register theirs from their providers; either way an application
 * swaps one by pointing its model at another class in `roster.policies`. The bundled policies
 * answer with Roster's own permissions: a method asks whether the user holds
 * the permission in the organization or team the subject belongs to.
 *
 * Roster's API, MCP tools and Atrium screens reach a policy with the model's
 * class, the organization or team the call is about, and the user the call is
 * about when everyone may do it to themselves:
 *
 *     Gate::forUser($actor)->allows('update', [OrganizationModel::class, $organization, null]);
 *
 * An application can ask with a record instead, as with any policy:
 * `$user->can('update', $organization)`.
 */
abstract class Policy extends KeystonePolicy
{
    /**
     * Whether the user holds a Roster permission where the subject lives.
     */
    protected function permits(?Model $user, string $permission, ?Model $subject = null, ?Model $self = null): bool
    {
        return app(Authorizer::class)->permits($user, $permission, $this->scope($subject), $self);
    }

    /**
     * The organization or team a subject is, or belongs to; null for global.
     */
    protected function scope(?Model $subject): OrganizationModel|TeamModel|null
    {
        if ($subject === null || $subject instanceof OrganizationModel || $subject instanceof TeamModel) {
            return $subject;
        }

        // The narrowest first: a team check also counts the team's organization.
        // Loaded relations are used as they are; otherwise the parent is found
        // by key, once per request (Authorizer::parentOf()).
        $authorizer = app(Authorizer::class);

        foreach (['team', 'organization'] as $relation) {
            $parent = $authorizer->parentOf($subject, $relation);

            if ($parent !== null) {
                return $parent;
            }
        }

        return null;
    }
}
