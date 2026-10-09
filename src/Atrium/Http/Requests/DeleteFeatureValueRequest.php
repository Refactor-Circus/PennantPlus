<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Http\Requests;

use Illuminate\Http\RedirectResponse;
use RefactorCircus\Atrium\Http\Requests\Request;
use RefactorCircus\PennantPlus\Atrium\Http\Requests\Concerns\AuthorizesFeatureFlags;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\DeleteFeatureValueAction;

class DeleteFeatureValueRequest extends Request
{
    use AuthorizesFeatureFlags;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'string', 'max:255'],
        ];
    }

    public function persist(): RedirectResponse
    {
        app(DeleteFeatureValueAction::class)->execute(
            $this->string('feature')->toString(),
            $this->string('scope')->toString(),
        );

        return redirect()->to($this->redirectToFeatureFlags())
            ->with('status', __('pennantplus::pennantplus.value_forgotten'));
    }
}
