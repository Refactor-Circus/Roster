<x-atrium::layout :title="__('roster::roster.new_user')">
    <x-atrium::page-header :title="__('roster::roster.new_user')" />

    <div class="mt-5 flex max-w-2xl flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        <x-atrium::card>
            <form method="POST" action="{{ route('atrium.roster.users.store') }}" class="flex flex-col gap-4">
                @csrf

                @if ($hasName)
                    <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name')" required />
                @endif
                <x-atrium::form.input name="email" type="email" :label="__('roster::roster.email')" :value="old('email')" required />
                <x-atrium::form.input name="password" type="password" :label="__('roster::roster.password')" :hint="__('roster::roster.password_create_hint')" />
                @php($accountStatuses = \RefactorCircus\Roster\Support\Statuses::users())
                <x-atrium::form.select
                    name="status"
                    :label="__('roster::roster.initial_status')"
                    :options="[$accountStatuses::active()->value => $accountStatuses::active()->label(), $accountStatuses::pending()->value => $accountStatuses::pending()->label()]"
                    :selected="old('status', $accountStatuses::active()->value)"
                    data-testid="initial-status" />

                @include('roster::ui.users.partials.profile-fields', ['profile' => null])

                <div>
                    <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-user" />
                </div>
            </form>
        </x-atrium::card>
    </div>
</x-atrium::layout>
