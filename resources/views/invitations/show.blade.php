{{--
    The invitation page without the Atrium dashboard: plain HTML, styled
    inline because no stylesheet is loaded. With Atrium installed, Roster
    renders roster::ui.invitations.show instead.
--}}
@php($heading = __('roster::roster.invitation_title', ['organization' => $organization->name]))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-testid="invitation-plain">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;background:#f4f4f5;color:#18181b;font:400 .875rem/1.5 system-ui,sans-serif;">
    <main style="box-sizing:border-box;max-width:28rem;min-height:100vh;margin:0 auto;padding:2.5rem 1rem;display:flex;flex-direction:column;justify-content:center;">
        <div style="background:#ffffff;border:1px solid #e4e4e7;border-radius:.5rem;padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
            <h1 style="margin:0;font-size:1.125rem;font-weight:600;">{{ $heading }}</h1>

            <p style="margin:0;">{{ __('roster::roster.invitation_prompt', ['organization' => $organization->name]) }}</p>

            @if ($teams->isNotEmpty())
                <p style="margin:0;">{{ __('roster::roster.invitation_teams', ['teams' => $teams->join(', ')]) }}</p>
            @endif

            @if ($errors->any())
                <p role="alert" style="margin:0;padding:.5rem .75rem;border-radius:.375rem;background:#fef2f2;color:#b91c1c;">{{ $errors->first() }}</p>
            @endif

            <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
                <form method="POST" action="{{ route('roster.invitations.page.accept', $token) }}" style="margin:0;">
                    @csrf
                    <button type="submit" data-testid="accept-invitation" style="cursor:pointer;padding:.5rem 1rem;border:1px solid #18181b;border-radius:.375rem;background:#18181b;color:#ffffff;font:inherit;font-weight:500;">{{ __('roster::roster.accept') }}</button>
                </form>
                <form method="POST" action="{{ route('roster.invitations.page.decline', $token) }}" style="margin:0;">
                    @csrf
                    <button type="submit" data-testid="decline-invitation" style="cursor:pointer;padding:.5rem 1rem;border:1px solid #d4d4d8;border-radius:.375rem;background:transparent;color:inherit;font:inherit;font-weight:500;">{{ __('roster::roster.decline') }}</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
