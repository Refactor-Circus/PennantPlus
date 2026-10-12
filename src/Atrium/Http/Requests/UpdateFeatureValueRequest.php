<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Http\Requests;

use Illuminate\Http\RedirectResponse;
use RefactorCircus\Atrium\Http\Requests\Request;
use RefactorCircus\PennantPlus\Atrium\Http\Requests\Concerns\AuthorizesFeatureFlags;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;

/**
 * Stores a feature flag value for a scope from the dashboard form.
 *
 * The scope arrives either already serialized, from a row of stored values,
 * or built in the form: the global scope, a model configured in
 * `pennantplus.scopes` and its key, or any other string scope. The value is
 * typed as JSON.
 */
class UpdateFeatureValueRequest extends Request
{
    use AuthorizesFeatureFlags;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return UpdateFeatureValueData::rules(encoded: true);
    }

    public function persist(): RedirectResponse
    {
        app(UpdateFeatureValueAction::class)->execute(UpdateFeatureValueData::fromEncoded($this->validated()));

        return redirect()->to($this->redirectToFeatureFlags())
            ->with('status', __('pennantplus::pennantplus.value_saved'));
    }
}
