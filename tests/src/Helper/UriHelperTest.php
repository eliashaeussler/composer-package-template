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

namespace EliasHaeussler\ComposerPackageTemplate\Tests\Helper;

use EliasHaeussler\ComposerPackageTemplate as Src;
use Nyholm\Psr7;
use PHPUnit\Framework;

/**
 * UriHelperTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Helper\UriHelper::class)]
final class UriHelperTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function mergePathMergesPathFromUriWithGivenPath(): void
    {
        $uri = new Psr7\Uri('https://www.example.com/foo/');
        $path = '/baz';

        self::assertEquals(
            new Psr7\Uri('https://www.example.com/foo/baz'),
            Src\Helper\UriHelper::mergePath($uri, $path),
        );
    }

    #[Framework\Attributes\Test]
    public function mergePathMergesPathsAndInterpolatesPathParameters(): void
    {
        $uri = new Psr7\Uri('https://www.example.com/{foo}/');
        $path = '/{baz}';
        $parameters = [
            'foo' => 'baz',
            'baz' => 'foo',
        ];

        self::assertEquals(
            new Psr7\Uri('https://www.example.com/baz/foo'),
            Src\Helper\UriHelper::mergePath($uri, $path, $parameters),
        );
    }

    #[Framework\Attributes\Test]
    public function interpolatePathParametersInterpolatesGivenParameters(): void
    {
        $path = '/{foo}/{baz}';
        $parameters = [
            'foo' => 'baz',
            'baz' => 'foo',
        ];

        self::assertSame(
            '/baz/foo',
            Src\Helper\UriHelper::interpolatePathParameters($path, $parameters),
        );
    }

    #[Framework\Attributes\Test]
    public function mergeQueryParamsMergesQueryParamsFromUriWithGivenQueryParams(): void
    {
        $uri = new Psr7\Uri('https://www.example.com/?foo=baz');
        $queryParams = ['baz' => 'foo'];

        self::assertEquals(
            new Psr7\Uri('https://www.example.com/?foo=baz&baz=foo'),
            Src\Helper\UriHelper::mergeQueryParams($uri, $queryParams),
        );
    }
}
