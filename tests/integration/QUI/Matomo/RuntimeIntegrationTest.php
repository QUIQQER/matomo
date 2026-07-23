<?php

namespace QUI\Tests\Matomo\Integration;

use PHPUnit\Framework\TestCase;
use QUI\GDPR\CookieInterface;
use QUI\Matomo\CookieUtils;
use QUI\Matomo\Cookies\IgnoreCookie;
use QUI\Projects\Project;
use ReflectionProperty;

class RuntimeIntegrationTest extends TestCase
{
    public function testCookieConfigurationUsesCurrentProject(): void
    {
        self::assertInstanceOf(Project::class, \QUI::getRewrite()->getProject());

        $allowedCategories = [
            CookieInterface::COOKIE_CATEGORY_ESSENTIAL,
            CookieInterface::COOKIE_CATEGORY_PREFERENCES,
            CookieInterface::COOKIE_CATEGORY_STATISTICS,
            CookieInterface::COOKIE_CATEGORY_MARKETING
        ];

        self::assertContains(CookieUtils::getCookieCategorySetting(), $allowedCategories);
        self::assertContains(CookieUtils::getUserIdTrackingCookieCategory(), $allowedCategories);
        self::assertContains(CookieUtils::getUserEmailTrackingCookieCategory(), $allowedCategories);
    }

    public function testIgnoreCookieUsesConfiguredMatomoOrigin(): void
    {
        $configuredOrigin = (string)\QUI::getRewrite()
            ->getProject()
            ?->getConfig('matomo.settings.url');
        $expectedOrigin = $configuredOrigin !== ''
            ? $configuredOrigin
            : \QUI::getRequest()->getHost();

        self::assertSame($expectedOrigin, (new IgnoreCookie())->getOrigin());
    }

    public function testCookieConfigurationFallsBackAfterProjectConfigFailure(): void
    {
        $Rewrite = \QUI::getRewrite();
        $ProjectProperty = new ReflectionProperty($Rewrite, 'project');
        $previousProject = $ProjectProperty->getValue($Rewrite);

        $Project = $this->createMock(Project::class);
        $Project->method('getConfig')
            ->willThrowException(new \QUI\Exception('Test configuration failure'));
        $ProjectProperty->setValue($Rewrite, $Project);

        try {
            self::assertSame('statistics', CookieUtils::getCookieCategorySetting());
            self::assertSame('marketing', CookieUtils::getUserIdTrackingCookieCategory());
            self::assertSame('marketing', CookieUtils::getUserEmailTrackingCookieCategory());
            self::assertSame(
                \QUI::getRequest()->getHost(),
                (new IgnoreCookie())->getOrigin()
            );
        } finally {
            $ProjectProperty->setValue($Rewrite, $previousProject);
        }
    }
}
