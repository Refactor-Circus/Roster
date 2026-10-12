<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Http;

use RefactorCircus\Keystone\Http\Requests\Request as BaseRequest;
use RefactorCircus\Roster\Support\Concerns\AuthorizesWithRoster;

/**
 * Base HTTP request for Roster, on refactor-circus/keystone's shared request.
 *
 * Validation rules come from the Data the request's Action takes, and
 * `persist()` calls that Action. The MCP surface does the same, so both speak
 * to one implementation rather than two that drift. Authorization is Roster's
 * own permissions (AuthorizesWithRoster); guests get a 401, signed-in users
 * without the permission a 403.
 */
abstract class Request extends BaseRequest
{
    use AuthorizesWithRoster;
}
