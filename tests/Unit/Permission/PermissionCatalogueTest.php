<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;

it('is bound as a singleton', function (): void {
    expect(app(PermissionCatalogue::class))->toBe(app(PermissionCatalogue::class));
});

it('adds a permission with its description and built-in role grants', function (): void {
    $catalogue = new PermissionCatalogue;

    expect($catalogue->permission('acme.widgets.approve', 'Approve widgets.', ['admin', 'lead']))->toBe($catalogue)
        ->and($catalogue->permission('acme.widgets.view', 'View widgets.'))->toBe($catalogue)
        ->and($catalogue->has('acme.widgets.approve'))->toBeTrue()
        ->and($catalogue->has('acme.widgets.delete'))->toBeFalse()
        ->and($catalogue->all())->toBe([
            'acme.widgets.approve' => ['description' => 'Approve widgets.', 'grantTo' => ['admin', 'lead']],
            'acme.widgets.view' => ['description' => 'View widgets.', 'grantTo' => []],
        ])
        ->and($catalogue->grantedTo('admin'))->toBe(['acme.widgets.approve'])
        ->and($catalogue->grantedTo('lead'))->toBe(['acme.widgets.approve'])
        ->and($catalogue->grantedTo('member'))->toBe([]);
});

it('registers the SSO and SCIM permissions from their domains, granted to admin', function (): void {
    $catalogue = app(PermissionCatalogue::class);

    expect($catalogue->all())->toMatchArray([
        'roster.sso.view' => ['description' => "View an organization's single sign-on connections.", 'grantTo' => ['admin']],
        'roster.sso.manage' => ['description' => 'Configure single sign-on connections.', 'grantTo' => ['admin']],
        'roster.scim.manage' => ['description' => 'Issue and revoke SCIM provisioning tokens.', 'grantTo' => ['admin']],
    ]);
});

it('rejects a name registered twice, naming it', function (): void {
    $catalogue = (new PermissionCatalogue)->permission('acme.widgets.approve', 'Approve widgets.');

    expect(fn () => $catalogue->permission('acme.widgets.approve', 'Again.'))
        ->toThrow(InvalidArgumentException::class, '[acme.widgets.approve]');
});

it('rejects a name Roster already defines, naming it', function (): void {
    expect(fn () => (new PermissionCatalogue)->permission('atrium.view', 'Open Atrium.'))
        ->toThrow(InvalidArgumentException::class, '[atrium.view]');
});

it('reserves roster.* for Roster, naming the permission', function (string $name): void {
    expect(fn () => (new PermissionCatalogue)->permission($name, 'Mine.'))
        ->toThrow(InvalidArgumentException::class, "[{$name}]");
})->with([
    'a new roster permission' => 'roster.widgets.approve',
    'a core permission' => 'roster.users.view',
]);

it('rejects a role slug that does not exist, naming it', function (): void {
    expect(fn () => (new PermissionCatalogue)->permission('acme.widgets.approve', 'Approve widgets.', ['admin', 'owner']))
        ->toThrow(InvalidArgumentException::class, '[owner]');
});

it('takes Roster\'s own permissions through the internal method only', function (): void {
    $catalogue = new PermissionCatalogue;

    expect($catalogue->rosterPermission('roster.widgets.approve', 'Approve widgets.', ['admin']))->toBe($catalogue)
        ->and($catalogue->has('roster.widgets.approve'))->toBeTrue()
        ->and(fn () => $catalogue->rosterPermission('acme.widgets.approve', 'Approve widgets.'))
        ->toThrow(InvalidArgumentException::class, '[acme.widgets.approve]')
        ->and(fn () => $catalogue->rosterPermission('roster.widgets.approve', 'Again.'))
        ->toThrow(InvalidArgumentException::class, '[roster.widgets.approve]')
        ->and(fn () => $catalogue->rosterPermission('roster.users.view', 'Core.'))
        ->toThrow(InvalidArgumentException::class, '[roster.users.view]')
        ->and(fn () => $catalogue->rosterPermission('roster.widgets.view', 'View widgets.', ['owner']))
        ->toThrow(InvalidArgumentException::class, '[owner]');
});

it('keeps a refused registration out', function (): void {
    $catalogue = new PermissionCatalogue;

    try {
        $catalogue->permission('acme.widgets.approve', 'Approve widgets.', ['owner']);
    } catch (InvalidArgumentException) {
        // Refused: nothing was added.
    }

    expect($catalogue->has('acme.widgets.approve'))->toBeFalse();
});
