<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Atrium\Http\Requests;

use Illuminate\Http\RedirectResponse;
use JayI\Atrium\Http\Requests\Request;
use JayI\PennantPlus\Actions\UpdateFeatureValueAction;
use JayI\PennantPlus\Atrium\Http\Requests\Concerns\AuthorizesFeatureFlags;
use JayI\PennantPlus\FeatureFlagManager;
use JayI\PennantPlus\Support\ScopeRules;

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
        return [
            'feature' => ['required', 'string', 'max:255'],
            ...ScopeRules::required(),
            'value' => ['required', 'json'],
        ];
    }

    public function persist(): RedirectResponse
    {
        /** @var array{feature: string, value: string} $data */
        $data = $this->validated();

        app(UpdateFeatureValueAction::class)->execute(
            $data['feature'],
            app(FeatureFlagManager::class)->scopeFromInput($data),
            json_decode($data['value'], true, flags: JSON_THROW_ON_ERROR),
        );

        return redirect()->to($this->redirectToFeatureFlags())
            ->with('atrium.status', __('pennantplus::pennantplus.value_saved'));
    }
}
