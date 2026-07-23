<?php

namespace QUI\Tests\Matomo\Unit;

use MatomoTracker;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\Matomo\Matomo;
use QUI\Projects\Project;

class MatomoTest extends TestCase
{
    public function testMatomoClientRequiresHostAndSiteId(): void
    {
        $Project = $this->createProjectMock([
            'matomo.settings.url' => '',
            'matomo.settings.id' => 0
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Matomo host not configured');

        Matomo::getMatomoClient($Project);
    }

    public function testMatomoClientNormalizesHostAndAppliesToken(): void
    {
        $Project = $this->createProjectMock([
            'matomo.settings.url' => 'stats.example.test',
            'matomo.settings.id' => '42',
            'matomo.settings.token' => 'secret-token'
        ]);

        $Tracker = Matomo::getMatomoClient($Project);

        self::assertSame(42, $Tracker->idSite);
        self::assertSame('secret-token', $Tracker->token_auth);
        self::assertSame('https://stats.example.test', MatomoTracker::$URL);
    }

    public function testMatomoClientPreservesExistingProtocol(): void
    {
        $Project = $this->createProjectMock([
            'matomo.settings.url' => 'http://stats.example.test',
            'matomo.settings.id' => 12,
            'matomo.settings.token' => ''
        ]);

        $Tracker = Matomo::getMatomoClient($Project);

        self::assertSame(12, $Tracker->idSite);
        self::assertSame('http://stats.example.test', MatomoTracker::$URL);
        self::assertFalse($Tracker->token_auth);
    }

    public function testGeneralTagManagerCodeIsDecoded(): void
    {
        $code = '<script>window._mtm = [];</script>';
        $Project = $this->createProjectMock([
            Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => json_encode(htmlentities($code))
        ]);

        self::assertSame($code, Matomo::getGeneralTagManagerCode($Project));
    }

    public function testMissingLanguageSpecificSiteIdFallsBackToGeneralId(): void
    {
        $Project = $this->createProjectMock(
            ['matomo.settings.id' => '17'],
            '__matomo_test_without_locale__',
            'en'
        );

        self::assertSame(17, Matomo::getSiteId($Project, 'en'));
    }

    public function testMissingLanguageSpecificTagManagerCodeFallsBackToGeneralCode(): void
    {
        $code = '<script>fallback</script>';
        $Project = $this->createProjectMock(
            [Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE => json_encode(htmlentities($code))],
            '__matomo_test_without_tag_manager_locale__',
            'en'
        );

        self::assertSame($code, Matomo::getTagManagerCode($Project, 'en'));
    }

    public function testTagManagerEnabledUsesProjectSetting(): void
    {
        self::assertTrue(
            Matomo::isTagManagerEnabled(
                $this->createProjectMock([Matomo::CONFIG_KEY_TAG_MANAGER_ENABLED => '1'])
            )
        );
        self::assertFalse(
            Matomo::isTagManagerEnabled(
                $this->createProjectMock([Matomo::CONFIG_KEY_TAG_MANAGER_ENABLED => '0'])
            )
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createProjectMock(
        array $config,
        string $name = '__matomo_test_project__',
        string $language = 'en'
    ): Project {
        $Project = $this->createMock(Project::class);
        $Project->method('getConfig')->willReturnCallback(
            static fn(bool|string $key = false): mixed => $config[$key] ?? null
        );
        $Project->method('getName')->willReturn($name);
        $Project->method('getLang')->willReturn($language);

        return $Project;
    }
}
