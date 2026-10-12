<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\DeleteFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\DeleteFeatureValueData;
use RefactorCircus\PennantPlus\Http\Request;

final class DeleteFeatureValueRequest extends Request
{
    public function rules(): array
    {
        return DeleteFeatureValueData::rules();
    }

    public function persist(): Response
    {
        app(DeleteFeatureValueAction::class)->execute(DeleteFeatureValueData::fromValidated($this->validated()));

        return response()->noContent();
    }
}
