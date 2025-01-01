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

namespace EliasHaeussler\ComposerPackageTemplate\EventListener;

use Composer\IO;
use CPSIT\ProjectBuilder;
use EliasHaeussler\ComposerPackageTemplate\Enums;
use EliasHaeussler\ComposerPackageTemplate\Service;
use EliasHaeussler\ComposerPackageTemplate\ValueObject;

/**
 * InitializeRepositoryListener.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class InitializeRepositoryListener
{
    public function __construct(
        private readonly Service\GitService $gitService,
        private readonly ProjectBuilder\IO\InputReader $inputReader,
        private readonly ProjectBuilder\IO\Messenger $messenger,
    ) {}

    public function __invoke(ProjectBuilder\Event\BuildStepProcessedEvent $event): void
    {
        $buildResult = $event->getBuildResult();

        if (!($event->getStep() instanceof ProjectBuilder\Builder\Generator\Step\MirrorProcessedFilesStep)) {
            return;
        }

        // Get repository data
        $repository = $buildResult->getInstructions()->getTemplateVariable('repository.createResult.object');
        $response = $buildResult->getInstructions()->getTemplateVariable('repository.createResult.response');

        if (!($repository instanceof ValueObject\GitHubRepository)
            || !($response instanceof Enums\CreateRepositoryResponse)
            || Enums\CreateRepositoryResponse::Failed === $response
            || !$this->gitService->isAvailable()
        ) {
            return;
        }

        // Remove repository data (since it's only necessary during runtime)
        $buildResult->getInstructions()->addTemplateVariable('repository.createResult', null);

        $this->messenger->newLine();

        if ($this->inputReader->ask('Should we initialize a local Git repository?')) {
            $this->messenger->progress('Initializing local repository...', IO\IOInterface::NORMAL);
            $this->gitService->initializeRepository($repository, $buildResult->getWrittenDirectory());
            $this->messenger->done();
        }
    }
}
