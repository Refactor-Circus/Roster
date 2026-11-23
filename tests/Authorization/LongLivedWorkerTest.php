<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Support\Authorization\Authorizer;

it('never carries resolved permissions from one request into the next', function (): void {
    $admin = user();
    grant($admin, 'super-admin');

    expect(app(Authorizer::class)->check($admin, 'roster.users.view'))->toBeTrue();

    // A long-lived worker (Octane, queues) forgets scoped instances between
    // requests; a revoked role must stop passing on the next one.
    app(Roles::class)->revoke($admin);
    app()->forgetScopedInstances();

    expect(app(Authorizer::class)->check($admin, 'roster.users.view'))->toBeFalse();
});
