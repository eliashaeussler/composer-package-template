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

namespace EliasHaeussler\ComposerPackageTemplate\Helper;

use Psr\Http\Message;

use function ltrim;
use function preg_replace_callback;
use function rtrim;

/**
 * UriHelper.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class UriHelper
{
    /**
     * @param non-empty-string                          $path
     * @param array<non-empty-string, non-empty-string> $parameters
     */
    public static function mergePath(
        Message\UriInterface $uri,
        string $path,
        array $parameters = [],
    ): Message\UriInterface {
        $mergedPath = rtrim($uri->getPath(), '/').'/'.ltrim($path, '/');

        if ([] !== $parameters) {
            $mergedPath = self::interpolatePathParameters(urldecode($mergedPath), $parameters);
        }

        return $uri->withPath($mergedPath);
    }

    /**
     * @param non-empty-string                                    $path
     * @param non-empty-array<non-empty-string, non-empty-string> $parameters
     */
    public static function interpolatePathParameters(string $path, array $parameters): string
    {
        return preg_replace_callback(
            '/\{(\w+)}/',
            static fn (array $matches): string => $parameters[$matches[1]] ?? '',
            $path,
        ) ?? $path;
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public static function mergeQueryParams(Message\UriInterface $uri, array $queryParams): Message\UriInterface
    {
        parse_str($uri->getQuery(), $query);

        $mergedQueryParams = array_replace_recursive($query, $queryParams);
        $queryString = http_build_query($mergedQueryParams);

        return $uri->withQuery($queryString);
    }
}
