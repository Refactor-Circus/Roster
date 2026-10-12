<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Every list Data class that pages, by its file.
 *
 * @return array<int, class-string>
 */
function pagedLists(): array
{
    return array_values(array_filter(
        array_map(
            fn (string $path): string => 'RefactorCircus\\Roster\\Domains\\'.basename(dirname($path, 2)).'\\Data\\'.basename($path, '.php'),
            (array) glob(dirname(__DIR__, 2).'/src/Domains/*/Data/List*Data.php'),
        ),
        fn (string $class): bool => method_exists($class, 'pagination'),
    ));
}

it('pages every list through its pagination, with the same default its field starts at', function (): void {
    expect(pagedLists())->toHaveCount(13);

    foreach (pagedLists() as $class) {
        /** @var Pagination $pagination */
        $pagination = $class::pagination();
        $perPage = (new ReflectionClass($class))->getConstructor()?->getParameters();
        $default = collect($perPage)->firstWhere(fn (ReflectionParameter $parameter): bool => $parameter->getName() === 'perPage')?->getDefaultValue();

        expect($default)->toBe($pagination->default, $class.'::$perPage starts at '.$default.', its pagination at '.$pagination->default)
            ->and($class::rules())->toHaveKeys(['per_page', 'page']);
    }
});
