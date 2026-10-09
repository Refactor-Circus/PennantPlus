<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\DeleteFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Http\Request;

final class DeleteFeatureValueRequest extends Request
{
    public function rules(): array
    {
        return DeleteFeatureValueAction::rules();
    }

    public function persist(): Response
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        app(DeleteFeatureValueAction::class)->execute(
            $this->string('feature')->toString(),
            app(FeatureFlagManager::class)->scopeFromInput($data),
        );

        return response()->noContent();
    }
}
