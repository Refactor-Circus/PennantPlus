<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Requests;

use JayI\PennantPlus\Actions\DeleteFeatureValueAction;
use JayI\PennantPlus\Mcp\Request;
use Laravel\Mcp\Response;

final class ForgetFeatureValueMcpRequest extends Request
{
    protected function rules(): array
    {
        return DeleteFeatureValueAction::rules();
    }

    protected function handle(array $validated): Response
    {
        app(DeleteFeatureValueAction::class)->execute(
            (string) $validated['feature'],
            $this->features()->scopeFromInput($validated),
        );

        return Response::text('Feature value forgotten.');
    }
}
