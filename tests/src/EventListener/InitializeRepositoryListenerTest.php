<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-package-template".
 *
 * Copyright (C) 2023-2026 Elias Häußler <elias@haeussler.dev>
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
use Symfony\Component\Filesystem;
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
    private IO\BufferIO $io;
    private Src\Tests\Fixtures\Classes\DummyExecutableFinder $executableFinder;
    private Filesystem\Filesystem $filesystem;
    private ProjectBuilder\Event\BuildStepProcessedEvent $event;
    private Src\EventListener\InitializeRepositoryListener $subject;

    protected function setUp(): void
    {
        $this->io = new IO\BufferIO();
        $this->executableFinder = new Src\Tests\Fixtures\Classes\DummyExecutableFinder();
        $this->filesystem = new Filesystem\Filesystem();

        $messenger = ProjectBuilder\IO\Messenger::create($this->io);
        $inputReader = $messenger->createInputReader();
        $processFactory = new Src\Resource\ProcessFactory($this->executableFinder);

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
        $instructions->addTemplateVariable('repository.createResult', [
            'object' => new Src\ValueObject\GitHubRepository(
                'foo',
                'baz',
                new Psr7\Uri('https://github.com/foo/baz'),
                'foo baz',
                false,
            ),
            'response' => Src\Enums\CreateRepositoryResponse::Created,
        ]);

        $this->event = new ProjectBuilder\Event\BuildStepProcessedEvent(
            new ProjectBuilder\Builder\Generator\Step\MirrorProcessedFilesStep(
                new ExpressionLanguage\ExpressionLanguage(),
                $this->filesystem,
                new ProjectBuilder\Twig\Renderer(
                    new Environment(
                        new Loader\ArrayLoader(),
                    ),
                    new EventDispatcher\EventDispatcher(),
                ),
                $messenger,
            ),
            new ProjectBuilder\Builder\BuildResult(
                $instructions,
            ),
            true,
        );

        $this->subject = new Src\EventListener\InitializeRepositoryListener(
            new Src\Service\GitService(
                $processFactory,
            ),
            $inputReader,
            $messenger,
        );

        $this->filesystem->mkdir($this->event->getBuildResult()->getWrittenDirectory());
        $this->executableFinder->addSuccessfulExecutable();
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
    public function invokeReturnsEarlyIfStoredCreateResultIsInvalid(): void
    {
        $instructions = $this->event->getBuildResult()->getInstructions();
        $instructions->addTemplateVariable('repository.createResult', 'foo');

        ($this->subject)($this->event);

        self::assertStringNotContainsString('Should we initialize a local Git repository?', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function invokeReturnsEarlyIfStoredCreateResponseIsFailed(): void
    {
        $instructions = $this->event->getBuildResult()->getInstructions();
        $instructions->addTemplateVariable(
            'repository.createResult.response',
            Src\Enums\CreateRepositoryResponse::Failed,
        );

        ($this->subject)($this->event);

        self::assertStringNotContainsString('Should we initialize a local Git repository?', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function invokeReturnsEarlyIfGitBinaryIsNotAvailable(): void
    {
        $this->executableFinder->executables = [null];

        ($this->subject)($this->event);

        self::assertStringNotContainsString('Should we initialize a local Git repository?', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function invokeReturnsEarlyIfUserAbortsRepositoryCreation(): void
    {
        $this->io->setUserInputs(['no']);

        ($this->subject)($this->event);

        self::assertStringContainsString('Should we initialize a local Git repository?', $this->io->getOutput());
        self::assertStringNotContainsString('Initializing local repository...', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function invokeInitializesLocalRepository(): void
    {
        $this->io->setUserInputs(['yes']);

        ($this->subject)($this->event);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Should we initialize a local Git repository?', $output);
        self::assertStringContainsString('Initializing local repository...', $output);
        self::assertNull(
            $this->event->getBuildResult()->getInstructions()->getTemplateVariable('repository.createResult'),
        );
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->event->getBuildResult()->getWrittenDirectory());
    }
}
