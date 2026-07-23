<?php

namespace QUI\Tests\Matomo\Support;

use QUI\Cache\Exception as CacheException;
use QUI\Matomo\TagManagerSync;

class TestableTagManagerSync extends TagManagerSync
{
    public bool $hasCachedStatus = false;
    public mixed $cachedStatus = null;
    public bool $apiUnavailableCached = false;
    public bool $apiUnavailableMarked = false;
    public ?bool $writtenSuccessfulStatus = null;
    public string $requestedUrl = '';

    /**
     * @var array<string, int|string>
     */
    public array $requestedParameters = [];

    public int $requestCount = 0;

    public function __construct(private readonly string|bool $response)
    {
    }

    protected function readSuccessfulCache(string $cacheKey): mixed
    {
        if (!$this->hasCachedStatus) {
            throw new CacheException('Cache miss');
        }

        return $this->cachedStatus;
    }

    protected function writeSuccessfulCache(string $cacheKey, bool $status): void
    {
        $this->writtenSuccessfulStatus = $status;
    }

    protected function isApiUnavailableCached(string $cacheKey): bool
    {
        return $this->apiUnavailableCached;
    }

    protected function markApiUnavailable(string $cacheKey): void
    {
        $this->apiUnavailableMarked = true;
    }

    protected function request(string $url, array $parameters): string|bool
    {
        $this->requestCount++;
        $this->requestedUrl = $url;
        $this->requestedParameters = $parameters;

        return $this->response;
    }
}
