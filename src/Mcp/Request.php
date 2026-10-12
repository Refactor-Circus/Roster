<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Mcp;

use RefactorCircus\Keystone\Mcp\Requests\Request as BaseRequest;
use RefactorCircus\Roster\Support\Concerns\AuthorizesWithRoster;

/**
 * Base MCP request for Roster, on refactor-circus/keystone's shared request.
 *
 * Mirrors the HTTP request's `persist()` pattern so tools stay one line and
 * both surfaces resolve the same Actions; the shared base marks every call
 * as coming over MCP. Authorization is Roster's own permissions, as over HTTP
 * (AuthorizesWithRoster).
 */
abstract class Request extends BaseRequest
{
    use AuthorizesWithRoster;
}
