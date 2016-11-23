<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Command;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;

/**
 * @dbIsolationPerTest
 */
class ReversSyncCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    public function testShouldNotSendReverseSyncIntegrationWithoutConnector()
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $result = $this->runCommand('oro:integration:reverse:sync', [
            '--integration='.$integration->getId()
        ]);

        $this->assertNotContains('Schedule reverse sync for "Foo Integration" integration', $result);
        $this->assertContains('Connector must be set', $result);

        self::assertEmptyMessages(Topics::REVERS_SYNC_INTEGRATION);
    }

    public function testShouldSendReverseSyncIntegrationWithoutAnyAdditionalOptions()
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $result = $this->runCommand('oro:integration:reverse:sync', [
            '--integration='.$integration->getId(),
            '--connector=TestChannel'
        ]);

        $this->assertContains('Schedule reverse sync for "Foo Integration" integration', $result);

        self::assertMessageSent(
            Topics::REVERS_SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => $integration->getId(),
                    'connector_parameters' => [],
                    'connector' => 'TestChannel',
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }

    public function testShouldSendSyncIntegrationWithCustomConnectorAndOptions()
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $result = $this->runCommand('oro:integration:reverse:sync', [
            '--integration='.$integration->getId(),
            '--connector' => 'theConnector',
            'fooConnectorOption=fooValue',
            'barConnectorOption=barValue',
        ]);

        $this->assertContains('Schedule reverse sync for "Foo Integration" integration', $result);

        self::assertMessageSent(
            Topics::REVERS_SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => $integration->getId(),
                    'connector_parameters' => [
                        'fooConnectorOption' => 'fooValue',
                        'barConnectorOption' => 'barValue',
                    ],
                    'connector' => 'theConnector',
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }
}
