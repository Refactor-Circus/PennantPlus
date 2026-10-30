<?php

declare(strict_types=1);

use RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;

/*
 * A stored value as a row of the `pennantplus.features` screen: an unsaved,
 * read-only model stand-in keyed by the feature and the scope Pennant stores.
 */

it('keys a global value by its feature and the serialized global scope', function (): void {
    $value = new StoredFeatureValue('beta', '__laravel_null', true);
    $row = FeatureValueRow::for($value);

    expect($row->getKey())->toBe('beta|__laravel_null')
        ->and($row->value)->toBe($value);
});

it('keys a scoped value by its feature and the serialized scope', function (): void {
    $value = new StoredFeatureValue('beta', 'App\Models\User|7', false);
    $row = FeatureValueRow::for($value);

    expect($row->getKey())->toBe('beta|App\Models\User|7')
        ->and($row->value)->toBe($value);
});

it('keeps a feature name containing | from colliding with another value', function (): void {
    $piped = FeatureValueRow::for(new StoredFeatureValue('a|b', 'c', true));
    $plain = FeatureValueRow::for(new StoredFeatureValue('a', 'b|c', true));

    expect($piped->getKey())->toBeString()
        ->and($plain->getKey())->toBeString()
        ->and($piped->getKey())->not->toBe($plain->getKey());
});

it('is an unsaved string-keyed model without timestamps', function (): void {
    $row = FeatureValueRow::for(new StoredFeatureValue('beta', '__laravel_null', true));

    expect($row->getKeyName())->toBe('key')
        ->and($row->getKeyType())->toBe('string')
        ->and($row->getIncrementing())->toBeFalse()
        ->and($row->timestamps)->toBeFalse()
        ->and($row->exists)->toBeFalse()
        ->and((new ReflectionClass(FeatureValueRow::class))->isFinal())->toBeTrue();
});

it('refuses to be persisted or reloaded', function (string $method): void {
    $row = FeatureValueRow::for(new StoredFeatureValue('beta', '__laravel_null', true));

    expect(fn (): mixed => $row->{$method}())->toThrow(LogicException::class);
})->with(['save', 'fresh', 'refresh', 'delete']);
