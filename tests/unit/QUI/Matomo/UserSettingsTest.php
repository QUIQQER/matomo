<?php

namespace QUI\Tests\Matomo\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Utils\XML\Settings;

class UserSettingsTest extends TestCase
{
    public function testBackendCategoryRendersTranslatedFieldsWithoutATemplate(): void
    {
        $Settings = new Settings();
        $Settings->setXMLPath('//user/window');
        $html = $Settings->getCategoriesHtml([$this->userXml()], 'MATOMO');
        $Document = new DOMDocument();
        self::assertTrue($Document->loadHTML('<meta charset="UTF-8">' . $html));
        $Path = new DOMXPath($Document);
        self::assertSame(2.0, $Path->evaluate('count(//input)'));

        foreach (['login', 'pass'] as $field) {
            self::assertSame(1.0, $Path->evaluate('count(//input[@name="quiqqer.matomo.' . $field . '"])'));
            self::assertSame(
                QUI::getLocale()->get('quiqqer/matomo', 'user.profile.' . $field),
                $Path->evaluate('string(//label[input[@name="quiqqer.matomo.' . $field . '"]]/div)')
            );
        }

        self::assertStringContainsString(
            QUI::getLocale()->get('quiqqer/matomo', 'user.profile.pass.description'),
            $html
        );
        self::assertStringNotContainsString('[quiqqer/matomo]', $html);
    }

    public function testMigrationPreservesEncryptedAttributesAndFrontendProfile(): void
    {
        $Document = new DOMDocument();
        self::assertTrue($Document->load($this->userXml()));
        $Path = new DOMXPath($Document);
        self::assertSame(0.0, $Path->evaluate('count(//window/tab | //window//template)'));
        self::assertSame('5', $Path->evaluate('string(//window/categories/category[@name="MATOMO"]/@index)'));
        self::assertSame('fa fa-line-chart', $Path->evaluate('string(//window/categories/category/icon)'));

        foreach (['login', 'pass'] as $field) {
            self::assertSame('1', $Path->evaluate(
                'string(/quiqqer/user/attributes/attribute[text()="quiqqer.matomo.' . $field . '"]/@encrypt)'
            ));
        }

        self::assertSame(
            'OPT_DIR/quiqqer/matomo/template/profile.html',
            $Path->evaluate('string(/quiqqer/user/profile/tab[@name="MATOMO"]/template)')
        );
        self::assertFileExists(dirname($this->userXml()) . '/template/profile.html');
    }

    private function userXml(): string
    {
        return dirname(__DIR__, 4) . '/user.xml';
    }
}
