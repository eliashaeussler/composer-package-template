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

namespace EliasHaeussler\ComposerPackageTemplate\EventListener;

use Composer\IO;
use CPSIT\ProjectBuilder;
use EliasHaeussler\ComposerPackageTemplate\Enums;
use EliasHaeussler\ComposerPackageTemplate\Service;
use EliasHaeussler\ComposerPackageTemplate\ValueObject;
use Nyholm\Psr7;
use Webmozart\Assert;

/**
 * CreateRepositoryListener.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class CreateRepositoryListener
{
    public function __construct(
        private readonly Service\CodeClimateService $codeClimateService,
        private readonly Service\CoverallsService $coverallsService,
        private readonly Service\GitHubService $gitHubService,
        private readonly ProjectBuilder\IO\InputReader $inputReader,
        private readonly ProjectBuilder\IO\Messenger $messenger,
    ) {}

    public function __invoke(ProjectBuilder\Event\BuildStepProcessedEvent $event): void
    {
        $buildResult = $event->getBuildResult();

        if (!($event->getStep() instanceof ProjectBuilder\Builder\Generator\Step\CollectBuildInstructionsStep)) {
            return;
        }

        $this->messenger->newLine();

        // Early return if repo should not be generated
        if (!$this->inputReader->ask('Should we create a new GitHub repository for you?')) {
            return;
        }

        // Map properties to repository object
        $repository = $this->createRepositoryFromBuildResult($buildResult);

        // Create GitHub repository
        $response = $this->createGitHubRepository($repository);

        // Store result
        $buildResult->getInstructions()->addTemplateVariable('repository.createResult', [
            'object' => $repository,
            'response' => $response,
        ]);

        // Early return if repository is not created
        if (Enums\CreateRepositoryResponse::Failed === $response) {
            return;
        }

        // Create coverage repositories
        $this->createCodeClimateRepository($buildResult, $repository);
        $this->createCoverallsRepository($buildResult, $repository);
    }

    private function createRepositoryFromBuildResult(
        ProjectBuilder\Builder\BuildResult $buildResult,
    ): ValueObject\GitHubRepository {
        $instructions = $buildResult->getInstructions();

        $owner = $instructions->getTemplateVariable('repository.owner');
        $name = $instructions->getTemplateVariable('repository.name');
        $url = $instructions->getTemplateVariable('repository.url');
        $description = $instructions->getTemplateVariable('package.description');
        $isPrivate = $this->inputReader->ask('Do you wish to keep the repository private for now?');

        Assert\Assert::string($owner);
        Assert\Assert::notEmpty($owner);
        Assert\Assert::string($name);
        Assert\Assert::notEmpty($name);
        Assert\Assert::string($url);
        Assert\Assert::string($description);

        return new ValueObject\GitHubRepository($owner, $name, new Psr7\Uri($url), $description, $isPrivate);
    }

    private function createGitHubRepository(ValueObject\GitHubRepository $repository): Enums\CreateRepositoryResponse
    {
        $this->messenger->progress('Creating new GitHub repository...', IO\IOInterface::NORMAL);

        $response = $this->gitHubService->createRepository($repository);

        match ($response) {
            Enums\CreateRepositoryResponse::Created => $this->messenger->done(),
            Enums\CreateRepositoryResponse::Failed => $this->messenger->failed(),
            Enums\CreateRepositoryResponse::AlreadyExists => $this->messenger->writeWithEmoji(
                ProjectBuilder\IO\Emoji::WhiteHeavyCheckMark->value,
                'Creating new GitHub repository... <comment>Already exists</comment>',
                true,
            ),
        };

        return $response;
    }

    private function createCodeClimateRepository(
        ProjectBuilder\Builder\BuildResult $result,
        ValueObject\GitHubRepository $repository,
    ): void {
        $isCodeClimateEnabled = (bool) $result->getInstructions()->getTemplateVariable('ci.codeclimate');

        if (!$isCodeClimateEnabled || $repository->isPrivate()) {
            return;
        }

        $this->messenger->newLine();

        if ($this->inputReader->ask('Should we initialize CodeClimate?')) {
            $this->messenger->progress('Initializing CodeClimate...', IO\IOInterface::NORMAL);
            $this->codeClimateService->addRepository($repository);
            $this->messenger->done();
            $this->messenger->newLine();
        }
    }

    private function createCoverallsRepository(
        ProjectBuilder\Builder\BuildResult $result,
        ValueObject\GitHubRepository $repository,
    ): void {
        $isCoverallsEnabled = (bool) $result->getInstructions()->getTemplateVariable('ci.coveralls');

        if (!$isCoverallsEnabled || $repository->isPrivate()) {
            return;
        }

        $this->messenger->newLine();

        if ($this->inputReader->ask('Should we initialize Coveralls?')) {
            $this->messenger->progress('Initializing Coveralls...', IO\IOInterface::NORMAL);
            $this->coverallsService->addRepository($repository);
            $this->messenger->done();
            $this->messenger->newLine();
        }
    }
}
