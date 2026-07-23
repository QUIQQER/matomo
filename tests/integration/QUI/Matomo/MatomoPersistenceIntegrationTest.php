<?php

namespace QUI\Tests\Matomo\Integration;

use PHPUnit\Framework\TestCase;
use QUI\Matomo\Matomo;
use QUI\Projects\Manager;
use QUI\Projects\Project;
use QUI\Translator;

class MatomoPersistenceIntegrationTest extends TestCase
{
    private const PROJECT_NAME = '__matomo_phpunit_persistence__';

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testLanguageSpecificValuesAreStoredAndRead(): void
    {
        $Project = $this->createProjectMock();

        Matomo::setSiteIds(['en' => '81', 'de' => '82'], $Project);
        Matomo::setTagManagerCodes(
            [
                'en' => '<script>english</script>',
                'de' => '<script>deutsch</script>'
            ],
            $Project
        );

        self::assertSame('81', Matomo::getSiteId($Project, 'en'));
        self::assertSame(
            '<script>english</script>',
            Matomo::getTagManagerCode($Project, 'en')
        );
    }

    public function testGeneralTagManagerCodeIsStoredAndRead(): void
    {
        $Project = $this->createProjectMock();
        $code = '<script>window._mtm = [];</script>';

        self::assertTrue(Matomo::setGeneralTagManagerCode($code, $Project));
        self::assertSame($code, Matomo::getGeneralTagManagerCode($Project));
    }

    private function createProjectMock(): Project
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getName')->willReturn(self::PROJECT_NAME);
        $Project->method('getLang')->willReturn('en');
        $Project->method('getConfig')->willReturnCallback(
            static function (bool|string $key = false): mixed {
                if ($key === 'matomo.settings.id') {
                    return 99;
                }

                return Manager::getConfig()->getValue(
                    self::PROJECT_NAME,
                    (string)$key
                );
            }
        );

        return $Project;
    }

    private function cleanup(): void
    {
        $group = 'project/' . self::PROJECT_NAME;

        Translator::delete($group, Matomo::LOCALE_KEY_SITE_IDS);
        Translator::delete($group, Matomo::LOCALE_KEY_TAG_MANAGER_CODES);

        $localeFiles = glob(
            VAR_DIR . 'locale/*/LC_MESSAGES/project_' . self::PROJECT_NAME . '.ini.php'
        ) ?: [];

        foreach ($localeFiles as $localeFile) {
            unlink($localeFile);
        }

        $localeBinDirectory = VAR_DIR . 'locale/bin/project/' . self::PROJECT_NAME;

        if (is_dir($localeBinDirectory)) {
            \QUI::getTemp()->moveToTemp($localeBinDirectory);
        }

        $Config = Manager::getConfig();
        $Config->del(self::PROJECT_NAME);
        $Config->save();
    }
}
