<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use JayI\Atrium\Navigation\NavigationRegistry;
use JayI\Atrium\Navigation\NavItem;
use JayI\Atrium\Support\Icons;
use JayI\PennantPlus\Tests\TestCase;
use Laravel\Pennant\Feature;
use Orchestra\Testbench\Attributes\DefineEnvironment;

/**
 * The Feature flags screen shows its navigation item and each control only
 * when the action behind it would be allowed.
 */
class AtriumScreenControlsTest extends TestCase
{
    private bool $manages = true;

    protected function gatePennant($app): void
    {
        $app->make(Repository::class)->set('pennantplus.ability', 'manageFeatures');
    }

    public function test_without_an_ability_every_control_is_shown(): void
    {
        $ada = $this->user();

        Feature::for($ada)->activate('beta');

        $this->actingAs($ada)->get(route('atrium.pennant.index', ['feature' => 'beta']))
            ->assertOk()
            ->assertSee('data-testid="pennant-set-value"', false)
            ->assertSee('data-testid="pennant-save"', false)
            ->assertSee('data-testid="pennant-toggle"', false)
            ->assertSee('data-testid="pennant-forget"', false)
            ->assertSee('data-testid="pennant-purge"', false);

        $this->assertTrue($this->navigationShown($ada));
    }

    #[DefineEnvironment('gatePennant')]
    public function test_with_the_ability_granted_every_control_is_shown(): void
    {
        $ada = $this->user();

        Feature::for($ada)->activate('beta');

        $this->actingAs($ada)->get(route('atrium.pennant.index', ['feature' => 'beta']))
            ->assertOk()
            ->assertSee('data-testid="pennant-set-value"', false)
            ->assertSee('data-testid="pennant-toggle"', false)
            ->assertSee('data-testid="pennant-forget"', false)
            ->assertSee('data-testid="pennant-purge"', false);

        $this->assertTrue($this->navigationShown($ada));
    }

    #[DefineEnvironment('gatePennant')]
    public function test_with_the_ability_denied_the_navigation_and_every_action_are_refused(): void
    {
        $this->manages = false;

        $ada = $this->user();

        Feature::for($ada)->activate('beta');

        $this->assertFalse($this->navigationShown($ada));

        $this->actingAs($ada)->get(route('atrium.pennant.index'))->assertForbidden();
        $this->actingAs($ada)->getJson(route('atrium.pennant.scopes', ['type' => User::class, 'q' => 'ada']))->assertForbidden();

        $this->actingAs($ada)->put(route('atrium.pennant.values.update'), [
            'feature' => 'beta',
            'scope' => Feature::serializeScope($ada),
            'value' => 'false',
        ])->assertForbidden();

        $this->actingAs($ada)->delete(route('atrium.pennant.values.destroy'), [
            'feature' => 'beta',
            'scope' => Feature::serializeScope($ada),
        ])->assertForbidden();

        $this->actingAs($ada)->delete(route('atrium.pennant.features.purge'), ['feature' => 'beta'])
            ->assertForbidden();

        $this->assertDatabaseHas('features', ['name' => 'beta', 'scope' => Feature::serializeScope($ada), 'value' => 'true']);
    }

    #[DefineEnvironment('gatePennant')]
    public function test_the_blade_conditional_asks_the_same_ability(): void
    {
        $template = '@pennantplusManages shown @else hidden @endpennantplusManages';

        $user = $this->user();

        // Views render inside a request; give the test's request its user.
        request()->setUserResolver(fn (): User => $user);

        $this->assertSame('shown', trim(Blade::render($template)));

        $this->manages = false;

        $this->assertSame('hidden', trim(Blade::render($template)));
    }

    public function test_values_show_their_state_as_a_status_dot(): void
    {
        $ada = $this->user();

        Feature::for($ada)->activate('beta');
        Feature::for(null)->deactivate('beta');

        $this->actingAs($ada)->get(route('atrium.pennant.index'))
            ->assertOk()
            ->assertSee('data-status="active"', false)
            ->assertSee('data-status="inactive"', false)
            ->assertDontSee('<x-atrium::button', false);
    }

    public function test_the_navigation_item_uses_the_flag_icon(): void
    {
        $ada = $this->user();

        $item = $this->navigationItem($ada);

        $this->assertNotNull($item);
        $this->assertSame(Icons::svg('flag'), $item->icon);
    }

    public function test_the_set_value_form_saves_with_an_icon_button(): void
    {
        $this->actingAs($this->user())->get(route('atrium.pennant.index'))
            ->assertOk()
            ->assertSee('aria-label="'.__('pennantplus::pennantplus.save').'"', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('viewAtrium', fn (User $user): bool => true);
        Gate::define('manageFeatures', fn (User $user): bool => $this->manages);
    }

    private function navigationShown(User $user): bool
    {
        return $this->navigationItem($user) !== null;
    }

    private function navigationItem(User $user): ?NavItem
    {
        $request = Request::create('/atrium');
        $request->setUserResolver(fn (): User => $user);

        foreach (app(NavigationRegistry::class)->items($request) as $item) {
            if ($item->route === 'atrium.pennant.index') {
                return $item;
            }
        }

        return null;
    }

    private function user(string $email = 'ada@example.com'): User
    {
        return User::forceCreate(['name' => 'Ada', 'email' => $email, 'password' => 'x']);
    }
}
