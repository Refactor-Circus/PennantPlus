<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Http\Requests;

use Illuminate\Http\RedirectResponse;
use RefactorCircus\Atrium\Http\Requests\Request;
use RefactorCircus\PennantPlus\Atrium\Http\Requests\Concerns\AuthorizesFeatureFlags;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction;

class PurgeFeatureRequest extends Request
{
    use AuthorizesFeatureFlags;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
        ];
    }

    public function persist(): RedirectResponse
    {
        app(PurgeFeatureAction::class)->execute($this->string('feature')->toString());

        return redirect()->route('atrium.pennant.index')
            ->with('status', __('pennantplus::pennantplus.feature_purged'));
    }
}
