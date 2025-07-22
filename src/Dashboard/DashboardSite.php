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
        $realHostname = $this->getRealHostName();
        $kludgedBaseUrl = $this->replaceHostnameInUrl($this->getBaseUrl());
        $httpClient->setBaseUrl($kludgedBaseUrl);
        $httpClient->setDefaultHeader("Host", $realHostname);

        $httpClient->addMiddleware(function (HttpRequest $request, callable $next) use ($realHostname): HttpResponse {
            $request->setUrl($this->replaceHostnameInUrl($request->getUrl()));
            $request->setHeader("Host", $realHostname);
            return $next($request);
        });

        return $httpClient;
    }

    /**
     * Given a url, try to replace it's base url so it routes with the cluster router.
     *
     * @param string $url
     * @return string
     */
    public function replaceHostnameInUrl(string $url): string
    {
        $internalHostname = $this->getInternalHostname();

        $realHostname = $this->getRealHostName();
        $kludgedUrl = str_replace($realHostname, $internalHostname, $url);
        $kludgedUrl = str_replace("https", "http", $kludgedUrl);
        return $kludgedUrl;
    }

    /**
     * @return string
     */
    private function getRealHostName(): string
    {
        $baseUrl = $this->getBaseUrl();
        $realHostname = parse_url($baseUrl, PHP_URL_HOST);
        return $realHostname;
    }

    /**
     * @return string
     */
    private function getInternalHostname(): string
    {
        $internalBaseUrl = $this->siteRecord->getExtra("internalBaseUrl") ?? $this->getBaseUrl();
        $internalHostname = parse_url($internalBaseUrl, PHP_URL_HOST);
        return $internalHostname;
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
