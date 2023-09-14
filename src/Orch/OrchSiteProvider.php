<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Orch;

use Garden\Http\CurlHandler;
use Garden\Sites\Clients\OrchHttpClient;
use Garden\Sites\Cluster;
use Garden\Sites\Exceptions\ConfigLoadingException;
use Garden\Sites\SiteProvider;
use Garden\Sites\SiteRecord;
use Garden\Utils\ArrayUtils;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends SiteProvider<OrchSite, OrchCluster>
 */
class OrchSiteProvider extends SiteProvider
{
    private OrchHttpClient $orchHttpClient;

    /**
     * Constructor.
     *
     * @param OrchHttpClient $orchHttpClient A configured orch client.
     * @param string[] $regionIDs One or more of the {@link Cluster::REGION_*} constants.
     */
    public function __construct(OrchHttpClient $orchHttpClient, array $regionIDs)
    {
        parent::__construct($regionIDs);
        $this->orchHttpClient = $orchHttpClient;
    }

    /**
     * Overridden to set the user agent.
     *
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent): void
    {
        parent::setUserAgent($userAgent);
        $this->orchHttpClient->setUserAgent($this->getUserAgent());
    }

    /**
     * @inheritDoc
     */
    protected function loadAllSiteRecords(): array
    {
        $apiSites = $this->orchHttpClient->get("/site/all")->getBody()["sites"];

        $siteRecordsBySiteID = [];
        foreach ($apiSites as $apiSite) {
            $site = new SiteRecord(
                $apiSite["siteid"],
                $apiSite["accountid"],
                $apiSite["cluster"],
                "https://" . $apiSite["baseurl"],
            );
            $siteRecordsBySiteID[$site->getSiteID()] = $site;
        }
        return $siteRecordsBySiteID;
    }

    /**
     * @inheritDoc
     */
    public function getSite(int $siteID): OrchSite
    {
        $siteRecord = $this->getSiteRecord($siteID);
        $site = new OrchSite($siteRecord, $this);
        return $site;
    }

    /**
     * @inheritDoc
     */
    public function loadAllClusters(): array
    {
        $apiClusters = $this->orchHttpClient->get("/cluster/all")->getBody()["clusters"];

        $result = [];
        foreach ($apiClusters as $apiCluster) {
            switch ($apiCluster["CloudZone"]) {
                case "sfo":
                case "sjc":
                    $region = Cluster::REGION_SJC1_PROD1;
                    break;
                case "ams":
                case "ams-1":
                    $region = Cluster::REGION_AMS1_PROD1;
                    break;
                case "mtl":
                case "yul":
                    if ($apiCluster["Network"] === "production") {
                        $region = Cluster::REGION_YUL1_PROD1;
                        break;
                    } else {
                        $region = Cluster::REGION_YUL1_DEV1;
                        break;
                    }
                default:
                    // Ignore this cluster.
                    continue 2;
            }

            $cluster = new OrchCluster($apiCluster["ClusterID"], $region, $apiCluster["ApiToken"]);
            $result[$cluster->getClusterID()] = $cluster;
        }

        return $result;
    }

    /**
     * Fetch a cluster's configuration. 5 minute cache time applied.
     *
     * @param OrchCluster $cluster
     *
     * @return array
     */
    public function getClusterConfig(OrchCluster $cluster): array
    {
        $cacheKey = "orch.cluster.config.{$cluster->getClusterID()}";
        $clusterConfig = $this->cache->get($cacheKey, function (ItemInterface $item) use ($cluster) {
            $item->expiresAfter(60 * 5); // 5 minute cache time.

            $response = $cluster->getClient()->get("/cluster/configuration");
            return $response->getBody()["configuration"] ?? [];
        });
        return $clusterConfig;
    }

    /**
     * Fetch a site config. 1 minute cache time is applied.
     *
     * @param int $siteID The siteID.
     *
     * @return array The config.
     */
    public function getSiteConfig(int $siteID): array
    {
        $cacheKey = "orchSites.config.{$siteID}";
        $config = $this->cache->get($cacheKey, function (ItemInterface $item) use ($siteID) {
            $item->expiresAfter(60); // 1 minute cache time.

            $response = $this->orchHttpClient->get("/site/context/get", [
                "siteid" => $siteID,
            ]);
            $responseBody = $response->getBody();

            $config = $responseBody["context"]["config"] ?? null;

            if (empty($config)) {
                throw new ConfigLoadingException("Orchestration failed to return a site config for site {$siteID}");
            }

            // Kludge. Why isn't this injected into the site config?
            $kludgedEsSecret = $responseBody["context"]["site"]["secret"] ?? null;
            if (!empty($kludgedEsSecret)) {
                ArrayUtils::setByPath("Inf.SearchApi.Secret", $config, $kludgedEsSecret);
            }

            return $config;
        });

        return $config;
    }

    /**
     * @return OrchHttpClient
     * @internal For testing only.
     */
    public function getOrchHttpClient(): OrchHttpClient
    {
        return $this->orchHttpClient;
    }
}
