<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-package-template".
 *
 * Copyright (C) 2023-2025 Elias Häußler <elias@haeussler.dev>
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

use Symfony\Component\Process;

/**
 * DummyExecutableFinder.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyExecutableFinder extends Process\ExecutableFinder
{
    /**
     * @var list<non-empty-string|null>
     */
    public array $executables = [];

    /**
     * @param array<mixed> $extraDirs
     */
    public function find(string $name, ?string $default = null, array $extraDirs = []): ?string
    {
        return array_shift($this->executables);
    }

    public function addFailingExecutable(): void
    {
        $this->executables[] = dirname(__DIR__, 2).'/Fixtures/Executables/failing.sh';
    }

    public function addSuccessfulExecutable(): void
    {
        $this->executables[] = dirname(__DIR__, 2).'/Fixtures/Executables/successful.sh';
    }

    public function addMissingExecutable(): void
    {
        $this->executables[] = null;
    }
}
