<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    // 1. Where to run
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ]);

    // 2. The "Skill Sets" (Synced to PHPStan Level 8)
    $rectorConfig->sets([
        // Upgrades code to PHP 8.4 standards (ReadOnly, Enums, Match)
        SetList::PHP_84,

        // The Heavy Lifter: Adds ": void", ": string", etc.
        // This directly solves PHPStan "Missing Return Type" errors.
        SetList::TYPE_DECLARATION,

        // Cleans up messy logic (nested ifs, etc.)
        SetList::CODE_QUALITY,

        // Deletes unused variables/methods (satisfies PHPStan "Unused Code" checks)
        SetList::DEAD_CODE,
    ]);

    // 3. Safety Skips (Crucial since we lack the Laravel Plugin)
    $rectorConfig->skip([
        // Don't try to type properties in Models, because Eloquent uses magic __get.
        // Standard Rector doesn't know this and will break your Models without this skip.
        TypedPropertyFromStrictConstructorRector::class => [
            __DIR__.'/app/Models',
        ],
    ]);
};
