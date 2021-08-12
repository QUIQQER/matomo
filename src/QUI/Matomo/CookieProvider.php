<?php

/**
 * @author PCSG (Jan Wennrich)
 */
namespace QUI\Matomo;

use QUI\GDPR\CookieCollection;
use QUI\GDPR\CookieProviderInterface;
use QUI\Matomo\Cookies\ConsentCookie;
use QUI\Matomo\Cookies\CvarCookie;
use QUI\Matomo\Cookies\HsrCookie;
use QUI\Matomo\Cookies\IdCookie;
use QUI\Matomo\Cookies\IgnoreCookie;
use QUI\Matomo\Cookies\RefCookie;
use QUI\Matomo\Cookies\SesCookie;
use QUI\Matomo\Cookies\SessIdCookie;
use QUI\Matomo\Cookies\TestCookie;

/**
 * Class QuiqqerCookieProvider
 *
 * @package QUI\Matomo
 */
class CookieProvider implements CookieProviderInterface
{
    public static function getCookies(): CookieCollection
    {
        return new CookieCollection([
            new ConsentCookie(),
            new CvarCookie(),
            new HsrCookie(),
            new IdCookie(),
            new IgnoreCookie(),
            new RefCookie(),
            new SesCookie(),
            new SessIdCookie(),
            new TestCookie()
        ]);
    }
}
