<?php

namespace QUI\Tests\Matomo\Integration;

use PHPUnit\Framework\TestCase;
use QUI\FrontendUsers\RegistrarInterface;
use QUI\Interfaces\Users\User;
use QUI\Matomo\EventTracking;
use QUI\Projects\Project;
use ReflectionProperty;

class EventTrackingIntegrationTest extends TestCase
{
    public function testTrackingEventsReturnWhenMatomoIsNotConfigured(): void
    {
        $Rewrite = \QUI::getRewrite();
        $ProjectProperty = new ReflectionProperty($Rewrite, 'project');
        $previousProject = $ProjectProperty->getValue($Rewrite);

        $Project = $this->createMock(Project::class);
        $Project->method('getConfig')->willReturn('');
        $ProjectProperty->setValue($Rewrite, $Project);

        try {
            $User = $this->createMock(User::class);
            $Registrar = $this->createMock(RegistrarInterface::class);

            EventTracking::onQuiqqerFrontendUsersUserRegister(
                $User,
                $Registrar,
                'active'
            );
            EventTracking::onQuiqqerFrontendUsersUserActivate(
                $User,
                $Registrar
            );
        } finally {
            $ProjectProperty->setValue($Rewrite, $previousProject);
        }

        self::addToAssertionCount(2);
    }
}
