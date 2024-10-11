<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Dashboard;

use Garden\Sites\Clients\DashboardHttpClient;
use Garden\Sites\Cluster;
use Garden\Sites\Exceptions\BadApiCredentialsException;
use Garden\Sites\Exceptions\ConfigLoadingException;
use Garden\Sites\SiteProvider;
use Garden\Sites\SiteRecord;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends SiteProvider<DashboardSite, DashboardCluster>
 */
class DashboardSiteProvider extends SiteProvider
{
    /**
     * Constructor.
     *
     * @param DashboardHttpClient $dashboardHttpClient A configured orch client.
     * @param string[] $regionIDs One or more of the {@link Cluster::REGION_*} constants.
     */
    public function __construct(private DashboardHttpClient $dashboardHttpClient, array $regionIDs)
    {
        parent::__construct($regionIDs);
    }

    /**
     * @return DashboardHttpClient
     */
    public function getDashboardHttpClient(): DashboardHttpClient
    {
        return $this->dashboardHttpClient;
    }

    /**
     * Overridden to set the user agent.
     *
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent): void
    {
        parent::setUserAgent($userAgent);
        $this->dashboardHttpClient->setUserAgent($this->getUserAgent());
    }

    /**
     * @inheritDoc
     */
    protected function loadAllSiteRecords(): array
    {
        $apiSites = $this->dashboardHttpClient
            ->get("/api/sites", [
                "regionID" => $this->regionIDs,
            ])
            ->getBody();

        $siteRecordsBySiteID = [];
        foreach ($apiSites as $apiSite) {
            $site = new SiteRecord(
                $apiSite["siteID"],
                $apiSite["accountID"],
                $apiSite["multisiteID"],
                $apiSite["clusterID"],
                $apiSite["baseUrl"],
            );
            $site->setExtra("internalBaseUrl", $apiSite["internalBaseUrl"]);
            $site->setExtra("internalHeaders", $apiSite["internalHeaders"]);
            $siteRecordsBySiteID[$site->getSiteID()] = $site;
        }

        return $siteRecordsBySiteID;
    }

    /**
     * @inheritDoc
     */
    public function getSite(int $siteID): DashboardSite
    {
        $siteRecord = $this->getSiteRecord($siteID);
        $site = new DashboardSite($siteRecord, $this);

        return $site;
    }

    /**
     * @inheritDoc
     */
    public function loadAllClusters(): array
    {
        $apiClusters = $this->dashboardHttpClient->get("/api/clusters")->getBody();

        $result = [];
        foreach ($apiClusters as $apiCluster) {
            $cluster = new DashboardCluster($apiCluster["clusterID"], $apiCluster["regionID"]);
            $result[$cluster->getClusterID()] = $cluster;
        }

        return $result;
    }

    /**
     * Fetch a site config. 1 minute cache time is applied.
     *
     * @param int $siteID The siteID.
     *
     * @return array The config.
     * @throws ConfigLoadingException
     */
    public function getSiteConfig(int $siteID): array
    {
        $config = $this->getSiteDetails($siteID)["config"] ?? [];
        if (empty($config)) {
            throw new ConfigLoadingException("Orchestration failed to return a site config for site {$siteID}");
        }
        return $config;
    }

    /**
     * Get a system access token for a site.
     *
     * @param int $siteID
     * @return string
     * @throws ConfigLoadingException
     */
    public function getSiteSystemAccessToken(int $siteID): string
    {
        $systemAccessToken = $this->getSiteDetails($siteID)["systemAccessToken"] ?? "";
        if (empty($systemAccessToken)) {
            throw new BadApiCredentialsException(
                "Orchestration failed to return a system access token for site {$siteID}",
            );
        }
        return $systemAccessToken;
    }

    /**
     * Fetch site details either from cache or from orch.
     *
     * @param int $siteID The siteID.
     *
     * @return array{config: array, site: array, systemAccessToken: string} The config.
     */
    public function getSiteDetails(int $siteID): array
    {
        $cacheKey = "dashboardSites.details.{$siteID}";
        $config = $this->cache->get($cacheKey, function (ItemInterface $item) use ($siteID): array {
            $item->expiresAfter(60); // 1 minute cache time.

            $responseBody = $this->dashboardHttpClient->get("/api/sites/{$siteID}")->getBody();
            return $responseBody;
        });

        return $config;
    }
}
