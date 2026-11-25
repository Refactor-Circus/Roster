<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Http\Requests\AcceptInvitationRequest;
use RefactorCircus\Roster\Domains\Invitation\Http\Requests\DeclineInvitationRequest;
use RefactorCircus\Roster\Domains\Invitation\Http\Requests\IndexInvitationsRequest;
use RefactorCircus\Roster\Domains\Invitation\Http\Requests\LookUpInvitationRequest;
use RefactorCircus\Roster\Domains\Invitation\Http\Requests\RevokeInvitationRequest;
use RefactorCircus\Roster\Domains\Invitation\Http\Requests\StoreInvitationRequest;

final class InvitationController
{
    public function index(IndexInvitationsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function revoke(RevokeInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function decline(DeclineInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function lookup(LookUpInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
