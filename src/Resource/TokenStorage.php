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

namespace EliasHaeussler\ComposerPackageTemplate\Resource;

use EliasHaeussler\ComposerPackageTemplate\Enums;

use function getenv;
use function strtoupper;

/**
 * TokenStorage.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class TokenStorage
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private static array $storage = [];

    /**
     * @return non-empty-string|null
     */
    public function get(Enums\TokenIdentifier $identifier): ?string
    {
        if ($this->has($identifier)) {
            return self::$storage[$identifier->value];
        }

        $envVariable = strtoupper($identifier->value.'_token');
        $token = getenv($envVariable);

        if (false !== $token && '' !== $token) {
            $this->set($identifier, $token);

            return self::$storage[$identifier->value];
        }

        return null;
    }

    public function has(Enums\TokenIdentifier $identifier): bool
    {
        return isset(self::$storage[$identifier->value]);
    }

    /**
     * @param non-empty-string $token
     */
    public function set(Enums\TokenIdentifier $identifier, string $token): void
    {
        self::$storage[$identifier->value] = $token;
    }
}
