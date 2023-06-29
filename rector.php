<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-package-template".
 *
 * Copyright (C) 2023 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use EliasHaeussler\RectorConfig\Config\Config;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Privatization\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector;

return static function (RectorConfig $rectorConfig): void {
    Config::create($rectorConfig, PhpVersion::PHP_81)
        ->in(
            __DIR__.'/src',
            __DIR__.'/tests/src',
        )
        ->withPHPUnit()
        ->skip(ChangeReadOnlyVariableWithDefaultValueToConstantRector::class, [
            __DIR__.'/tests/src/Helper/UriHelperTest.php',
        ])
        ->apply()
    ;
};
