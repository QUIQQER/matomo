<?php

namespace QUI\Tests\Matomo\Integration;

use PHPUnit\Framework\TestCase;
use QUI\Config;
use QUI\Matomo\Patches;
use QUI\Package\Manager as PackageManager;
use QUI\Package\Package;
use QUI\Projects\Manager;
use QUI\Projects\Project;
use QUI\Translator;

class PatchesIntegrationTest extends TestCase
{
    private const PATCH_PROJECT_NAME = '__matomo_patch_phpunit__';

    public function testPiwikMigrationIsANoOpWithoutMigratableSettings(): void
    {
        $suffixes = [
            '.settings.url',
            '.settings.id',
            '.settings.token',
            '.settings.langdata',
            '.settings.cookiecategory'
        ];

        foreach (Manager::getProjects(true) as $Project) {
            foreach ($suffixes as $suffix) {
                if (
                    $Project->getConfig('piwik' . $suffix)
                    && !$Project->getConfig('matomo' . $suffix)
                ) {
                    self::markTestSkipped(
                        'The installation contains Piwik settings that would be migrated.'
                    );
                }
            }
        }

        Patches::migratePiwikSettings();

        self::addToAssertionCount(1);
    }

    public function testSiteIdPatchMigratesLanguageValues(): void
    {
        $projectConfig = Manager::getConfig()->toArray();

        if ($projectConfig === []) {
            self::markTestSkipped('No project configuration is available.');
        }

        $Config = $this->createMock(Config::class);
        $Config->expects(self::once())
            ->method('get')
            ->with('patches', Patches::SITE_IDS_TO_LOCALE_VARIABLES)
            ->willReturn(0);
        $Config->expects(self::once())
            ->method('set')
            ->with('patches', Patches::SITE_IDS_TO_LOCALE_VARIABLES, 1);
        $Config->expects(self::once())->method('save');

        $Package = $this->createMock(Package::class);
        $Package->method('getConfig')->willReturn($Config);

        $PackageManager = $this->createMock(PackageManager::class);
        $PackageManager->method('getInstalledPackage')
            ->with('quiqqer/matomo')
            ->willReturn($Package);

        $previousPackageManager = \QUI::$PackageManager;
        $previousProjects = Manager::$projects;
        \QUI::$PackageManager = $PackageManager;
        Manager::$projects = [];

        $isFirstProject = true;

        foreach ($projectConfig as $projectName => $configuration) {
            $language = $configuration['default_lang'] ?? 'en';
            $template = $configuration['template'] ?? false;
            $Project = $this->createMock(Project::class);
            $Project->method('getTemplate')->willReturn($template);

            if ($isFirstProject) {
                $Project->method('getName')->willReturn(self::PATCH_PROJECT_NAME);
                $Project->method('getConfig')
                    ->with('matomo.settings.langdata')
                    ->willReturn(
                        json_encode([
                            'en' => ['id' => '81'],
                            'de' => ['id' => '82']
                        ])
                    );
                $isFirstProject = false;
            } else {
                $Project->method('getName')->willReturn((string)$projectName);
                $Project->method('getConfig')->willReturn('');
            }

            Manager::$projects[(string)$projectName][(string)$language] = $Project;
        }

        $group = 'project/' . self::PATCH_PROJECT_NAME;

        try {
            Patches::moveSiteIdsToLocaleVariables();

            self::assertSame(
                '81',
                \QUI::getLocale()->getByLang(
                    'en',
                    $group,
                    \QUI\Matomo\Matomo::LOCALE_KEY_SITE_IDS
                )
            );
        } finally {
            \QUI::$PackageManager = $previousPackageManager;
            Manager::$projects = $previousProjects;
            Translator::delete(
                $group,
                \QUI\Matomo\Matomo::LOCALE_KEY_SITE_IDS
            );
            $this->removeLocaleFiles(self::PATCH_PROJECT_NAME);
        }
    }

    private function removeLocaleFiles(string $projectName): void
    {
        $localeFiles = glob(
            VAR_DIR . 'locale/*/LC_MESSAGES/project_' . $projectName . '.ini.php'
        ) ?: [];

        foreach ($localeFiles as $localeFile) {
            unlink($localeFile);
        }

        $localeBinDirectory = VAR_DIR . 'locale/bin/project/' . $projectName;

        if (is_dir($localeBinDirectory)) {
            \QUI::getTemp()->moveToTemp($localeBinDirectory);
        }
    }
}
