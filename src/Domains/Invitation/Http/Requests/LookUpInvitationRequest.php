<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Actions\LookUpInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\LookUpInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Exceptions\InvalidInvitationException;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationLookupResource;
use RefactorCircus\Roster\Http\Request;

/**
 * The public lookup behind `GET {prefix}/invitations/{token}`: the token is
 * the credential, so it needs no signed-in user and no permission. Every
 * token that is not a usable invitation gets one identical 404, and no
 * answer may be cached.
 */
final class LookUpInvitationRequest extends Request
{
    /**
     * No permission: whoever holds the token may see the invitation.
     */
    protected function ability(): string
    {
        return '';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return LookUpInvitationData::rules();
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return ['token' => $this->route('token')];
    }

    public function persist(): JsonResponse
    {
        try {
            $invitation = app(LookUpInvitationAction::class)->execute(LookUpInvitationData::fromValidated($this->validated()));
        } catch (InvalidInvitationException) {
            // One fixed answer for every refusal, so none tells which it was.
            return new JsonResponse(['message' => __('Not found.')], 404, ['Cache-Control' => 'no-store']);
        }

        return (new InvitationLookupResource($invitation))->response()->header('Cache-Control', 'no-store');
    }
}
