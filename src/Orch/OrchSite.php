<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Orch;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Sites\Clients\SiteHttpClient;
use Garden\Sites\Cluster;
use Garden\Sites\Site;
use Garden\Sites\SiteRecord;
use Garden\Utils\ArrayUtils;

/**
 * @extends Site<OrchSite, OrchCluster>
 * @property OrchSiteProvider $siteProvider
 */
class OrchSite extends Site
{
    /**
     * Constructor.
     *
     * @param SiteRecord $siteRecord
     * @param OrchSiteProvider $siteProvider
     */
    public function __construct(SiteRecord $siteRecord, OrchSiteProvider $siteProvider)
    {
        parent::__construct($siteRecord, $siteProvider);
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
        $clusterRouterHostname = $this->getClusterRouterHostname();
        if ($clusterRouterHostname) {
            $kludgedBaseUrl = $this->replaceHostnameInUrl($this->getBaseUrl());
            $httpClient->setBaseUrl($kludgedBaseUrl);
            $httpClient->setDefaultHeader("Host", $realHostname);

            $httpClient->addMiddleware(function (HttpRequest $request, callable $next) use (
                $realHostname
            ): HttpResponse {
                $request->setUrl($this->replaceHostnameInUrl($request->getUrl()));
                $request->setHeader("Host", $realHostname);
                return $next($request);
            });
        }

        return $httpClient;
    }

    /**
     * @inheritDoc
     */
    protected function loadSiteConfig(): array
    {
        $cluster = $this->getCluster();
        $clusterConfig = $this->siteProvider->getClusterConfig($cluster)["vanilla"] ?? [];
        $siteConfig = $this->siteProvider->getSiteConfig($this->getSiteID());

        $mergedConfig = ArrayUtils::mergeRecursive($clusterConfig, $siteConfig, function ($a, $b) {
            return $b;
        });
        return $mergedConfig;
    }

    /**
     * Given a url, try to replace it's base url so it routes with the cluster router.
     *
     * @param string $url
     * @return string
     */
    public function replaceHostnameInUrl(string $url): string
    {
        $clusterRouterHostname = $this->getClusterRouterHostname();
        if ($clusterRouterHostname === null) {
            return $url;
        }

        $realHostname = $this->getRealHostName();
        $kludgedUrl = str_replace($realHostname, $clusterRouterHostname, $url);
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
     * @return string|null
     */
    public function getClusterRouterHostname(): ?string
    {
        switch ($this->getCluster()->getRegionID()) {
            case Cluster::REGION_AMS1_PROD1:
                return "haproxy-router.ams1-routing-prod1.vanilladev.com";
            case Cluster::REGION_SJC1_PROD1:
                return "haproxy-router.sjc1-routing-prod1.vanilladev.com";
            case Cluster::REGION_YUL1_PROD1:
                return "haproxy-router.yul1-routing-prod1.vanilladev.com";
            case Cluster::REGION_YUL1_DEV1:
                return "haproxy-router.yul1-routing-dev1.vanilladev.com";
        }

        return null;
    }
}
