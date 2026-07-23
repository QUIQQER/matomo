<?php

namespace QUI\Tests\Matomo\Unit;

use PHPUnit\Framework\TestCase;
use QUI\Matomo\Matomo;
use QUI\Matomo\TagManagerSync;
use QUI\Projects\Project;
use QUI\Tests\Matomo\Support\TestableTagManagerSync;

require_once dirname(__DIR__, 3) . '/Support/TestableTagManagerSync.php';

class TagManagerSyncTest extends TestCase
{
    public function testContainerIdIsExtractedFromLiveAndEnvironmentUrls(): void
    {
        self::assertSame(
            'Ab12Cd34',
            TagManagerSync::extractContainerId(
                '<script src="https://stats.example.test/js/container_Ab12Cd34.js"></script>'
            )
        );
        self::assertSame(
            'Ab12Cd34',
            TagManagerSync::extractContainerId(
                '<script src="https://stats.example.test/js/container_Ab12Cd34_staging.js"></script>'
            )
        );
        self::assertNull(TagManagerSync::extractContainerId('<script>no container</script>'));
    }

    public function testEnabledMatomoSyncDisablesQuiqqerBridge(): void
    {
        $Sync = new TestableTagManagerSync(
            json_encode(['activelySyncGtmDataLayer' => 1])
        );

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => 'stats.example.test',
                'matomo.settings.id' => '42',
                'matomo.settings.token' => 'secret-token',
                TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => '1',
                Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
            ])
        );

        self::assertSame(
            [
                'automatic' => true,
                'useQuiqqerBridge' => false,
                'activelySyncGtmDataLayer' => true
            ],
            $status
        );
        self::assertSame('https://stats.example.test/index.php', $Sync->requestedUrl);
        self::assertSame('TagManager.getContainer', $Sync->requestedParameters['method']);
        self::assertSame(42, $Sync->requestedParameters['idSite']);
        self::assertSame('Ab12Cd34', $Sync->requestedParameters['idContainer']);
        self::assertSame('secret-token', $Sync->requestedParameters['token_auth']);
        self::assertStringNotContainsString('secret-token', $Sync->requestedUrl);
        self::assertTrue($Sync->writtenSuccessfulStatus);
    }

    public function testDisabledMatomoSyncEnablesQuiqqerBridge(): void
    {
        $Sync = new TestableTagManagerSync(
            json_encode(['activelySyncGtmDataLayer' => 0])
        );

        self::assertTrue(
            $Sync->shouldUseQuiqqerBridge(
                $this->createProject([
                    'matomo.settings.url' => 'https://stats.example.test/',
                    'matomo.settings.id' => 42,
                    'matomo.settings.token' => 'secret-token',
                    TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => '0',
                    Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
                ])
            )
        );
    }

    public function testUnavailableApiUsesDisabledManualSetting(): void
    {
        $Sync = new TestableTagManagerSync('{invalid json');

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => 'https://stats.example.test',
                'matomo.settings.id' => 42,
                'matomo.settings.token' => 'secret-token',
                TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => '0',
                Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
            ])
        );

        self::assertSame(
            [
                'automatic' => false,
                'useQuiqqerBridge' => false,
                'activelySyncGtmDataLayer' => null
            ],
            $status
        );
        self::assertTrue($Sync->apiUnavailableMarked);
    }

    public function testMissingApiConfigurationUsesEnabledDefaultWithoutRequest(): void
    {
        $Sync = new TestableTagManagerSync('');

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => '',
                'matomo.settings.id' => 0,
                'matomo.settings.token' => ''
            ])
        );

        self::assertFalse($status['automatic']);
        self::assertTrue($status['useQuiqqerBridge']);
        self::assertSame(0, $Sync->requestCount);
        self::assertNull($Sync->writtenSuccessfulStatus);
    }

    public function testMissingTokenSkipsApiAndUsesManualSetting(): void
    {
        $Sync = new TestableTagManagerSync(
            json_encode(['activelySyncGtmDataLayer' => true])
        );
        $Sync->hasCachedStatus = true;
        $Sync->cachedStatus = true;

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => 'https://stats.example.test',
                'matomo.settings.id' => 42,
                'matomo.settings.token' => '',
                TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => false,
                Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
            ])
        );

        self::assertFalse($status['automatic']);
        self::assertFalse($status['useQuiqqerBridge']);
        self::assertSame(0, $Sync->requestCount);
        self::assertFalse($Sync->apiUnavailableMarked);
        self::assertNull($Sync->writtenSuccessfulStatus);
    }

    public function testTemporaryApiFailureCacheUsesManualSettingWithoutRequest(): void
    {
        $Sync = new TestableTagManagerSync(
            json_encode(['activelySyncGtmDataLayer' => true])
        );
        $Sync->apiUnavailableCached = true;

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => 'https://stats.example.test',
                'matomo.settings.id' => 42,
                'matomo.settings.token' => 'secret-token',
                TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => '1',
                Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
            ])
        );

        self::assertFalse($status['automatic']);
        self::assertTrue($status['useQuiqqerBridge']);
        self::assertSame(0, $Sync->requestCount);
    }

    public function testMissingContainerCodeUsesManualSettingWithoutRequest(): void
    {
        $Sync = new TestableTagManagerSync('');

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => 'https://stats.example.test',
                'matomo.settings.id' => 42,
                'matomo.settings.token' => 'secret-token',
                TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => '1',
                Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => ''
            ])
        );

        self::assertFalse($status['automatic']);
        self::assertTrue($status['useQuiqqerBridge']);
        self::assertSame(0, $Sync->requestCount);
    }

    public function testEmptyApiResponseUsesManualSetting(): void
    {
        $Sync = new TestableTagManagerSync('');

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => 'https://stats.example.test',
                'matomo.settings.id' => 42,
                'matomo.settings.token' => 'secret-token',
                TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => '1',
                Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
            ])
        );

        self::assertFalse($status['automatic']);
        self::assertTrue($status['useQuiqqerBridge']);
        self::assertTrue($Sync->apiUnavailableMarked);
    }

    public function testApiResponseWithoutSyncSettingUsesManualSetting(): void
    {
        $Sync = new TestableTagManagerSync(json_encode(['idcontainer' => 'Ab12Cd34']));

        $status = $Sync->getBridgeStatus(
            $this->createProject([
                'matomo.settings.url' => 'https://stats.example.test',
                'matomo.settings.id' => 42,
                'matomo.settings.token' => 'secret-token',
                TagManagerSync::CONFIG_KEY_DATA_LAYER_BRIDGE => '0',
                Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
            ])
        );

        self::assertFalse($status['automatic']);
        self::assertFalse($status['useQuiqqerBridge']);
        self::assertTrue($Sync->apiUnavailableMarked);
    }

    public function testCachedStatusAvoidsApiRequest(): void
    {
        $Sync = new TestableTagManagerSync('');
        $Sync->hasCachedStatus = true;
        $Sync->cachedStatus = true;

        self::assertFalse(
            $Sync->shouldUseQuiqqerBridge(
                $this->createProject([
                    'matomo.settings.url' => 'https://stats.example.test',
                    'matomo.settings.id' => 42,
                    'matomo.settings.token' => 'secret-token',
                    Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => $this->getEncodedContainerCode()
                ])
            )
        );
        self::assertSame(0, $Sync->requestCount);
        self::assertNull($Sync->writtenSuccessfulStatus);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createProject(array $config): Project
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getConfig')->willReturnCallback(
            static fn(bool|string $key = false): mixed => $config[$key] ?? null
        );
        $Project->method('getName')->willReturn('__matomo_tag_manager_sync_test__');
        $Project->method('getLang')->willReturn('en');

        return $Project;
    }

    private function getEncodedContainerCode(): string
    {
        return (string)json_encode(
            htmlentities(
                '<script src="https://stats.example.test/js/container_Ab12Cd34.js"></script>'
            )
        );
    }
}
