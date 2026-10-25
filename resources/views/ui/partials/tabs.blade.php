{{--
    The tab bar of the organization and user screens.

    `tabs` lists the keys the viewer may open, in order; `tab` is the open one.
    A built-in tab takes its icon from `icons` (key => Atrium icon name) and
    its label from roster::roster.{key}. A registered tab (`registered`,
    key => TabDefinition) takes both from its definition, escaped, with
    squares-2x2 for an icon Atrium doesn't know. `href` turns a key into its
    ?tab= link; `label` names the bar.
--}}
<nav class="flex flex-wrap gap-2" aria-label="{{ $label }}">
    @foreach ($tabs as $name)
        @php($definition = $registered[$name] ?? null)
        <x-atrium::icon-button
            :icon="$definition === null ? $icons[$name] : (\RefactorCircus\Atrium\Support\Icons::has((string) $definition->icon) ? $definition->icon : 'squares-2x2')"
            :label="$definition === null ? __('roster::roster.'.$name) : $definition->label"
            :variant="$tab === $name ? 'primary' : 'ghost'"
            :href="$href($name)"
            :aria-current="$tab === $name ? 'page' : null"
            data-testid="tab-{{ $name }}" />
    @endforeach
</nav>
