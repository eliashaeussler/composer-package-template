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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\Resource;

use EliasHaeussler\ComposerPackageTemplate as Src;
use PHPUnit\Framework;
use ReflectionObject;

/**
 * TokenStorageTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Resource\TokenStorage::class)]
final class TokenStorageTest extends Framework\TestCase
{
    private Src\Resource\TokenStorage $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Resource\TokenStorage();
    }

    #[Framework\Attributes\Test]
    public function getReturnsCachedToken(): void
    {
        $this->subject->set(Src\Enums\TokenIdentifier::GitHub, 'foo');

        self::assertSame('foo', $this->subject->get(Src\Enums\TokenIdentifier::GitHub));
    }

    #[Framework\Attributes\Test]
    public function getReturnsTokenFromEnvironmentVariable(): void
    {
        putenv('GITHUB_TOKEN=foo');

        self::assertSame('foo', $this->subject->get(Src\Enums\TokenIdentifier::GitHub));

        putenv('GITHUB_TOKEN');
    }

    #[Framework\Attributes\Test]
    public function getIgnoresEmptyEnvironmentVariable(): void
    {
        putenv('GITHUB_TOKEN=');

        self::assertNull($this->subject->get(Src\Enums\TokenIdentifier::GitHub));

        putenv('GITHUB_TOKEN');
    }

    #[Framework\Attributes\Test]
    public function getReturnsNullIfTokenIsNotStored(): void
    {
        self::assertNull($this->subject->get(Src\Enums\TokenIdentifier::GitHub));
    }

    #[Framework\Attributes\Test]
    public function hasReturnsTrueIfTokenIsStored(): void
    {
        self::assertFalse($this->subject->has(Src\Enums\TokenIdentifier::GitHub));

        $this->subject->set(Src\Enums\TokenIdentifier::GitHub, 'foo');

        self::assertTrue($this->subject->has(Src\Enums\TokenIdentifier::GitHub));
    }

    #[Framework\Attributes\Test]
    public function setStoresToken(): void
    {
        self::assertNull($this->subject->get(Src\Enums\TokenIdentifier::GitHub));

        $this->subject->set(Src\Enums\TokenIdentifier::GitHub, 'foo');

        self::assertSame('foo', $this->subject->get(Src\Enums\TokenIdentifier::GitHub));
    }

    protected function tearDown(): void
    {
        $this->resetTokenStorage();
    }

    private function resetTokenStorage(): void
    {
        $reflectionObject = new ReflectionObject($this->subject);
        $reflectionObject->setStaticPropertyValue('storage', []);
    }
}
