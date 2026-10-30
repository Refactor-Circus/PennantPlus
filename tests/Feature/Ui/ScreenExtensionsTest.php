<?php

declare(strict_types=1);

// Namespaced so the marker component compiles as a class: Blade treats a
// component name without a backslash as a view.

namespace RefactorCircus\PennantPlus\Tests\Feature\Ui;

use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Screens\Data\ResolvedScreen;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow;
use RefactorCircus\PennantPlus\Atrium\Http\Controllers\FeatureFlagController;
use RefactorCircus\PennantPlus\Atrium\PennantPlugin;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;
use SplFileInfo;

/*
 * The feature flags page declares itself as the `pennantplus.features` list
 * screen, so other packages add tabs and sections to it without PennantPlus
 * knowing they exist.
 */

/**
 * What a registered tab or section renders: its `marker` data, or "marker".
 */
final class PennantScreenMarker extends Component
{
    public string $marker;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data = [])
    {
        $this->marker = is_string($data['marker'] ?? null) ? $data['marker'] : 'marker';
    }

    public function render(): string
    {
        return '<span>{{ $marker }}</span>';
    }
}

const PENNANT_SCREEN_SLOTS = ['header.actions', 'before', 'row.actions', 'after', 'aside'];

beforeEach(function (): void {
    Gate::define('viewAtrium', fn (?User $user): bool => true);

    Blade::component(PennantScreenMarker::class, 'pennant-marker');
});

/**
 * Register a public tab on the screen, and a public section in every
 * standard slot (`aside` included, which a list screen does not declare),
 * counting each section's resolver calls by slot.
 *
 * @param  array<string, int>  $calls
 */
function pennantScreenRegister(array &$calls): void
{
    Atrium::screens()->tab('pennantplus.features', TabDefinition::make('acme.tab')->label('Acme')->component('pennant-marker')->public()
        ->resolve(fn (): array => ['marker' => 'the acme panel']));

    foreach (PENNANT_SCREEN_SLOTS as $slot) {
        $calls[$slot] = 0;

        Atrium::screens()->section('pennantplus.features', $slot, SectionDefinition::make('acme.'.$slot)->component('pennant-marker')->public()
            ->resolve(function () use (&$calls, $slot): array {
                $calls[$slot]++;

                return ['marker' => 'in '.$slot];
            }));
    }
}

/**
 * The screen the page was rendered with.
 */
function pennantResolvedScreen(mixed $resolved): ResolvedScreen
{
    expect($resolved)->toBeInstanceOf(ResolvedScreen::class);

    assert($resolved instanceof ResolvedScreen);

    return $resolved;
}

it('declares the pennantplus.features list screen with the standard slots and no record', function (): void {
    $screen = pennantResolvedScreen($this->actingAs(makeUser())->get(route('atrium.pennant.index'))->assertOk()->viewData('resolved'))->screen;

    expect($screen->key)->toBe('pennantplus.features')
        ->and($screen->kind)->toBe(Screen::LIST)
        ->and($screen->record)->toBeNull()
        ->and($screen->slots)->toBe(['header.actions', 'before', 'after'])
        ->and($screen->rowSlots)->toBe(['row.actions'])
        ->and(array_map(fn (TabDefinition $tab): string => $tab->key, $screen->tabs))->toBe([Screen::OVERVIEW]);
});

it('renders each registered section once in its slot, and none in aside', function (): void {
    Feature::for(null)->activate('beta');

    $calls = [];
    pennantScreenRegister($calls);

    $html = (string) $this->actingAs(makeUser())->get(route('atrium.pennant.index'))->assertOk()->getContent();

    foreach (['header.actions', 'before', 'row.actions', 'after'] as $slot) {
        expect(substr_count($html, 'data-testid="section-acme.'.$slot.'"'))->toBe(1);
    }

    expect($html)->not->toContain('data-testid="section-acme.aside"')
        ->and($html)->not->toContain('in aside')
        ->and($calls)->toBe(['header.actions' => 1, 'before' => 1, 'row.actions' => 1, 'after' => 1, 'aside' => 0]);
});

it('lays out header actions, the tab bar, before, the values with their row actions, then after', function (): void {
    Feature::for(null)->activate('beta');

    $calls = [];
    pennantScreenRegister($calls);

    $this->actingAs(makeUser())->get(route('atrium.pennant.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'data-testid="section-acme.header.actions"',
            'data-testid="screen-tabs"',
            'data-testid="section-acme.before"',
            'data-testid="pennant-values"',
            'data-testid="pennant-value"',
            'data-testid="pennant-forget"',
            'data-testid="section-acme.row.actions"',
            '</tr>',
            '</table>',
            'data-testid="section-acme.after"',
        ], false);
});

it('opens the overview tab without ?tab=', function (): void {
    $calls = [];
    pennantScreenRegister($calls);

    $response = $this->actingAs(makeUser())->get(route('atrium.pennant.index'))
        ->assertOk()
        ->assertSee('data-testid="tab-overview"', false)
        ->assertSee('data-testid="tab-acme.tab"', false)
        ->assertDontSee('data-testid="tab-panel-acme.tab"', false)
        ->assertSee('data-testid="pennant-set-value"', false);

    $resolved = pennantResolvedScreen($response->viewData('resolved'));

    expect($resolved->openTab)->toBe(Screen::OVERVIEW)
        ->and($resolved->openRegisteredTab)->toBeNull();
});

it('opens a registered tab through ?tab= in place of the page, keeping only the header actions', function (): void {
    Feature::for(null)->activate('beta');

    $calls = [];
    pennantScreenRegister($calls);

    $response = $this->actingAs(makeUser())->get(route('atrium.pennant.index', ['tab' => 'acme.tab']))
        ->assertOk()
        ->assertSeeInOrder([
            'data-testid="section-acme.header.actions"',
            'data-testid="screen-tabs"',
            'data-testid="tab-panel-acme.tab"',
            'the acme panel',
        ], false);

    foreach (['before', 'row.actions', 'after', 'aside'] as $slot) {
        $response->assertDontSee('data-testid="section-acme.'.$slot.'"', false);
    }

    $response->assertDontSee('data-testid="pennant-values"', false)
        ->assertDontSee('data-testid="pennant-set-value"', false)
        ->assertDontSee('data-testid="pennant-filters"', false);

    expect(pennantResolvedScreen($response->viewData('resolved'))->openTab)->toBe('acme.tab')
        ->and($calls)->toBe(['header.actions' => 1, 'before' => 0, 'row.actions' => 0, 'after' => 0, 'aside' => 0]);
});

it('answers an unknown ?tab= with the overview', function (): void {
    Feature::for(null)->activate('beta');

    $calls = [];
    pennantScreenRegister($calls);

    $response = $this->actingAs(makeUser())->get(route('atrium.pennant.index', ['tab' => 'unknown']))
        ->assertOk()
        ->assertSee('data-testid="pennant-values"', false)
        ->assertDontSee('data-testid="tab-panel-', false);

    expect(pennantResolvedScreen($response->viewData('resolved'))->openTab)->toBe(Screen::OVERVIEW);
});

it('wraps only the current page of values in rows', function (): void {
    foreach (range(1, 30) as $team) {
        Feature::for('team-'.$team)->activate('beta');
    }

    $response = $this->actingAs(makeUser())->get(route('atrium.pennant.index', ['page' => 2]))->assertOk();

    $values = $response->viewData('values');
    $rows = $response->viewData('rows');

    expect($values->currentPage())->toBe(2)
        ->and($rows)->toHaveCount(5)
        ->and(array_map(fn (FeatureValueRow $row): StoredFeatureValue => $row->value, $rows))->toBe($values->items())
        ->and(substr_count((string) $response->getContent(), 'data-testid="pennant-value"'))->toBe(5);
});

it('renders a row section once per value row, with that row as its record', function (): void {
    Feature::for(null)->activate('beta');
    Feature::for('team-7')->deactivate('beta');

    $records = [];

    Atrium::screens()->section('pennantplus.features', 'row.actions', SectionDefinition::make('acme.row')->component('pennant-marker')
        ->authorize(function (Request $request, ?Model $record) use (&$records): bool {
            $records[] = $record;

            return true;
        }));

    $response = $this->actingAs(makeUser())->get(route('atrium.pennant.index'))->assertOk();

    $rows = $response->viewData('rows');

    expect(substr_count((string) $response->getContent(), 'data-testid="section-acme.row"'))->toBe(2)
        ->and($rows)->toHaveCount(2)
        ->and($records)->toBe($rows)
        ->and(array_unique(array_map(fn (FeatureValueRow $row): string => (string) $row->getKey(), $rows)))->toHaveCount(2);
});

it('shows a permission() section only once the Gate defines its ability', function (bool $defined): void {
    if ($defined) {
        Gate::define('acme.extra', fn (User $user): bool => true);
    }

    Atrium::screens()->section('pennantplus.features', 'before', SectionDefinition::make('acme.extra')->component('pennant-marker')->permission('acme.extra'));

    $html = (string) $this->actingAs(makeUser())->get(route('atrium.pennant.index'))->assertOk()->getContent();

    expect(str_contains($html, 'data-testid="section-acme.extra"'))->toBe($defined);
})->with([
    'defined' => [true],
    'not defined' => [false],
]);

it('denies a row permission() the Gate does not define', function (): void {
    Feature::for(null)->activate('beta');

    Atrium::screens()->section('pennantplus.features', 'row.actions', SectionDefinition::make('acme.row')->component('pennant-marker')->permission('acme.row'));

    $this->actingAs(makeUser())->get(route('atrium.pennant.index'))
        ->assertOk()
        ->assertSee('data-testid="pennant-value"', false)
        ->assertDontSee('data-testid="section-acme.row"', false);
});

it('asks the Gate about a row permission() with the FeatureValueRow', function (): void {
    Feature::for(null)->activate('beta');

    $received = [];

    Gate::define('acme.row', function (User $user, mixed ...$arguments) use (&$received): bool {
        $received[] = $arguments;

        return true;
    });

    Atrium::screens()->section('pennantplus.features', 'row.actions', SectionDefinition::make('acme.row')->component('pennant-marker')->permission('acme.row'));

    $response = $this->actingAs(makeUser())->get(route('atrium.pennant.index'))
        ->assertOk()
        ->assertSee('data-testid="section-acme.row"', false);

    $rows = $response->viewData('rows');

    expect($rows)->toHaveCount(1)
        ->and($received)->toBe([[$rows[0]]]);
});

it('evaluates a permission() for a request without a user as a guest', function (): void {
    $users = [];

    Gate::define('acme.guest', function (?User $user) use (&$users): bool {
        $users[] = $user;

        return true;
    });
    Gate::define('acme.member', fn (User $user): bool => true);

    Atrium::screens()
        ->section('pennantplus.features', 'before', SectionDefinition::make('acme.guest')->component('pennant-marker')->permission('acme.guest'))
        ->section('pennantplus.features', 'before', SectionDefinition::make('acme.member')->component('pennant-marker')->permission('acme.member'));

    $view = app(FeatureFlagController::class)->index(Request::create(route('atrium.pennant.index')));

    expect($view)->toBeInstanceOf(View::class);
    assert($view instanceof View);

    $sections = pennantResolvedScreen($view->getData()['resolved'])->sections('before');

    expect(array_map(fn (SectionDefinition $section): string => $section->key, $sections))->toBe(['acme.guest'])
        ->and($users)->toBe([null]);
});

it('refuses a user who may not manage flags before any extension is asked', function (?string $tab): void {
    config()->set('pennantplus.ability', 'manage-flags');
    Gate::define('manage-flags', fn (User $user): bool => false);

    $calls = 0;

    $authorize = function () use (&$calls): bool {
        $calls++;

        return true;
    };

    $resolve = function () use (&$calls): array {
        $calls++;

        return [];
    };

    Atrium::screens()->tab('pennantplus.features', TabDefinition::make('acme.tab')->component('pennant-marker')->authorize($authorize)->resolve($resolve));
    Atrium::screens()->tab('*', TabDefinition::make('acme.everywhere')->component('pennant-marker')->authorize($authorize)->resolve($resolve));

    foreach (PENNANT_SCREEN_SLOTS as $slot) {
        Atrium::screens()
            ->section('pennantplus.features', $slot, SectionDefinition::make('acme.'.$slot)->component('pennant-marker')->authorize($authorize)->resolve($resolve))
            ->section('pennantplus.*', $slot, SectionDefinition::make('acme.package.'.$slot)->component('pennant-marker')->authorize($authorize)->resolve($resolve));
    }

    Feature::for(null)->activate('beta');

    $this->actingAs(makeUser())->get(route('atrium.pennant.index', array_filter(['tab' => $tab])))->assertForbidden();

    expect($calls)->toBe(0);
})->with([
    'the page' => [null],
    'a registered tab' => ['acme.tab'],
]);

it('renders no tab bar, panel or section while nothing is registered', function (): void {
    Feature::for(null)->activate('beta');

    $html = (string) $this->actingAs(makeUser())->get(route('atrium.pennant.index', ['feature' => 'beta']))
        ->assertOk()
        ->assertSee('data-testid="pennant-set-value"', false)
        ->assertSee('data-testid="pennant-filters"', false)
        ->assertSee('data-testid="pennant-values"', false)
        ->assertSee('data-testid="pennant-value"', false)
        ->assertSee('data-testid="pennant-toggle"', false)
        ->assertSee('data-testid="pennant-forget"', false)
        ->assertSee('data-testid="pennant-purge"', false)
        ->getContent();

    expect($html)
        ->not->toContain('data-testid="screen-tabs"')
        ->not->toContain('data-testid="tab-')
        ->not->toContain('data-testid="section-');
});

it('shows no audit trail on the feature flags page', function (): void {
    Feature::for(null)->activate('beta');

    $this->actingAs(makeUser())->get(route('atrium.pennant.index', ['feature' => 'beta']))
        ->assertOk()
        ->assertDontSee('data-testid="audit-trail"', false)
        ->assertDontSee('audit-trail', false);
});

it('links no history from its navigation', function (): void {
    $urls = array_map(fn (NavItem $item): string => (string) $item->resolveUrl(), app(PennantPlugin::class)->navigation());

    $history = array_values(array_filter([
        Route::has('atrium.history.show') ? route('atrium.history.show', 'pennantplus') : null,
        route('pennantplus.history.index'),
    ]));

    expect($urls)->toBe([route('atrium.pennant.index')])
        ->and(array_intersect($urls, $history))->toBe([])
        ->and(array_filter($urls, fn (string $url): bool => preg_match('#/history(/|\?|$)#', $url) === 1))->toBe([]);
});

it('leaves no audit trail or history nav item in the code', function (): void {
    $root = dirname(__DIR__, 3);
    $found = [];

    foreach (['src', 'resources'] as $directory) {
        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            // Code only: the Markdown docs under resources are checked with
            // the README.
            if ($file->getExtension() === 'md') {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            foreach (['audit-trail', 'x-atrium::audit', 'historyNavItem'] as $needle) {
                if (str_contains($contents, $needle)) {
                    $found[] = substr($file->getPathname(), strlen($root) + 1).': '.$needle;
                }
            }
        }
    }

    expect($found)->toBe([]);
});
