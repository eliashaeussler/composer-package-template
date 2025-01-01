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

namespace EliasHaeussler\ComposerPackageTemplate\Service;

use EliasHaeussler\ComposerPackageTemplate\Enums;
use EliasHaeussler\ComposerPackageTemplate\Resource;
use EliasHaeussler\ComposerPackageTemplate\ValueObject;

use function sprintf;
use function trim;

/**
 * GitService.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class GitService
{
    public function __construct(
        private readonly Resource\ProcessFactory $processFactory,
    ) {}

    public function initializeRepository(
        ValueObject\GitHubRepository $repository,
        string $directory,
    ): Enums\InitializeRepositoryResponse {
        // Initialize repository
        $initProcess = $this->processFactory->create(['git', 'init', '--initial-branch', 'main']);
        $initProcess->setWorkingDirectory($directory);
        $initProcess->run();

        // Early return if repository could not be initialized
        if (!$initProcess->isSuccessful()) {
            return Enums\InitializeRepositoryResponse::Failed;
        }

        // Add remote repository
        $remoteUrl = sprintf(
            'git@%s:%s.git',
            $repository->getUrl()->getHost(),
            trim($repository->getUrl()->getPath(), '/'),
        );
        $remoteProcess = $this->processFactory->create([
            'git',
            'remote',
            'add',
            'origin',
            $remoteUrl,
        ]);
        $remoteProcess->setWorkingDirectory($directory);
        $remoteProcess->run();

        if ($remoteProcess->isSuccessful()) {
            return Enums\InitializeRepositoryResponse::Initialized;
        }

        return Enums\InitializeRepositoryResponse::Failed;
    }

    public function isAvailable(): bool
    {
        return $this->processFactory->isExecutable('git');
    }
}
