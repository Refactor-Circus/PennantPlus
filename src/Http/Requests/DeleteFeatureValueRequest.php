<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\Response;
use JayI\PennantPlus\Actions\DeleteFeatureValueAction;
use JayI\PennantPlus\FeatureFlagManager;
use JayI\PennantPlus\Http\Request;

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
