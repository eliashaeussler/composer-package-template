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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\EventListener;

use Composer\IO;
use CPSIT\ProjectBuilder;
use EliasHaeussler\ComposerPackageTemplate as Src;
use Nyholm\Psr7;
use PHPUnit\Framework;
use Symfony\Component\EventDispatcher;
use Symfony\Component\ExpressionLanguage;
use Twig\Environment;
use Twig\Loader;

/**
 * InitializeRepositoryListenerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\EventListener\InitializeRepositoryListener::class)]
final class InitializeRepositoryListenerTest extends Framework\TestCase
{
    use Src\Tests\ClientMockTrait;

    private IO\BufferIO $io;
    private Src\Resource\TokenStorage $tokenStorage;
    private ProjectBuilder\Event\BuildStepProcessedEvent $event;
    private Src\EventListener\InitializeRepositoryListener $subject;

    protected function setUp(): void
    {
        $this->io = new IO\BufferIO();

        $client = $this->getPreparedClient();
        $messenger = ProjectBuilder\IO\Messenger::create($this->io);
        $inputReader = $messenger->createInputReader();

        $instructions = new ProjectBuilder\Builder\BuildInstructions(
            new ProjectBuilder\Builder\Config\Config(
                'foo',
                'baz',
                [
                    new ProjectBuilder\Builder\Config\ValueObject\Step('dummy'),
                ],
                [],
            ),
            'foo',
        );
        $instructions->addTemplateVariable('repository.owner', 'foo');
        $instructions->addTemplateVariable('repository.name', 'baz');
        $instructions->addTemplateVariable('repository.url', 'https://github.com/foo/baz');
        $instructions->addTemplateVariable('package.description', 'foo baz');
        $instructions->addTemplateVariable('ci.codeclimate', true);
        $instructions->addTemplateVariable('ci.coveralls', true);

        $this->tokenStorage = new Src\Resource\TokenStorage();
        $this->event = new ProjectBuilder\Event\BuildStepProcessedEvent(
            new ProjectBuilder\Builder\Generator\Step\CollectBuildInstructionsStep(
                new ExpressionLanguage\ExpressionLanguage(),
                $messenger,
                new ProjectBuilder\Builder\Generator\Step\Interaction\InteractionFactory([]),
                new ProjectBuilder\Twig\Renderer(
                    new Environment(
                        new Loader\ArrayLoader(),
                    ),
                    new EventDispatcher\EventDispatcher(),
                ),
            ),
            new ProjectBuilder\Builder\BuildResult(
                $instructions,
            ),
            true,
        );

        $this->subject = new Src\EventListener\InitializeRepositoryListener(
            new Src\Service\CodeClimateService(
                $client,
                $inputReader,
                $messenger,
                $this->tokenStorage,
            ),
            new Src\Service\CoverallsService(
                $client,
                $inputReader,
                $messenger,
                $this->tokenStorage,
            ),
            new Src\Service\GitHubService(
                $client,
                $inputReader,
                $messenger,
                new Src\Resource\ProcessFactory(
                    new Src\Tests\Fixtures\Classes\DummyExecutableFinder(),
                ),
                $this->tokenStorage,
            ),
            $inputReader,
            $messenger,
        );
    }

    #[Framework\Attributes\Test]
    public function invokeDoesNothingIfGivenStepIsNotSupported(): void
    {
        $event = new ProjectBuilder\Event\BuildStepProcessedEvent(
            new Src\Tests\Fixtures\Classes\DummyStep(),
            $this->event->getBuildResult(),
            $this->event->isSuccessful(),
        );

        ($this->subject)($event);

        self::assertEmpty($this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function invokeReturnsEarlyIfUserAbortsRepositoryCreation(): void
    {
        $this->io->setUserInputs(['no']);

        ($this->subject)($this->event);

        self::assertStringNotContainsString('Creating new GitHub repository', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function invokeCreatesGitHubRepositoryAndSkipsCoverageRepositoriesIfGitHubRequestFails(): void
    {
        $this->io->setUserInputs(['yes', 'yes']);

        $this->tokenStorage->set(Src\Enums\TokenIdentifier::GitHub, 'foo');
        $this->mockHandler->append(new Psr7\Response(404), new Psr7\Response(400));

        ($this->subject)($this->event);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Do you wish to keep the repository private for now?', $output);
        self::assertStringContainsString('Creating new GitHub repository...', $output);
        self::assertStringNotContainsString('Should we initialize CodeClimate?', $output);
        self::assertStringNotContainsString('Should we initialize Coveralls?', $output);
    }

    #[Framework\Attributes\Test]
    public function invokeCreatesGitHubRepositoryAndSkipsCoverageRepositoriesOnPrivateRepository(): void
    {
        $this->io->setUserInputs(['yes', 'yes', 'yes']);

        $this->tokenStorage->set(Src\Enums\TokenIdentifier::GitHub, 'foo');
        $this->mockHandler->append(new Psr7\Response(200));

        ($this->subject)($this->event);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Do you wish to keep the repository private for now?', $output);
        self::assertStringContainsString('Creating new GitHub repository...', $output);
        self::assertStringNotContainsString('Should we initialize CodeClimate?', $output);
        self::assertStringNotContainsString('Should we initialize Coveralls?', $output);
    }

    #[Framework\Attributes\Test]
    public function invokeCreatesGitHubRepositoryAndSkipsCodeClimateRepositoryIfCodeClimateIsDisabled(): void
    {
        $this->io->setUserInputs(['yes', 'yes']);

        $instructions = $this->event->getBuildResult()->getInstructions();
        $instructions->addTemplateVariable('ci.codeclimate', false);
        $instructions->addTemplateVariable('ci.coveralls', false);

        $this->tokenStorage->set(Src\Enums\TokenIdentifier::GitHub, 'foo');
        $this->mockHandler->append(new Psr7\Response(200));

        ($this->subject)($this->event);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Do you wish to keep the repository private for now?', $output);
        self::assertStringContainsString('Creating new GitHub repository...', $output);
        self::assertStringNotContainsString('Should we initialize CodeClimate?', $output);
    }

    #[Framework\Attributes\Test]
    public function invokeCreatesGitHubRepositoryAndAddsCodeClimateRepository(): void
    {
        $this->io->setUserInputs(['yes', 'no']);

        $instructions = $this->event->getBuildResult()->getInstructions();
        $instructions->addTemplateVariable('ci.coveralls', false);

        $this->tokenStorage->set(Src\Enums\TokenIdentifier::GitHub, 'foo');
        $this->tokenStorage->set(Src\Enums\TokenIdentifier::CodeClimate, 'foo');
        $this->mockHandler->append(new Psr7\Response(200), new Psr7\Response(200));

        ($this->subject)($this->event);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Do you wish to keep the repository private for now?', $output);
        self::assertStringContainsString('Creating new GitHub repository...', $output);
        self::assertStringContainsString('Should we initialize CodeClimate?', $output);
        self::assertStringContainsString('Initializing CodeClimate...', $output);
    }

    #[Framework\Attributes\Test]
    public function invokeCreatesGitHubRepositoryAndSkipsCoverallsRepositoryIfCoverallsIsDisabled(): void
    {
        $this->io->setUserInputs(['yes', 'yes']);

        $instructions = $this->event->getBuildResult()->getInstructions();
        $instructions->addTemplateVariable('ci.codeclimate', false);
        $instructions->addTemplateVariable('ci.coveralls', false);

        $this->tokenStorage->set(Src\Enums\TokenIdentifier::GitHub, 'foo');
        $this->mockHandler->append(new Psr7\Response(200));

        ($this->subject)($this->event);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Do you wish to keep the repository private for now?', $output);
        self::assertStringContainsString('Creating new GitHub repository...', $output);
        self::assertStringNotContainsString('Should we initialize Coveralls?', $output);
    }

    #[Framework\Attributes\Test]
    public function invokeCreatesGitHubRepositoryAndAddsCoverallsRepository(): void
    {
        $this->io->setUserInputs(['yes', 'no']);

        $instructions = $this->event->getBuildResult()->getInstructions();
        $instructions->addTemplateVariable('ci.codeclimate', false);

        $this->tokenStorage->set(Src\Enums\TokenIdentifier::GitHub, 'foo');
        $this->tokenStorage->set(Src\Enums\TokenIdentifier::Coveralls, 'foo');
        $this->mockHandler->append(new Psr7\Response(200), new Psr7\Response(200));

        ($this->subject)($this->event);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Do you wish to keep the repository private for now?', $output);
        self::assertStringContainsString('Creating new GitHub repository...', $output);
        self::assertStringContainsString('Should we initialize Coveralls?', $output);
        self::assertStringContainsString('Initializing Coveralls...', $output);
    }
}
