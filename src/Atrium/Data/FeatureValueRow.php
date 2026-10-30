<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Data;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;

/**
 * One stored feature value as a row of the `pennantplus.features` screen.
 *
 * A row slot's row is a model, and a stored value is not one, so each value
 * is wrapped in this unsaved stand-in (after Atrium's `SettingsPanelRow`):
 * keyed by the value's feature and scope, so screen extensions remember their
 * answers per value, and carrying the value for registrants to read as
 * `$record->value`. It has no table, is never saved or reloaded, and so needs
 * no policy or lifecycle events.
 */
final class FeatureValueRow extends Model
{
    /** @var string */
    protected $primaryKey = 'key';

    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $incrementing = false;

    /** @var bool */
    public $timestamps = false;

    public private(set) StoredFeatureValue $value;

    public static function for(StoredFeatureValue $value): self
    {
        $row = new self;
        $row->forceFill(['key' => self::keyOf($value)]);
        $row->value = $value;

        return $row;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): never
    {
        throw self::readOnly('saved');
    }

    /**
     * @param  array<array-key, mixed>|string  $with
     */
    public function fresh($with = []): never
    {
        throw self::readOnly('reloaded');
    }

    public function refresh(): never
    {
        throw self::readOnly('reloaded');
    }

    public function delete(): never
    {
        throw self::readOnly('deleted');
    }

    /**
     * The feature, URL-encoded so a `|` in its name cannot be mistaken for the
     * separator, then the scope exactly as Pennant serialized and stored it.
     */
    private static function keyOf(StoredFeatureValue $value): string
    {
        return rawurlencode($value->feature).'|'.$value->scope;
    }

    private static function readOnly(string $verb): LogicException
    {
        return new LogicException("A FeatureValueRow stands in for a stored feature value and cannot be {$verb}.");
    }
}
