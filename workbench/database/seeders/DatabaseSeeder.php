<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use Workbench\App\Features\BetaDashboardFeature;
use Workbench\App\Features\BillingSupportFeature;
use Workbench\App\Features\ExternalWritesFeature;
use Workbench\App\Features\NewCheckoutFeature;
use Workbench\App\Features\ReportsFeature;
use Workbench\Database\Factories\UserFactory;

/**
 * Demo data: users, and feature flag values stored through PennantPlus's own
 * action, the same way the Feature flags page and the API store them.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = UserFactory::new()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        $ada = UserFactory::new()->create(['name' => 'Ada Lovelace', 'email' => 'ada@acme.test']);
        $grace = UserFactory::new()->create(['name' => 'Grace Hopper', 'email' => 'grace@acme.test']);
        $alan = UserFactory::new()->create(['name' => 'Alan Turing', 'email' => 'alan@globex.test']);
        UserFactory::new()->create(['name' => 'Eve Example', 'email' => 'eve@example.com']);

        $set = app(UpdateFeatureValueAction::class);
        $global = Feature::serializeScope(null);

        // Global values first: setting one forgets the feature's other scopes.
        $set->execute(ReportsFeature::class, $global, true);
        $set->execute(NewCheckoutFeature::class, $global, false);
        $set->execute(ExternalWritesFeature::class, $global, false);
        $set->execute(BetaDashboardFeature::class, $global, true);
        $set->execute(BillingSupportFeature::class, $global, true);
        $set->execute('dark-mode', $global, false);
        $set->execute('checkout-button-color', $global, 'blue');

        // Per-user overrides that differ from the global value.
        $set->execute(NewCheckoutFeature::class, Feature::serializeScope($admin), true);
        $set->execute(NewCheckoutFeature::class, Feature::serializeScope($ada), true);
        $set->execute(ReportsFeature::class, Feature::serializeScope($alan), false);
        $set->execute(BetaDashboardFeature::class, Feature::serializeScope($grace), false);
        $set->execute('dark-mode', Feature::serializeScope($admin), true);
        $set->execute('dark-mode', Feature::serializeScope($grace), true);
        $set->execute('checkout-button-color', Feature::serializeScope($ada), 'green');

        // Plain string scopes, such as a tenant or an API client.
        $set->execute('checkout-button-color', 'tenant:acme', 'purple');
        $set->execute(ExternalWritesFeature::class, 'tenant:acme', true);
    }
}
