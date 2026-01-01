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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\ValueObject;

use EliasHaeussler\ComposerPackageTemplate as Src;
use Nyholm\Psr7;
use PHPUnit\Framework;

/**
 * GitHubRepositoryTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ValueObject\GitHubRepository::class)]
final class GitHubRepositoryTest extends Framework\TestCase
{
    private Src\ValueObject\GitHubRepository $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\ValueObject\GitHubRepository(
            'foo',
            'baz',
            new Psr7\Uri('https://github.com/foo/baz'),
            'foo baz',
            true,
        );
    }

    #[Framework\Attributes\Test]
    public function getOwnerReturnsOwner(): void
    {
        self::assertSame('foo', $this->subject->getOwner());
    }

    #[Framework\Attributes\Test]
    public function getNameReturnsName(): void
    {
        self::assertSame('baz', $this->subject->getName());
    }

    #[Framework\Attributes\Test]
    public function getUrlReturnsUrl(): void
    {
        self::assertEquals(new Psr7\Uri('https://github.com/foo/baz'), $this->subject->getUrl());
    }

    #[Framework\Attributes\Test]
    public function getDescriptionReturnsDescription(): void
    {
        self::assertSame('foo baz', $this->subject->getDescription());
    }

    #[Framework\Attributes\Test]
    public function isPrivateReturnsTrueIfRepositoryIsPrivate(): void
    {
        self::assertTrue($this->subject->isPrivate());
    }
}
