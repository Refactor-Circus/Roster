<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Support\Authorization\Authorizer;

it('never carries resolved permissions from one request into the next', function (): void {
    $admin = user();
    grant($admin, 'super-admin');

    expect(app(Authorizer::class)->check($admin, 'roster.users.view'))->toBeTrue();

    // A long-lived worker (Octane, queues) forgets scoped instances between
    // requests; a revoked role must stop passing on the next one.
    RoleAssignmentModel::query()->where('user_id', $admin->getKey())->delete();
    app()->forgetScopedInstances();

    expect(app(Authorizer::class)->check($admin, 'roster.users.view'))->toBeFalse();
});
