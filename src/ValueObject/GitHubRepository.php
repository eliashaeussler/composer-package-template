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

namespace EliasHaeussler\ComposerPackageTemplate\ValueObject;

use Psr\Http\Message;

/**
 * GitHubRepository.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class GitHubRepository
{
    /**
     * @param non-empty-string $owner
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $owner,
        private readonly string $name,
        private readonly Message\UriInterface $url,
        private readonly string $description = '',
        private readonly bool $private = false,
    ) {}

    /**
     * @return non-empty-string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): Message\UriInterface
    {
        return $this->url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }
}
