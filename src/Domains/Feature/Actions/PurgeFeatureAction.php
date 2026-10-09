<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturePurgedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturePurgingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Forget every stored value of a feature, for every scope.
 */
final class PurgeFeatureAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
        ];
    }

    public function execute(string $feature): void
    {
        FeaturePurgingActionEvent::dispatch($feature);

        $this->perform($feature);

        FeaturePurgedActionEvent::dispatch($feature);
    }

    private function perform(string $feature): void
    {
        DB::transaction(function () use ($feature): void {
            $this->features->store()->purge($feature);
        });
    }
}
