# References

## OrganizationUiController tabs

- **Location:** `src/Atrium/Http/Controllers/OrganizationUiController.php:66` (`TABS`), `:132-141` (filtering and `?tab=` authorization), `:282` (`offers()`)
- **Relevance:** the tab model registered tabs must join
- **Borrow:** tab => permission map filtered by `ScreenAccess::allows($permission, $model)`, and the fallback to the first allowed tab

## Organization show view tab bar

- **Location:** `resources/views/ui/organizations/show.blade.php:16-24`
- **Relevance:** where registered tabs render and how they're styled
- **Borrow:** the `$tabIcons` map, the `x-atrium::button` primary/ghost variants, `aria-current`, `data-testid="tab-{name}"`; extract into a shared partial for both screens

## UserUiController::show and users/show view

- **Location:** `src/Atrium/Http/Controllers/UserUiController.php:102`, `resources/views/ui/users/show.blade.php`
- **Relevance:** the screen that gains a tab bar; its current cards become the default tab
- **Borrow:** `authorizeScreen('roster.users.view', null, $target)` for user-scoped checks

## Atrium WidgetDefinition

- **Location:** `../Atrium/src/Domains/Widgets/Data/WidgetDefinition.php`
- **Relevance:** sibling-package precedent for a host-registered UI piece
- **Borrow:** `make(key)` + fluent `label/icon/view/component/resolve/authorize`, `public private(set)` state, private closures, view XOR component

## RedirectDomains

- **Location:** `src/Atrium/RedirectDomains.php`
- **Relevance:** precedent for a tab offered only when something else is present
- **Borrow:** static availability check consulted while the tab list is built
