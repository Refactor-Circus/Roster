<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Enums;

/**
 * Roster's built-in ways a user joins an organization. Other packages add
 * theirs to `Services\MembershipSources`; `MembershipModel::sourceEnum()`
 * answers a stored value's case, or null for those.
 */
enum MembershipSource: string
{
    case Direct = 'direct';
    case Invitation = 'invitation';
    case Domain = 'domain';
    case Personal = 'personal';
    case Import = 'import';
}
