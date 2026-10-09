<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Audit\Contracts\Auditable;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;

/**
 * A feature flag value was stored for a scope.
 *
 * A flag value is about no model, so the audit log (refactor-circus/keen) records the
 * entry with no subject and names the feature, scope and value as context.
 */
final class FeatureValueUpdatedActionEvent implements ActionFinishedEvent, Auditable
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public StoredFeatureValue $value,
    ) {}

    public function auditSubject(): ?Model
    {
        return null;
    }

    /**
     * @return array{feature: string, scope: string, value: mixed}
     */
    public function auditContext(): array
    {
        return [
            'feature' => $this->value->feature,
            'scope' => $this->value->scope,
            'value' => $this->value->value,
        ];
    }
}
