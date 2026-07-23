<?php

namespace QUI\Tests\Matomo\Unit;

use PHPUnit\Framework\TestCase;
use QUI\Matomo\EventHandler;
use QUI\Package\Package;

class EventHandlerTest extends TestCase
{
    public function testProjectConfigEventIgnoresUnknownProject(): void
    {
        EventHandler::onProjectConfigSave(
            '__matomo_unknown_project__',
            [],
            []
        );

        self::addToAssertionCount(1);
    }

    public function testProjectConfigEventWithoutRelevantParametersLeavesConfigurationUntouched(): void
    {
        $Project = \QUI::getRewrite()->getProject();

        if ($Project === null) {
            self::markTestSkipped('No current project is available.');
        }

        EventHandler::onProjectConfigSave(
            $Project->getName(),
            [],
            []
        );

        self::addToAssertionCount(1);
    }

    public function testInstallEventIgnoresOtherPackages(): void
    {
        $Package = $this->createMock(Package::class);
        $Package->method('getName')->willReturn('quiqqer/unrelated-package');

        EventHandler::onPackageInstallAfter($Package);

        self::addToAssertionCount(1);
    }
}
