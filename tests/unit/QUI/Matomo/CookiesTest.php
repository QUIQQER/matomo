<?php

namespace QUI\Tests\Matomo\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\GDPR\CookieInterface;
use QUI\Matomo\CookieProvider;
use QUI\Matomo\Cookies\ConsentCookie;
use QUI\Matomo\Cookies\CvarCookie;
use QUI\Matomo\Cookies\HsrCookie;
use QUI\Matomo\Cookies\IdCookie;
use QUI\Matomo\Cookies\IgnoreCookie;
use QUI\Matomo\Cookies\RefCookie;
use QUI\Matomo\Cookies\SesCookie;
use QUI\Matomo\Cookies\SessIdCookie;
use QUI\Matomo\Cookies\TestCookie;
use QUI\Matomo\Cookies\UserEmailTracking;
use QUI\Matomo\Cookies\UserIdTracking;

class CookiesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<CookieInterface>, string}>
     */
    public static function cookieProvider(): iterable
    {
        yield 'consent' => [ConsentCookie::class, 'mtm_consent'];
        yield 'custom variables' => [CvarCookie::class, '_pk_cvar.*'];
        yield 'heatmap/session recording' => [HsrCookie::class, '_pk_hsr.*'];
        yield 'visitor id' => [IdCookie::class, '_pk_id.*'];
        yield 'tracking opt-out' => [IgnoreCookie::class, 'matomo_ignore'];
        yield 'referrer' => [RefCookie::class, '_pk_ref.*'];
        yield 'session' => [SesCookie::class, '_pk_ses.*'];
        yield 'opt-out session' => [SessIdCookie::class, 'MATOMO_SESSID'];
        yield 'cookie support' => [TestCookie::class, '_pk_testcookie.*'];
        yield 'user id consent' => [UserIdTracking::class, ''];
        yield 'user email consent' => [UserEmailTracking::class, ''];
    }

    /**
     * @param class-string<CookieInterface> $cookieClass
     */
    #[DataProvider('cookieProvider')]
    public function testCookieMetadata(string $cookieClass, string $expectedName): void
    {
        $Cookie = new $cookieClass();

        self::assertSame($expectedName, $Cookie->getName());
        self::assertNotSame('', $Cookie->getPurpose());
        self::assertContains(
            $Cookie->getCategory(),
            [
                CookieInterface::COOKIE_CATEGORY_ESSENTIAL,
                CookieInterface::COOKIE_CATEGORY_PREFERENCES,
                CookieInterface::COOKIE_CATEGORY_STATISTICS,
                CookieInterface::COOKIE_CATEGORY_MARKETING
            ]
        );
    }

    public function testProviderReturnsEveryDeclaredCookie(): void
    {
        $Cookies = CookieProvider::getCookies();

        self::assertCount(11, $Cookies->toArray());
        self::assertInstanceOf(ConsentCookie::class, $Cookies->first());
        self::assertInstanceOf(UserEmailTracking::class, $Cookies->last());
    }

    public function testFirstPartyCookieOriginsUseTheCurrentHost(): void
    {
        $expectedHost = \QUI::getRequest()->getHost();

        foreach (
            [
                new ConsentCookie(),
                new CvarCookie(),
                new HsrCookie(),
                new IdCookie(),
                new RefCookie(),
                new SesCookie(),
                new SessIdCookie(),
                new TestCookie(),
                new UserIdTracking(),
                new UserEmailTracking()
            ] as $Cookie
        ) {
            self::assertSame($expectedHost, $Cookie->getOrigin());
        }
    }

    public function testCookieLifetimesAreAvailable(): void
    {
        self::assertNotSame('', (new ConsentCookie())->getLifetime());
        self::assertNotSame('', (new CvarCookie())->getLifetime());
        self::assertNotSame('', (new HsrCookie())->getLifetime());
        self::assertNotSame('', (new IdCookie())->getLifetime());
        self::assertNotSame('', (new IgnoreCookie())->getLifetime());
        self::assertNotSame('', (new RefCookie())->getLifetime());
        self::assertNotSame('', (new SesCookie())->getLifetime());
        self::assertNotSame('', (new SessIdCookie())->getLifetime());
        self::assertNotSame('', (new TestCookie())->getLifetime());
        self::assertSame('', (new UserIdTracking())->getLifetime());
        self::assertSame('', (new UserEmailTracking())->getLifetime());
    }
}
