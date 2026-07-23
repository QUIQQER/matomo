<?php

namespace QUI\Matomo;

use JsonException;
use QUI;
use QUI\Cache\LongTermCache;
use QUI\Cache\Manager as CacheManager;
use QUI\Projects\Project;
use QUI\Utils\Request\Url;

use function array_key_exists;
use function filter_var;
use function hash;
use function http_build_query;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function preg_match;
use function str_contains;
use function strtolower;
use function trim;

/**
 * Resolves whether QUIQQER needs to mirror data layer entries to Matomo.
 */
class TagManagerSync
{
    public const CONFIG_KEY_DATA_LAYER_BRIDGE = 'matomo.settings.tagmanager.dataLayerBridge';

    protected const CACHE_TTL_UNAVAILABLE = 60;

    /**
     * @return array{
     *     automatic: bool,
     *     useQuiqqerBridge: bool,
     *     activelySyncGtmDataLayer: bool|null
     * }
     */
    public function getBridgeStatus(Project $Project): array
    {
        $activelySyncGtmDataLayer = $this->getActiveSyncStatus($Project);

        if ($activelySyncGtmDataLayer !== null) {
            return [
                'automatic' => true,
                'useQuiqqerBridge' => !$activelySyncGtmDataLayer,
                'activelySyncGtmDataLayer' => $activelySyncGtmDataLayer
            ];
        }

        return [
            'automatic' => false,
            'useQuiqqerBridge' => $this->getManualBridgeSetting($Project),
            'activelySyncGtmDataLayer' => null
        ];
    }

    public function shouldUseQuiqqerBridge(Project $Project): bool
    {
        return $this->getBridgeStatus($Project)['useQuiqqerBridge'];
    }

    public function getActiveSyncStatus(Project $Project): ?bool
    {
        $apiContext = $this->getApiContext($Project);

        if ($apiContext === null) {
            return null;
        }

        $cacheKey = $this->getCacheKey($apiContext);

        try {
            $cachedStatus = $this->readSuccessfulCache($cacheKey);

            if (is_bool($cachedStatus)) {
                return $cachedStatus;
            }
        } catch (QUI\Cache\Exception) {
            // Fetch the current value from Matomo below.
        }

        if ($this->isApiUnavailableCached($cacheKey)) {
            return null;
        }

        $status = $this->fetchActiveSyncStatus($apiContext);

        if ($status === null) {
            $this->markApiUnavailable($cacheKey);
        } else {
            $this->writeSuccessfulCache($cacheKey, $status);
        }

        return $status;
    }

    protected function getManualBridgeSetting(Project $Project): bool
    {
        $setting = $Project->getConfig(self::CONFIG_KEY_DATA_LAYER_BRIDGE);

        if ($setting === null || $setting === '') {
            return true;
        }

        if (is_string($setting)) {
            return !in_array(strtolower(trim($setting)), ['0', 'false', 'off', 'no'], true);
        }

        return (bool)$setting;
    }

    /**
     * @return array{url: string, siteId: int, containerId: string, token: string}|null
     */
    protected function getApiContext(Project $Project): ?array
    {
        $matomoUrl = $Project->getConfig('matomo.settings.url');
        $token = $Project->getConfig('matomo.settings.token');

        if (
            !is_string($matomoUrl)
            || trim($matomoUrl) === ''
            || !is_string($token)
            || trim($token) === ''
        ) {
            return null;
        }

        $siteId = (int)Matomo::getSiteId($Project);

        if ($siteId <= 0) {
            return null;
        }

        $containerId = self::extractContainerId(Matomo::getTagManagerCode($Project));

        if ($containerId === null) {
            return null;
        }

        $matomoUrl = trim($matomoUrl);

        if (!str_contains($matomoUrl, '://')) {
            $matomoUrl = 'https://' . $matomoUrl;
        }

        return [
            'url' => rtrim($matomoUrl, '/') . '/index.php',
            'siteId' => $siteId,
            'containerId' => $containerId,
            'token' => trim($token)
        ];
    }

    public static function extractContainerId(string $tagManagerCode): ?string
    {
        $matches = [];

        if (
            preg_match(
                '~container_([A-Za-z0-9]{8})(?:_[A-Za-z0-9_-]+)?\.js~',
                $tagManagerCode,
                $matches
            ) !== 1
        ) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param array{url: string, siteId: int, containerId: string, token: string} $apiContext
     */
    protected function fetchActiveSyncStatus(array $apiContext): ?bool
    {
        try {
            $response = $this->request(
                $apiContext['url'],
                [
                    'module' => 'API',
                    'method' => 'TagManager.getContainer',
                    'format' => 'json',
                    'idSite' => $apiContext['siteId'],
                    'idContainer' => $apiContext['containerId'],
                    'token_auth' => $apiContext['token']
                ]
            );

            if (!is_string($response) || $response === '') {
                return null;
            }

            $container = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (
                !is_array($container)
                || !array_key_exists('activelySyncGtmDataLayer', $container)
            ) {
                return null;
            }

            return filter_var(
                $container['activelySyncGtmDataLayer'],
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE
            );
        } catch (QUI\Exception | JsonException) {
            return null;
        }
    }

    /**
     * @param array<string, int|string> $parameters
     *
     * @throws QUI\Exception
     */
    protected function request(string $url, array $parameters): string|bool
    {
        return Url::get(
            $url,
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($parameters),
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 3
            ]
        );
    }

    protected function readSuccessfulCache(string $cacheKey): mixed
    {
        return LongTermCache::get($cacheKey);
    }

    protected function writeSuccessfulCache(string $cacheKey, bool $status): void
    {
        LongTermCache::set($cacheKey, $status);
    }

    protected function isApiUnavailableCached(string $cacheKey): bool
    {
        try {
            return CacheManager::get($cacheKey . '/unavailable') === true;
        } catch (QUI\Cache\Exception) {
            return false;
        }
    }

    protected function markApiUnavailable(string $cacheKey): void
    {
        CacheManager::set(
            $cacheKey . '/unavailable',
            true,
            self::CACHE_TTL_UNAVAILABLE
        );
    }

    /**
     * @param array{url: string, siteId: int, containerId: string, token: string} $apiContext
     */
    protected function getCacheKey(array $apiContext): string
    {
        return 'quiqqer/matomo/tag-manager-sync/' . hash(
            'sha256',
            $apiContext['url'] . '|' . $apiContext['siteId'] . '|' . $apiContext['containerId']
        );
    }
}
