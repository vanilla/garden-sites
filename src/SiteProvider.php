<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

use Garden\Sites\Exceptions\ClusterNotFoundException;
use Garden\Sites\Exceptions\SiteNotFoundException;
use Garden\Sites\Orch\OrchCluster;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @template TSite of Site
 * @template TCluster of Cluster
 */
abstract class SiteProvider
{
    protected CacheInterface $cache;

    protected string $userAgent = "vanilla-sites-package";

    /** @var string The region sites should be loaded from. */
    protected string $region;

    /** @var string The network sites should be loaded from. */
    protected string $network;

    /**
     * Constructor.
     */
    public function __construct(string $region, string $network)
    {
        $this->cache = new ArrayAdapter();
        $this->region = $region;
        $this->network = $network;
    }

    /**
     * Set the region and network we should be filtering too.
     *
     * @param string $region
     * @param string $network
     *
     * @return void
     */
    public function setRegionAndNetwork(string $region, string $network): void
    {
        $this->region = $region;
        $this->network = $network;
    }

    /**
     * Apply a symfony cache contract.
     *
     * @param CacheInterface $cache
     * @return void
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return array<int, SiteRecord>
     */
    abstract protected function loadAllSiteRecords(): array;

    /**
     * @return array<int, SiteRecord>
     */
    protected function getAllSiteRecords(): array
    {
        $cacheKey = "allSiteRecords_" . str_replace("\\", "_", get_class($this));
        $result = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(60); // Short cache duration.
            $loadedSiteRecords = $this->loadAllSiteRecords();

            return $loadedSiteRecords;
        });

        $filteredClusters = $this->getClusters($this->region, $this->network);
        $validClusterIDs = [];
        foreach ($filteredClusters as $filteredCluster) {
            $validClusterIDs[] = $filteredCluster->getClusterID();
        }

        $result = array_filter($result, function (SiteRecord $siteRecord) use ($validClusterIDs) {
            return in_array($siteRecord->getClusterID(), $validClusterIDs);
        });

        return $result;
    }

    /**
     * Lookup a site, returning a minimal site record.
     *
     * @param int $siteID
     * @return SiteRecord
     *
     * @throws SiteNotFoundException
     */
    protected function getSiteRecord(int $siteID): SiteRecord
    {
        $allSites = $this->getAllSiteRecords();
        $site = $allSites[$siteID] ?? null;
        if ($site === null) {
            throw new SiteNotFoundException($siteID);
        }

        return $site;
    }

    /**
     * @return array<int, TSite>
     */
    public function getSites(): array
    {
        $allSites = $this->getAllSiteRecords();
        $results = [];
        foreach ($allSites as $siteID => $_) {
            try {
                $results[$siteID] = $this->getSite($siteID);
            } catch (SiteNotFoundException $ex) {
                // Nothing
            }
        }
        return $results;
    }

    /**
     * Get a full site instance.
     *
     * @param int $siteID
     * @return TSite
     *
     * @throws SiteNotFoundException
     */
    abstract public function getSite(int $siteID): Site;

    /**
     * @return array<string, TCluster>
     */
    abstract protected function loadAllClusters(): array;

    /**
     * @return array<string, TCluster>
     */
    public function getAllClusters(): array
    {
        $cacheKey = "allClusterRecords." . str_replace("\\", "_", get_class($this));
        $result = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(60); // Short cache duration.
            $loadedClusters = $this->loadAllClusters();

            return $loadedClusters;
        });
        return $result;
    }

    /**
     * Get a cluster by its ID.
     *
     * @param string $clusterID
     *
     * @return TCluster
     *
     * @throws ClusterNotFoundException
     */
    public function getCluster(string $clusterID): Cluster
    {
        $clusters = $this->getAllClusters();
        $cluster = $clusters[$clusterID] ?? null;
        if ($cluster === null) {
            throw new ClusterNotFoundException($clusterID);
        }

        return $cluster;
    }

    /**
     * Get a list of clusters filtered by network and region.
     *
     * @param string $region
     * @param string $network
     *
     * @return array<string, TCluster>
     */
    protected function getClusters(string $region, string $network): array
    {
        $allClusters = $this->getAllClusters();
        $filteredClusters = array_filter($allClusters, function (Cluster $cluster) use ($region, $network) {
            return $cluster->getNetwork() === $network && $cluster->getRegion() === $region;
        });

        return $filteredClusters;
    }
}
