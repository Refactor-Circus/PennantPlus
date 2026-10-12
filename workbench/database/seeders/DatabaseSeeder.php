<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;
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
        $set->execute(new UpdateFeatureValueData(ReportsFeature::class, true, scope: $global));
        $set->execute(new UpdateFeatureValueData(NewCheckoutFeature::class, false, scope: $global));
        $set->execute(new UpdateFeatureValueData(ExternalWritesFeature::class, false, scope: $global));
        $set->execute(new UpdateFeatureValueData(BetaDashboardFeature::class, true, scope: $global));
        $set->execute(new UpdateFeatureValueData(BillingSupportFeature::class, true, scope: $global));
        $set->execute(new UpdateFeatureValueData('dark-mode', false, scope: $global));
        $set->execute(new UpdateFeatureValueData('checkout-button-color', 'blue', scope: $global));

        // Per-user overrides that differ from the global value.
        $set->execute(new UpdateFeatureValueData(NewCheckoutFeature::class, true, scope: Feature::serializeScope($admin)));
        $set->execute(new UpdateFeatureValueData(NewCheckoutFeature::class, true, scope: Feature::serializeScope($ada)));
        $set->execute(new UpdateFeatureValueData(ReportsFeature::class, false, scope: Feature::serializeScope($alan)));
        $set->execute(new UpdateFeatureValueData(BetaDashboardFeature::class, false, scope: Feature::serializeScope($grace)));
        $set->execute(new UpdateFeatureValueData('dark-mode', true, scope: Feature::serializeScope($admin)));
        $set->execute(new UpdateFeatureValueData('dark-mode', true, scope: Feature::serializeScope($grace)));
        $set->execute(new UpdateFeatureValueData('checkout-button-color', 'green', scope: Feature::serializeScope($ada)));

        // Plain string scopes, such as a tenant or an API client.
        $set->execute(new UpdateFeatureValueData('checkout-button-color', 'purple', scope: 'tenant:acme'));
        $set->execute(new UpdateFeatureValueData(ExternalWritesFeature::class, true, scope: 'tenant:acme'));
    }
}
