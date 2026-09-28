<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\Response;
use JayI\PennantPlus\Actions\PurgeFeatureAction;
use JayI\PennantPlus\Http\Request;

final class PurgeFeatureRequest extends Request
{
    public function rules(): array
    {
        return PurgeFeatureAction::rules();
    }

    public function persist(): Response
    {
        app(PurgeFeatureAction::class)->execute($this->string('feature')->toString());

        return response()->noContent();
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameter('feature');
    }
}
