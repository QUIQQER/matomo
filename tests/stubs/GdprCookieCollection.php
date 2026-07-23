<?php

namespace QUI\GDPR;

if (!class_exists(CookieCollection::class)) {
    class CookieCollection extends \QUI\Collection
    {
        /** @var list<class-string<CookieInterface>> */
        protected array $allowed = [CookieInterface::class];
    }
}
