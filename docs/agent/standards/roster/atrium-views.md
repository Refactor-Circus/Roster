---
description: "Atrium components and safelisted utilities only; no Roster CSS, components or inline styles"
tags: [roster, atrium, frontend, blade, views]
kind: convention
---

# Atrium Views

Roster ships no stylesheet and no Blade component namespace: its views are built from Atrium's components and safelisted utilities only.

## Components and styles

- Views use `x-atrium::*` components: `form.actions` for a form row's buttons, `description-list` for label/value pairs, `flash` for the status and first error, `guest` for standalone pages such as the invitation page.
- Only the utilities safelisted in Atrium's `resources/css/atrium.css`. No `<style>` blocks and no `style=` attributes.
- A class missing from Atrium's safelist: use the closest safelisted one and ask for it in Atrium. Never add CSS here.
- `tests/Feature/Ui/StylesTest.php` asserts `AtriumStyles::missingClasses()` and `AtriumStyles::inlineStyles()` are empty for `resources/views`.

## Partials

- Shared markup is a plain partial included with every key it reads, so the including view's variables never leak in:
  `@include('roster::ui.partials.status-dot', ['status' => …, 'variant' => null, 'label' => null, 'testid' => null])`.

## Host-application pages

- Markup shown on the host application's own pages, where Atrium's stylesheet is absent, uses Atrium's `standalone` components: the impersonation banner (`roster::impersonation-banner`) is `<x-atrium::banner standalone>` with `<x-atrium::banner.button standalone>`.

## Audit history

- Roster renders no history. Every screen is declared on Atrium's screen registry, and refactor-circus/keen attaches its History section to those screens from its own config. Never add `<x-atrium::audit-trail>` or `historyNavItem()`.

## Test hooks

- Test hooks are `data-testid` attributes.
