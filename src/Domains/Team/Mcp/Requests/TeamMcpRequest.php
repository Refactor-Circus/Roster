<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

abstract class TeamMcpRequest extends OrganizationMcpRequest
{
    private ?TeamModel $resolvedTeam = null;

    /**
     * The team named by the `team` slug argument, within the organization.
     */
    protected function team(): TeamModel
    {
        return $this->resolvedTeam ??= TeamModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->where('slug', $this->get('team'))
            ->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function teamRules(): array
    {
        return $this->organizationRules() + ['team' => ['required', 'string']];
    }

    /**
     * Every team tool names its organization and team.
     */
    protected function rules(): array
    {
        return $this->teamRules();
    }
}
