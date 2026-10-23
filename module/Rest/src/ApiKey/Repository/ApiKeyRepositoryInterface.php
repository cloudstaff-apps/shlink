<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Repository;

use Cake\Chronos\Chronos;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\Repository\EntityRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/**
 * @extends EntityRepositoryInterface<ApiKey>
 */
interface ApiKeyRepositoryInterface extends EntityRepositoryInterface, EntitySpecificationRepositoryInterface
{
    /**
     * Will create provided API key with admin permissions, only if no other API keys exist yet
     */
    public function createInitialApiKey(string $apiKey): ApiKey|null;

    /**
     * Checks whether an API key with provided name exists or not
     */
    public function nameExists(string $name): bool;

    /**
     * Delete an API key by name
     * @return positive-int|0 Number of affected results
     */
    public function deleteByName(string $name): int;

    /**
     * Finds an exact API key name or a legacy migrated name in the form "name - plaintext-key".
     *
     * @return ApiKey[]
     */
    public function findForProvisioning(string $name): array;

    public function updateCredentials(
        string $id,
        string $hashedKey,
        Chronos $expirationDate,
    ): void;
}
