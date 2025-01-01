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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\Exception;

use EliasHaeussler\ComposerPackageTemplate as Src;
use PHPUnit\Framework;

/**
 * ExecutableIsMissingTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Exception\ExecutableIsMissing::class)]
final class ExecutableIsMissingTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorCreatesException(): void
    {
        $actual = new Src\Exception\ExecutableIsMissing('foo');

        self::assertSame('The executable "foo" is missing in the system.', $actual->getMessage());
        self::assertSame(1687764952, $actual->getCode());
    }
}
