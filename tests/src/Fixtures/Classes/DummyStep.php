<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-package-template".
 *
 * Copyright (C) 2023-2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\Fixtures\Classes;

use CPSIT\ProjectBuilder\Builder;

/**
 * DummyStep.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyStep implements Builder\Generator\Step\StepInterface
{
    public function run(Builder\BuildResult $buildResult): bool
    {
        return true;
    }

    public function revert(Builder\BuildResult $buildResult): void
    {
        // Intentionally left blank.
    }

    public function setConfig(Builder\Config\ValueObject\Step $config): void
    {
        // Intentionally left blank.
    }

    public function getConfig(): Builder\Config\ValueObject\Step
    {
        return new Builder\Config\ValueObject\Step('dummy');
    }

    public static function getType(): string
    {
        return 'dummy';
    }

    public static function supports(string $type): bool
    {
        return false;
    }
}
