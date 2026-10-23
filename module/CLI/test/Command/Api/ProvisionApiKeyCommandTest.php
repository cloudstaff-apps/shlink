<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\ApiKey\RoleResolverInterface;
use Shlinkio\Shlink\CLI\Command\Api\ProvisionApiKeyCommand;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProvisionApiKeyCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject&ApiKeyServiceInterface $apiKeyService;
    private MockObject&RoleResolverInterface $roleResolver;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->roleResolver = $this->createMock(RoleResolverInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(
            new ProvisionApiKeyCommand($this->apiKeyService, $this->roleResolver),
        );
    }

    #[Test]
    public function nameAndExpirationAreRequired(): void
    {
        $this->apiKeyService->expects($this->never())->method('provision');
        self::assertSame(Command::INVALID, $this->commandTester->execute([]));
        self::assertSame(Command::INVALID, $this->commandTester->execute(['--name' => 'user@example.com']));
    }

    #[Test]
    public function stableGeneratedKeyOutputIsReturned(): void
    {
        $roles = [RoleDefinition::forAuthoredShortUrls()];
        $this->roleResolver->expects($this->once())->method('determineRoles')->willReturn($roles);
        $this->apiKeyService
            ->expects($this->once())
            ->method('provision')
            ->with($this->callback(static function (ApiKeyMeta $meta): bool {
                return $meta->name === 'user@example.com'
                    && $meta->expirationDate?->format('Y-m-d') === '2027-12-31';
            }))
            ->willReturn('plain-text-key');

        self::assertSame(Command::SUCCESS, $this->commandTester->execute([
            '--name' => 'user@example.com',
            '--expiration-date' => '2027-12-31',
        ]));
        self::assertStringContainsString('Generated API key: "plain-text-key"', $this->commandTester->getDisplay());
    }
}
