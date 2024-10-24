<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Dashboard;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Sites\Clients\SiteHttpClient;
use Garden\Sites\Cluster;
use Garden\Sites\Site;
use Garden\Sites\SiteRecord;
use Garden\Utils\ArrayUtils;

/**
 * @extends Site<DashboardSite, DashboardCluster>
 * @property DashboardSiteProvider $siteProvider
 */
class DashboardSite extends Site
{
    /**
     * Constructor.
     *
     * @param SiteRecord $siteRecord
     * @param DashboardSiteProvider $siteProvider
     */
    public function __construct(SiteRecord $siteRecord, DashboardSiteProvider $siteProvider)
    {
        parent::__construct($siteRecord, $siteProvider);
    }

    /**
     * @return string
     */
    public function getSystemAccessToken(): string
    {
        return $this->siteProvider->getSiteSystemAccessToken($this->siteRecord->getSiteID());
    }

    /**
     * Add additional default cookies to requests.
     *
     * @return SiteHttpClient
     */
    public function httpClient(): SiteHttpClient
    {
        $httpClient = parent::httpClient();
        $httpClient->setBaseUrl($this->siteRecord->getExtra("internalBaseUrl") ?? $httpClient->getBaseUrl());
        foreach ($this->siteRecord->getExtra("internalHeaders") ?? [] as $key => $val) {
            $httpClient->setDefaultHeader($key, $val);
        }

        return $httpClient;
    }

    /**
     * @inheritDoc
     */
    protected function loadSiteConfig(): array
    {
        $siteConfig = $this->siteProvider->getSiteConfig($this->getSiteID());
        return $siteConfig;
    }
}
