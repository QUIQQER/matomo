<?php

namespace QUI\Tests\Matomo\Unit;

use PHPUnit\Framework\TestCase;
use QUI\Interfaces\Projects\Site;
use QUI\Matomo\TemplateExtender;
use QUI\Projects\Project;
use QUI\Template;

class TemplateExtenderTest extends TestCase
{
    public function testHeaderAddsDataLayerConfigurationAndScript(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getConfig')->willReturnCallback(
            static fn(bool|string $key = false): mixed => match ($key) {
                'matomo.settings.trackToPaq' => true,
                default => null
            }
        );

        $Site = $this->createMock(Site::class);
        $Site->method('getProject')->willReturn($Project);

        $headerExtensions = [];
        $Template = $this->createMock(Template::class);
        $Template->expects(self::exactly(2))
            ->method('extendHeader')
            ->willReturnCallback(
                static function (string $extension) use (&$headerExtensions): void {
                    $headerExtensions[] = $extension;
                }
            );

        TemplateExtender::extendHeader($Template, $Site);

        self::assertSame(
            '<script data-no-cache="1">'
            . 'window.MATOMO_TRACK_TO_PAQ = 1;'
            . 'window.MATOMO_USE_DATA_LAYER_BRIDGE = 1;'
            . '</script>',
            $headerExtensions[0]
        );
        self::assertStringContainsString(
            'quiqqer/matomo/bin/dataLayer.js',
            $headerExtensions[1]
        );
    }

    public function testFooterIsNotAddedWithoutRequiredConfiguration(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getName')->willReturn('__matomo_disabled_test__');
        $Project->method('getLang')->willReturn('en');
        $Project->method('getConfig')->willReturnCallback(
            static fn(bool|string $key = false): mixed => match ($key) {
                'matomo.settings.id' => 0,
                'matomo.settings.url', 'matomo.settings.langdata' => '',
                default => null
            }
        );

        $Site = $this->createMock(Site::class);
        $Site->method('getProject')->willReturn($Project);

        $Template = $this->createMock(Template::class);
        $Template->expects(self::never())->method('extendFooter');

        TemplateExtender::extendFooter($Template, $Site);
    }

    public function testFooterAddsRenderedTrackingCode(): void
    {
        $config = [
            'matomo.settings.url' => 'stats.example.test',
            'matomo.settings.id' => 42,
            'matomo.settings.langdata' => '',
            'matomo.settings.userIdTracking.isEnabled' => 0,
            'matomo.settings.userEmailTracking.isEnabled' => 0,
            'matomo.settings.userEmailTracking.customDimensionId' => 0
        ];

        $Project = $this->createMock(Project::class);
        $Project->method('getName')->willReturn('__matomo_footer_test__');
        $Project->method('getLang')->willReturn('en');
        $Project->method('getConfig')->willReturnCallback(
            static fn(bool|string $key = false): mixed => $config[$key] ?? null
        );

        $Site = $this->createMock(Site::class);
        $Site->method('getProject')->willReturn($Project);

        $footer = '';
        $Template = $this->createMock(Template::class);
        $Template->expects(self::once())
            ->method('extendFooter')
            ->willReturnCallback(
                static function (string $extension) use (&$footer): void {
                    $footer = $extension;
                }
            );

        TemplateExtender::extendFooter($Template, $Site);

        self::assertStringContainsString(
            "const u = '//stats.example.test/';",
            $footer
        );
        self::assertStringContainsString(
            "parseInt('42')",
            $footer
        );
    }
}
