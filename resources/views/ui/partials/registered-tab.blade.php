{{--
    The open registered tab's body.

    `roster` holds Roster's variables: the record (`organization` or `user`),
    `tab`, `tabs`, `registeredTab` and `errors`; `data` is what the tab's
    resolver returned. A view gets the resolver's data with Roster's
    variables spread over it, so a resolver can never replace them. A
    component gets the record as its `organization` / `user` prop and the
    resolver's data as one `data` prop, never spread into its attributes.
--}}
@if ($registeredTab->view !== null)
    @include($registeredTab->view, [...$data, ...$roster])
@elseif (array_key_exists('organization', $roster))
    <x-dynamic-component :component="$registeredTab->component" :organization="$roster['organization']" :data="$data" />
@else
    <x-dynamic-component :component="$registeredTab->component" :user="$roster['user']" :data="$data" />
@endif
