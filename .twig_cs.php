<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/composer-package-template".
 *
 * Copyright (C) 2023 Elias Häußler <elias@haeussler.dev>
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

use FriendsOfTwig\Twigcs;

$finder = Twigcs\Finder\TemplateFinder::create()
    ->in(__DIR__.'/templates')
    // Regex hack as long as https://github.com/symfony/symfony/issues/42675 is not resolved
    // Otherwise, dotfiles won't be included
    ->name('/\\.twig$/')
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
;

return Twigcs\Config\Config::create()
    ->setFinder($finder)
;
