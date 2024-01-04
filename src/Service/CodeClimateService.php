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

namespace EliasHaeussler\ComposerPackageTemplate\Service;

use CPSIT\ProjectBuilder;
use EliasHaeussler\ComposerPackageTemplate\Enums;
use EliasHaeussler\ComposerPackageTemplate\Helper;
use EliasHaeussler\ComposerPackageTemplate\Resource;
use EliasHaeussler\ComposerPackageTemplate\ValueObject;
use Nyholm\Psr7;
use Psr\Http\Client;
use Psr\Http\Message;

use function json_encode;
use function sprintf;

/**
 * CodeClimateService.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class CodeClimateService
{
    private const API_TOKEN_URL = 'https://codeclimate.com/profile/tokens';

    private readonly Message\UriInterface $baseUrl;

    public function __construct(
        private readonly Client\ClientInterface $client,
        private readonly ProjectBuilder\IO\InputReader $inputReader,
        private readonly ProjectBuilder\IO\Messenger $messenger,
        private readonly Resource\TokenStorage $tokenStorage,
    ) {
        $this->baseUrl = new Psr7\Uri('https://api.codeclimate.com/v1');
    }

    /**
     * @see https://developer.codeclimate.com/#add-public-oss-repository
     */
    public function addRepository(ValueObject\GitHubRepository $repository): Enums\CreateRepositoryResponse
    {
        if ($this->repositoryExists($repository)) {
            return Enums\CreateRepositoryResponse::AlreadyExists;
        }

        $response = $this->sendPostRequest(
            '/github/repos',
            [
                'data' => [
                    'type' => 'repos',
                    'attributes' => [
                        'url' => (string) $repository->getUrl(),
                    ],
                ],
            ],
        );

        if (201 === $response->getStatusCode()) {
            return Enums\CreateRepositoryResponse::Created;
        }

        return Enums\CreateRepositoryResponse::Failed;
    }

    public function repositoryExists(ValueObject\GitHubRepository $repository): bool
    {
        $response = $this->sendGetRequest(
            '/repos',
            [
                'github_slug' => $repository->getOwner().'/'.$repository->getName(),
            ],
        );

        return 200 === $response->getStatusCode();
    }

    /**
     * @param non-empty-string     $path
     * @param array<string, mixed> $json
     */
    private function sendPostRequest(string $path, array $json): Message\ResponseInterface
    {
        $request = new Psr7\Request(
            'POST',
            Helper\UriHelper::mergePath($this->baseUrl, $path),
            $this->getRequestHeaders(),
        );
        $request->getBody()->write(json_encode($json, JSON_THROW_ON_ERROR));
        $request->getBody()->rewind();

        return $this->client->sendRequest($request);
    }

    /**
     * @param non-empty-string     $path
     * @param array<string, mixed> $queryParams
     */
    private function sendGetRequest(string $path, array $queryParams = []): Message\ResponseInterface
    {
        $request = new Psr7\Request(
            'GET',
            Helper\UriHelper::mergePath(
                Helper\UriHelper::mergeQueryParams($this->baseUrl, $queryParams),
                $path,
            ),
            $this->getRequestHeaders(),
        );

        return $this->client->sendRequest($request);
    }

    /**
     * @return array{
     *     Accept: non-empty-string,
     *     Authorization: non-empty-string,
     *     Content-Type: non-empty-string,
     * }
     */
    private function getRequestHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.api+json',
            // see https://developer.codeclimate.com/#authentication
            'Authorization' => 'Token token='.$this->getAccessToken(),
            'Content-Type' => 'application/vnd.api+json',
        ];
    }

    private function getAccessToken(): string
    {
        $token = $this->tokenStorage->get(Enums\TokenIdentifier::CodeClimate);

        if (null !== $token) {
            return $token;
        }

        $this->messenger->write([
            'Requests to CodeClimate API must be authorized by an access token.',
            sprintf('Please create your token at <href=%1$s>%1$s</>.', self::API_TOKEN_URL),
        ]);
        $this->messenger->newLine();

        $token = $this->inputReader->staticValue(
            'Please insert your access token',
            required: true,
        );

        $this->tokenStorage->set(Enums\TokenIdentifier::CodeClimate, $token);

        return $token;
    }
}
