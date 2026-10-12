<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Mcp\Request;

/**
 * Accepting or declining acts as the authenticated MCP user.
 */
abstract class InvitationResponseMcpRequest extends Request
{
    /**
     * Answering needs no permission: only the invitee, signed in, can do it.
     */
    protected function ability(): string
    {
        return '';
    }

    public function authorize(): bool
    {
        return $this->actor() !== null;
    }

    protected function respondent(): Model
    {
        /** @var Model */
        return $this->actor();
    }
}
