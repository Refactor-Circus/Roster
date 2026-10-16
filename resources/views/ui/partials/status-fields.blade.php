{{--
    An organization's or team's own status and its reason, for its settings
    form. Callers pass `statuses` (the configured enum's cases) and `record`.
--}}
<x-atrium::form.select
    name="status"
    :label="__('roster::roster.status')"
    :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
    :selected="old('status', $record->status->value)"
    wrapper="w-72"
    data-testid="status" />
<x-atrium::form.input name="status_reason" :label="__('roster::roster.reason')" :value="old('status_reason', $record->status_reason)" wrapper="w-80" data-testid="status-reason" />
