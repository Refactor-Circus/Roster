@use(RefactorCircus\Roster\Support\Statuses)

<x-atrium::card :title="__('roster::roster.widget_user_status')">
    <div class="flex flex-wrap gap-2">
        @foreach ($counts as $status => $count)
            <a class="flex items-center gap-2 rounded-radius border border-outline px-3 py-2 transition hover:bg-surface-alt dark:border-outline-dark dark:hover:bg-surface-dark-alt"
               href="{{ route('atrium.roster.users.index', ['status' => $status]) }}">
                @include('roster::ui.partials.status-dot', ['status' => Statuses::user($status), 'variant' => null, 'label' => null, 'testid' => null])
                <span class="text-sm font-semibold tabular-nums">{{ $count }}</span>
            </a>
        @endforeach
    </div>
</x-atrium::card>
