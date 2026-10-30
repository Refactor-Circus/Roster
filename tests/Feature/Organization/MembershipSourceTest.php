<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ListMembersTool;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Support\Tables;
use Workbench\App\Models\User;

/**
 * A membership row written straight to the table, as one stored before
 * `memberships.source` became a string.
 */
function storedMembership(OrganizationModel $organization, User $user, string $source): MembershipModel
{
    $id = (string) Str::ulid();

    DB::table(Tables::name('memberships'))->insert([
        'id' => $id,
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'source' => $source,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return MembershipModel::query()->findOrFail($id);
}

/**
 * Something that joins people up, the way Roster's Actions do.
 */
function membershipJoiner(): object
{
    return new class
    {
        use ManagesMemberships;

        public function joinAs(OrganizationModel $organization, Model $user, MembershipSource|string $source): MembershipModel
        {
            return $this->join($organization, $user, $source);
        }
    };
}

/**
 * Lines under these paths that treat `->source` as a MembershipSource: an
 * identity or equality check against a case, a `match` on it, an enum
 * property or method read from it, or a case passed to `where('source')`.
 *
 * @param  array<int, string>  $files
 * @return array<int, string>
 */
function membershipSourceEnumUses(array $files): array
{
    $patterns = [
        '/->source\s*[!=]==?\s*\\\\?(?:[\w\\\\]+\\\\)?MembershipSource::/',
        '/(?:[\w\\\\]+\\\\)?MembershipSource::\w+\s*[!=]==?\s*\$[\w>?-]*->source\b/',
        '/match\s*\(\s*\$[\w>?-]*->source\s*\)/',
        '/->source\??->\w+/',
        '/where(?:In|Not|NotIn)?\(\s*[\'"](?:[\w.]+\.)?source[\'"]\s*,\s*(?:[\'"][^\'"]*[\'"]\s*,\s*)?\[?\s*\\\\?(?:[\w\\\\]+\\\\)?MembershipSource::/',
    ];

    $found = [];

    foreach ($files as $file) {
        foreach (file($file) ?: [] as $number => $line) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $found[] = $file.':'.($number + 1).': '.trim($line);
                }
            }
        }
    }

    return $found;
}

it('casts the source to a string and gives built-in values their enum case', function (MembershipSource $case): void {
    $membership = storedMembership(organization(), user(), $case->value);

    expect($membership->source)->toBe($case->value)
        ->and($membership->sourceEnum())->toBe($case);
})->with(MembershipSource::cases());

it('gives no enum case for scim or a value nothing registers', function (string $source): void {
    $membership = storedMembership(organization(), user(), $source);

    expect($membership->source)->toBe($source)
        ->and($membership->sourceEnum())->toBeNull();
})->with(['scim', 'legacy-crm']);

it('still takes an enum case when one is assigned', function (): void {
    $membership = MembershipModel::factory()->for(organization(), 'organization')->create(['user_id' => user()->getKey()]);

    expect($membership->source)->toBe('direct')
        ->and($membership->fresh()?->source)->toBe('direct')
        ->and($membership->sourceEnum())->toBe(MembershipSource::Direct);
});

it('reads a membership stored as scim back as scim and shows SCIM', function (): void {
    $organization = organization();
    $membership = storedMembership($organization, user(), 'scim');

    expect($membership->source)->toBe('scim')
        ->and($membership->sourceEnum())->toBeNull()
        ->and((new MemberResource($membership))->resolve()['source'])->toBe('SCIM');
});

it('shows a source nothing registers as stored, without an error', function (): void {
    $membership = storedMembership(organization(), user(), 'legacy-crm');

    expect((new MemberResource($membership))->resolve()['source'])->toBe('legacy-crm')
        ->and((new MemberResource($membership))->toJson())->toContain('"source":"legacy-crm"');
});

it('declares the member source as a plain string', function (): void {
    $source = MemberResource::schema(new JsonSchemaTypeFactory)['source']->toArray();

    expect($source['type'])->toBe('string')
        ->and($source)->not->toHaveKey('enum');
});

it('passes the member tool output schema with registered and unregistered sources', function (): void {
    app(MembershipSources::class)->register('acme-sync', 'Acme sync');
    $organization = organization();
    storedMembership($organization, user(), 'scim');
    storedMembership($organization, user(), 'acme-sync');
    storedMembership($organization, user(), 'legacy-crm');

    $response = mcpTool(ListMembersTool::class, ['organization' => $organization->slug])->assertOk();

    $response->assertSee('SCIM')->assertSee('Acme sync')->assertSee('legacy-crm');
});

it('lists members with the registered label', function (): void {
    app(MembershipSources::class)->register('acme-sync', 'Acme sync');
    $organization = organization();
    storedMembership($organization, user(), 'scim');
    storedMembership($organization, user(), 'acme-sync');

    $sources = collect($this->getJson(route('roster.organizations.members.index', $organization->slug))
        ->assertOk()
        ->json('data'))
        ->pluck('source')
        ->sort()
        ->values()
        ->all();

    expect($sources)->toBe(['Acme sync', 'SCIM', 'direct']);
});

it('joins with a built-in case, a built-in value or a registered source', function (): void {
    app(MembershipSources::class)->register('acme-sync', 'Acme sync');
    $organization = organization();

    expect(membershipJoiner()->joinAs($organization, user(), MembershipSource::Invitation)->fresh()?->source)->toBe('invitation')
        ->and(membershipJoiner()->joinAs($organization, user(), 'domain')->fresh()?->source)->toBe('domain')
        ->and(membershipJoiner()->joinAs($organization, user(), 'acme-sync')->fresh()?->source)->toBe('acme-sync')
        ->and(membershipJoiner()->joinAs($organization, user(), 'scim')->fresh()?->source)->toBe('scim');
});

it('refuses to join with a source nothing registers, naming it', function (): void {
    $organization = organization();
    $ada = user();

    expect(fn () => membershipJoiner()->joinAs($organization, $ada, 'legacy-crm'))
        ->toThrow(InvalidArgumentException::class, '[legacy-crm]')
        ->and($organization->membershipFor($ada))->toBeNull();
});

it('finds every way of treating a membership source as an enum', function (string $line): void {
    $file = tempnam(sys_get_temp_dir(), 'roster-source');
    file_put_contents($file, "<?php\n{$line}\n");

    try {
        expect(membershipSourceEnumUses([$file]))->toHaveCount(1);
    } finally {
        unlink($file);
    }
})->with([
    'identity' => 'if ($membership->source === MembershipSource::Scim) {}',
    'negated identity' => 'if ($m?->source !== \RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource::Direct) {}',
    'case first' => 'if (MembershipSource::Direct == $membership->source) {}',
    'match' => 'return match ($membership->source) {};',
    'enum property' => 'echo $membership->source->value;',
    'enum method' => 'echo $membership?->source->label();',
    'where' => '$query->where(\'source\', MembershipSource::Domain);',
    'where with operator' => '$query->where("memberships.source", "!=", MembershipSource::Domain);',
    'whereIn' => '$query->whereIn(\'source\', [MembershipSource::Domain]);',
]);

it('has nothing in src or the views treat a membership source as an enum', function (): void {
    $root = dirname(__DIR__, 3);
    $files = [];

    foreach (['src', 'resources/views'] as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && str_ends_with($file->getFilename(), '.php')) {
                $files[] = $file->getPathname();
            }
        }
    }

    expect($files)->not->toBeEmpty()
        ->and(membershipSourceEnumUses($files))->toBe([]);
});
