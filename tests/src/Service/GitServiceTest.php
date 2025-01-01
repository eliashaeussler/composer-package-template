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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\Service;

use EliasHaeussler\ComposerPackageTemplate as Src;
use EliasHaeussler\ComposerPackageTemplate\Tests;
use Nyholm\Psr7;
use PHPUnit\Framework;

/**
 * GitServiceTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Service\GitService::class)]
final class GitServiceTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyExecutableFinder $executableFinder;
    private Src\ValueObject\GitHubRepository $repository;
    private Src\Service\GitService $subject;

    protected function setUp(): void
    {
        $this->executableFinder = new Tests\Fixtures\Classes\DummyExecutableFinder();
        $this->repository = new Src\ValueObject\GitHubRepository(
            'foo',
            'baz',
            new Psr7\Uri('https://github.com/foo/baz'),
        );

        $this->subject = new Src\Service\GitService(
            new Src\Resource\ProcessFactory($this->executableFinder),
        );
    }

    #[Framework\Attributes\Test]
    public function initializeRepositoryReturnsFailedResponseIfGitCommandFails(): void
    {
        $this->executableFinder->addFailingExecutable();

        self::assertSame(
            Src\Enums\InitializeRepositoryResponse::Failed,
            $this->subject->initializeRepository($this->repository, __DIR__),
        );
        self::assertSame([], $this->executableFinder->executables);
    }

    #[Framework\Attributes\Test]
    public function initializeRepositoryInitializesLocalRepository(): void
    {
        $this->executableFinder->addSuccessfulExecutable();

        self::assertSame(
            Src\Enums\InitializeRepositoryResponse::Initialized,
            $this->subject->initializeRepository($this->repository, __DIR__),
        );
        self::assertSame([], $this->executableFinder->executables);
    }

    #[Framework\Attributes\Test]
    public function isAvailableReturnsTrueIfGitBinaryIsExecutable(): void
    {
        $this->executableFinder->addSuccessfulExecutable();

        self::assertTrue($this->subject->isAvailable());
    }

    #[Framework\Attributes\Test]
    public function isAvailableReturnsFalseIfGitBinaryIsNotExecutable(): void
    {
        $this->executableFinder->addMissingExecutable();

        self::assertFalse($this->subject->isAvailable());
    }
}
