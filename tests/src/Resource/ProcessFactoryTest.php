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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\Resource;

use EliasHaeussler\ComposerPackageTemplate as Src;
use EliasHaeussler\ComposerPackageTemplate\Tests;
use PHPUnit\Framework;
use Symfony\Component\Process;

/**
 * ProcessFactoryTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Resource\ProcessFactory::class)]
final class ProcessFactoryTest extends Framework\TestCase
{
    private Tests\Fixtures\Classes\DummyExecutableFinder $executableFinder;
    private Src\Resource\ProcessFactory $subject;

    protected function setUp(): void
    {
        $this->executableFinder = new Tests\Fixtures\Classes\DummyExecutableFinder();
        $this->subject = new Src\Resource\ProcessFactory($this->executableFinder);
    }

    #[Framework\Attributes\Test]
    public function createThrowsExceptionIfExecutableIsEmpty(): void
    {
        $this->expectExceptionObject(new Src\Exception\ExecutableIsMissing(''));

        $this->subject->create(['']);
    }

    #[Framework\Attributes\Test]
    public function createThrowsExceptionIfExecutableIsMissing(): void
    {
        $this->expectExceptionObject(new Src\Exception\ExecutableIsMissing('foo'));

        $this->subject->create(['foo', 'baz']);
    }

    #[Framework\Attributes\Test]
    public function createReturnsProcessForGivenCommand(): void
    {
        $this->executableFinder->executables = ['/foo'];

        self::assertEquals(
            new Process\Process(['/foo', 'baz']),
            $this->subject->create(['foo', 'baz']),
        );
    }

    #[Framework\Attributes\Test]
    public function isExecutableReturnsTrueIfBinaryExists(): void
    {
        self::assertFalse($this->subject->isExecutable('foo'));

        $this->executableFinder->executables = ['foo'];

        self::assertTrue($this->subject->isExecutable('foo'));
    }
}
