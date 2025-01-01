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
 * GitHubService.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class GitHubService
{
    private const API_VERSION = '2022-11-28';
    private const API_TOKEN_URL = 'https://github.com/settings/tokens/new?scopes=repo';

    private readonly Message\UriInterface $baseUrl;

    public function __construct(
        private readonly Client\ClientInterface $client,
        private readonly ProjectBuilder\IO\InputReader $inputReader,
        private readonly ProjectBuilder\IO\Messenger $messenger,
        private readonly Resource\ProcessFactory $processFactory,
        private readonly Resource\TokenStorage $tokenStorage,
    ) {
        $this->baseUrl = new Psr7\Uri('https://api.github.com');
    }

    public function createRepository(ValueObject\GitHubRepository $repository): Enums\CreateRepositoryResponse
    {
        if ($this->repositoryExists($repository)) {
            return Enums\CreateRepositoryResponse::AlreadyExists;
        }

        if ($this->processFactory->isExecutable('gh')) {
            return $this->createRepositoryUsingBinary($repository);
        }

        return $this->createRepositoryUsingApi($repository);
    }

    public function repositoryExists(ValueObject\GitHubRepository $repository): bool
    {
        if ($this->processFactory->isExecutable('gh')) {
            return $this->checkIfRepositoryExistsUsingBinary($repository);
        }

        return $this->checkIfRepositoryExistsUsingApi($repository);
    }

    private function createRepositoryUsingBinary(ValueObject\GitHubRepository $repository): Enums\CreateRepositoryResponse
    {
        $createProcess = $this->processFactory->create([
            'gh',
            'repo',
            'create',
            $repository->getName(),
            '--description',
            $repository->getDescription(),
            $repository->isPrivate() ? '--private' : '--public',
        ]);
        $createProcess->run();

        if ($createProcess->isSuccessful()) {
            return Enums\CreateRepositoryResponse::Created;
        }

        return Enums\CreateRepositoryResponse::Failed;
    }

    /**
     * @see https://docs.github.com/en/rest/repos/repos#create-a-repository-for-the-authenticated-user
     */
    private function createRepositoryUsingApi(ValueObject\GitHubRepository $repository): Enums\CreateRepositoryResponse
    {
        $response = $this->sendPostRequest(
            '/user/repos',
            [
                'name' => $repository->getName(),
                'description' => $repository->getDescription(),
                'private' => $repository->isPrivate(),
            ],
        );

        return match ($response->getStatusCode()) {
            201 => Enums\CreateRepositoryResponse::Created,
            304 => Enums\CreateRepositoryResponse::AlreadyExists,
            default => Enums\CreateRepositoryResponse::Failed,
        };
    }

    private function checkIfRepositoryExistsUsingBinary(ValueObject\GitHubRepository $repository): bool
    {
        $viewProcess = $this->processFactory->create([
            'gh',
            'repo',
            'view',
            $repository->getName(),
        ]);
        $viewProcess->run();

        return $viewProcess->isSuccessful();
    }

    private function checkIfRepositoryExistsUsingApi(ValueObject\GitHubRepository $repository): bool
    {
        $response = $this->sendGetRequest(
            '/repos/{owner}/{repo}',
            [
                'owner' => $repository->getOwner(),
                'repo' => $repository->getName(),
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
     * @param non-empty-string                          $path
     * @param array<non-empty-string, non-empty-string> $parameters
     */
    private function sendGetRequest(string $path, array $parameters = []): Message\ResponseInterface
    {
        $request = new Psr7\Request(
            'GET',
            Helper\UriHelper::mergePath($this->baseUrl, $path, $parameters),
            $this->getRequestHeaders(),
        );

        return $this->client->sendRequest($request);
    }

    /**
     * @return array{
     *     Accept: non-empty-string,
     *     Authorization: non-empty-string,
     *     X-GitHub-Api-Version: non-empty-string,
     * }
     */
    private function getRequestHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github+json',
            // see https://docs.github.com/en/rest/overview/authenticating-to-the-rest-api#authenticating-with-a-personal-access-token
            'Authorization' => 'Bearer '.$this->getAccessToken(),
            'X-GitHub-Api-Version' => self::API_VERSION,
        ];
    }

    private function getAccessToken(): string
    {
        $token = $this->tokenStorage->get(Enums\TokenIdentifier::GitHub);

        if (null !== $token) {
            return $token;
        }

        $this->messenger->write([
            'Requests to GitHub API must be authorized by an access token.',
            'When creating a private repository, the token must have scope <comment>repo</comment>, otherwise <comment>public_repo</comment>.',
            sprintf('Please create your token at <href=%1$s>%1$s</>.', self::API_TOKEN_URL),
        ]);
        $this->messenger->newLine();

        $token = $this->inputReader->staticValue(
            'Please insert your access token',
            required: true,
        );

        $this->tokenStorage->set(Enums\TokenIdentifier::GitHub, $token);

        return $token;
    }
}
