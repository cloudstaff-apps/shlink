<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\CLI\ApiKey\RoleResolverInterface;
use Shlinkio\Shlink\CLI\Command\Api\Input\ApiKeyInput;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Shlinkio\Shlink\Common\normalizeOptionalDate;
use function sprintf;

#[AsCommand(
    name: ProvisionApiKeyCommand::NAME,
    description: 'Provisions an API key while preserving access to existing authored short URLs',
)]
class ProvisionApiKeyCommand extends Command
{
    public const string NAME = 'api-key:provision';

    public function __construct(
        private readonly ApiKeyServiceInterface $apiKeyService,
        private readonly RoleResolverInterface $roleResolver,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('The unique user name or email for the API key', shortcut: 'm')]
        string|null $name = null,
        #[Option('The expiration date in YYYY-MM-DD format', shortcut: 'e')]
        string|null $expirationDate = null,
        #[Option('Optionally restrict newly created keys to this domain', name: 'domain-only', shortcut: 'd')]
        string|null $domain = null,
    ): int {
        if ($name === null || $name === '') {
            $io->error('The --name option is required.');
            return Command::INVALID;
        }
        if ($expirationDate === null || $expirationDate === '') {
            $io->error('The --expiration-date option is required.');
            return Command::INVALID;
        }

        $roleInput = new ApiKeyInput();
        $roleInput->authorOnly = true;
        $roleInput->domain = $domain;
        $apiKeyMeta = ApiKeyMeta::fromParams(
            name: $name,
            expirationDate: normalizeOptionalDate($expirationDate),
            roleDefinitions: $this->roleResolver->determineRoles($roleInput),
        );
        $apiKey = $this->apiKeyService->provision($apiKeyMeta);

        $io->success(sprintf('Generated API key: "%s"', $apiKey));
        return Command::SUCCESS;
    }
}
