<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Testing\Arch;
use RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow;

const PENNANTPLUS_DOMAINS = __DIR__.'/../src/Domains';

const PENNANTPLUS_ACTIONS = [PENNANTPLUS_DOMAINS.'/*/Actions/*.php'];

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\PennantPlus')
    ->toUseStrictTypes();

arch('actions are final classes')
    ->expect(Arch::domainNamespaces('RefactorCircus\PennantPlus', PENNANTPLUS_DOMAINS, 'Actions'))
    ->classes()
    ->toBeFinal();

arch('actions take their input as Data objects, not arrays')
    ->expect(fn (): array => array_values(array_filter(
        array_map(function (string $action): ?string {
            foreach ((new ReflectionMethod($action, 'execute'))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && in_array($parameter->getName(), ['data', 'filters', 'input', 'attributes', 'values'], true)) {
                    return $action.'::execute($'.$parameter->getName().')';
                }
            }

            return null;
        }, Arch::domainClasses('RefactorCircus\PennantPlus', PENNANTPLUS_DOMAINS, 'Actions')),
    )))
    ->toBeEmpty();

arch('action input validates itself')
    ->expect(fn (): array => Arch::dataWithoutRules(Arch::domainClasses('RefactorCircus\PennantPlus', PENNANTPLUS_DOMAINS, 'Data')))
    ->toBeEmpty();

arch('every action dispatches a starting and a finished event')
    ->expect(fn (): array => Arch::actionEventGaps(PENNANTPLUS_ACTIONS))
    ->toBeEmpty();

// Pennant's own store calls write too: count them alongside Eloquent's.
arch('actions write inside a transaction')
    ->expect(fn (): array => Arch::writesOutsideTransactions(PENNANTPLUS_ACTIONS, ['activate', 'deactivate', 'activateForEveryone', 'deactivateForEveryone', 'forget', 'purge', 'set', 'setForAllScopes']))
    ->toBeEmpty();

arch('every action lives in a domain Actions directory')
    ->expect(fn (): array => array_values(array_filter(
        array_map(fn (SplFileInfo $file): string => $file->getPathname(), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src', FilesystemIterator::SKIP_DOTS)))),
        fn (string $path): bool => str_ends_with($path, 'Action.php') && basename(dirname($path)) !== 'Actions',
    )))
    ->toBeEmpty();

// PennantPlus stores its values through Pennant and has no models with a
// table today; this catches any Eloquent model added under src.
arch('every model fires an event for every lifecycle hook')
    ->expect(fn (): array => Arch::modelEventGaps(array_values(array_filter(
        array_map(
            fn (SplFileInfo $file): string => 'RefactorCircus\\PennantPlus\\'.str_replace('/', '\\', substr($file->getPathname(), strlen(__DIR__.'/../src/'), -4)),
            array_filter(
                iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src', FilesystemIterator::SKIP_DOTS)), false),
                fn (SplFileInfo $file): bool => $file->getExtension() === 'php' && ctype_upper($file->getFilename()[0]),
            ),
        ),
        // FeatureValueRow is the screen's unsaved row stand-in for a stored
        // value, not a model with a table: it has no lifecycle to announce,
        // and it refuses to save, reload or delete (see the rule below).
        fn (string $class): bool => $class !== FeatureValueRow::class
            && class_exists($class) && is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract(),
    ))))
    ->toBeEmpty();

arch('the excluded row stand-in is final and has no table')
    ->expect(fn (): array => array_values(array_filter([
        (new ReflectionClass(FeatureValueRow::class))->isFinal() ? null : 'not final',
        (new ReflectionProperty(FeatureValueRow::class, 'table'))->getDeclaringClass()->getName() === Model::class ? null : 'declares $table',
        (new ReflectionMethod(FeatureValueRow::class, 'getTable'))->getDeclaringClass()->getName() === Model::class ? null : 'overrides getTable()',
    ])))
    ->toBeEmpty();

arch('requests validate once, with the Data rules, and build from what they validated')
    ->expect(fn (): array => array_values(array_filter(
        [...(array) glob(PENNANTPLUS_DOMAINS.'/*/Http/Requests/*.php'), ...(array) glob(PENNANTPLUS_DOMAINS.'/*/Mcp/Requests/*.php'), ...(array) glob(__DIR__.'/../src/Atrium/Http/Requests/*.php')],
        fn (string $path): bool => str_contains((string) file_get_contents($path), 'Data::from('),
    )))
    ->toBeEmpty();
