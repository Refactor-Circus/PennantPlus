<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Data;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use stdClass;

/**
 * One feature flag value Pennant has stored for one scope.
 */
final class StoredFeatureValue
{
    /** How the scope's model is shown, when it is a configured scope model. */
    public private(set) ?string $title = null;

    public function __construct(
        public private(set) string $feature,
        /** The scope exactly as Pennant serialized it. */
        public private(set) string $scope,
        public private(set) mixed $value,
        public private(set) ?Carbon $updatedAt = null,
    ) {}

    public static function fromRow(stdClass $row): self
    {
        $updatedAt = is_string($row->updated_at ?? null) ? Carbon::parse($row->updated_at) : null;

        return new self(
            (string) $row->name,
            (string) $row->scope,
            json_decode((string) $row->value, true, flags: JSON_THROW_ON_ERROR),
            $updatedAt,
        );
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function isGlobal(): bool
    {
        return $this->scope === Feature::serializeScope(null);
    }

    /**
     * The model type of a `Type|key` scope, null for any other scope.
     */
    public function scopeType(): ?string
    {
        return ! $this->isGlobal() && str_contains($this->scope, '|')
            ? Str::before($this->scope, '|')
            : null;
    }

    /**
     * The model key of a `Type|key` scope, null for any other scope.
     */
    public function scopeId(): ?string
    {
        return $this->scopeType() === null ? null : Str::after($this->scope, '|');
    }

    public function scopeLabel(): string
    {
        if ($this->isGlobal()) {
            return __('pennantplus::pennantplus.global');
        }

        $type = $this->scopeType();

        return $type === null ? $this->scope : class_basename($type).' #'.$this->scopeId();
    }

    /**
     * Pennant treats any stored value other than false as active.
     */
    public function isActive(): bool
    {
        return $this->value !== false;
    }

    /**
     * The value as shown in the dashboard: rich values are shown as JSON.
     */
    public function displayValue(): string
    {
        return is_bool($this->value)
            ? ($this->value ? 'true' : 'false')
            : (string) json_encode($this->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
