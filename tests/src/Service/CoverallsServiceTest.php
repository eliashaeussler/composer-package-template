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

use Composer\IO;
use CPSIT\ProjectBuilder;
use EliasHaeussler\ComposerPackageTemplate as Src;
use EliasHaeussler\ComposerPackageTemplate\Tests;
use Generator;
use Nyholm\Psr7;
use PHPUnit\Framework;
use ReflectionObject;

/**
 * CoverallsServiceTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Service\CoverallsService::class)]
final class CoverallsServiceTest extends Framework\TestCase
{
    use Tests\ClientMockTrait;

    private IO\BufferIO $io;
    private Src\Resource\TokenStorage $tokenStorage;
    private Src\ValueObject\GitHubRepository $repository;
    private Src\Service\CoverallsService $subject;

    protected function setUp(): void
    {
        $this->io = new IO\BufferIO();
        $this->tokenStorage = new Src\Resource\TokenStorage();
        $this->repository = new Src\ValueObject\GitHubRepository(
            'foo',
            'baz',
            new Psr7\Uri('https://github.com/foo/baz'),
        );

        $messenger = ProjectBuilder\IO\Messenger::create($this->io);

        $this->subject = new Src\Service\CoverallsService(
            $this->getPreparedClient(),
            $messenger->createInputReader(),
            $messenger,
            $this->tokenStorage,
        );
    }

    #[Framework\Attributes\Test]
    public function addRepositoryReturnsAlreadyExistsResponseIfRepositoryAlreadyExists(): void
    {
        $this->tokenStorage->set(Src\Enums\TokenIdentifier::Coveralls, 'foo');
        $this->mockHandler->append(new Psr7\Response(200));

        self::assertSame(
            Src\Enums\CreateRepositoryResponse::AlreadyExists,
            $this->subject->addRepository($this->repository),
        );
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('addRepositoryDataProvider')]
    public function addRepositoryAddsRepository(
        bool $successful,
        Src\Enums\CreateRepositoryResponse $expected,
    ): void {
        // Check for repository existence
        $this->tokenStorage->set(Src\Enums\TokenIdentifier::Coveralls, 'foo');
        $this->mockHandler->append(new Psr7\Response(404));

        // Mock repository creation
        if ($successful) {
            $this->mockHandler->append(new Psr7\Response(201));
        } else {
            $this->mockHandler->append(new Psr7\Response(400));
        }

        self::assertSame($expected, $this->subject->addRepository($this->repository));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('repositoryExistsDataProvider')]
    public function repositoryExistsChecksRepositoryExistenceUsingApi(bool $successful, bool $expected): void
    {
        $this->tokenStorage->set(Src\Enums\TokenIdentifier::Coveralls, 'foo');

        if ($successful) {
            $this->mockHandler->append(new Psr7\Response(200));
        } else {
            $this->mockHandler->append(new Psr7\Response(404));
        }

        self::assertSame($expected, $this->subject->repositoryExists($this->repository));
    }

    #[Framework\Attributes\Test]
    public function apiRequestsRequireAccessTokenFromUserInput(): void
    {
        $this->io->setUserInputs(['foo']);

        $this->mockHandler->append(new Psr7\Response());

        self::assertNull($this->tokenStorage->get(Src\Enums\TokenIdentifier::Coveralls));

        $this->subject->repositoryExists($this->repository);

        self::assertStringContainsString('Please insert your access token', $this->io->getOutput());
        self::assertSame('foo', $this->tokenStorage->get(Src\Enums\TokenIdentifier::Coveralls));
    }

    /**
     * @return Generator<string, array{bool, Src\Enums\CreateRepositoryResponse}>
     */
    public static function addRepositoryDataProvider(): Generator
    {
        yield 'successful' => [true, Src\Enums\CreateRepositoryResponse::Created];
        yield 'failed' => [false, Src\Enums\CreateRepositoryResponse::Failed];
    }

    /**
     * @return Generator<string, array{bool, bool}>
     */
    public static function repositoryExistsDataProvider(): Generator
    {
        yield 'successful' => [true, true];
        yield 'failed' => [false, false];
    }

    protected function tearDown(): void
    {
        $this->resetTokenStorage();
    }

    private function resetTokenStorage(): void
    {
        $reflectionObject = new ReflectionObject($this->tokenStorage);
        $reflectionObject->setStaticPropertyValue('storage', []);
    }
}
