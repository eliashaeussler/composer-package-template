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

namespace EliasHaeussler\ComposerPackageTemplate\Resource;

use EliasHaeussler\ComposerPackageTemplate\Exception;
use Symfony\Component\Process;

use function array_shift;
use function array_unshift;

/**
 * ProcessFactory.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class ProcessFactory
{
    /**
     * @var array<non-empty-string, string|null>
     */
    private array $executables = [];

    public function __construct(
        private readonly Process\ExecutableFinder $executableFinder,
    ) {}

    /**
     * @param non-empty-list<string> $command
     *
     * @throws Exception\ExecutableIsMissing
     */
    public function create(array $command): Process\Process
    {
        $name = array_shift($command);

        if ('' === $name || !$this->isExecutable($name)) {
            throw new Exception\ExecutableIsMissing($name);
        }

        array_unshift($command, $this->findExecutable($name));

        return new Process\Process($command);
    }

    /**
     * @param non-empty-string $name
     */
    public function isExecutable(string $name): bool
    {
        return null !== $this->findExecutable($name);
    }

    /**
     * @param non-empty-string $name
     */
    private function findExecutable(string $name): ?string
    {
        if (!isset($this->executables[$name])) {
            $this->executables[$name] = $this->executableFinder->find($name);
        }

        return $this->executables[$name];
    }
}
