<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Http\Requests;

use Illuminate\Http\RedirectResponse;
use RefactorCircus\Atrium\Http\Requests\Request;
use RefactorCircus\PennantPlus\Atrium\Http\Requests\Concerns\AuthorizesFeatureFlags;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\PurgeFeatureData;

class PurgeFeatureRequest extends Request
{
    use AuthorizesFeatureFlags;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return PurgeFeatureData::rules();
    }

    public function persist(): RedirectResponse
    {
        app(PurgeFeatureAction::class)->execute(PurgeFeatureData::fromValidated($this->validated()));

        return redirect()->route('atrium.pennant.index')
            ->with('status', __('pennantplus::pennantplus.feature_purged'));
    }
}
