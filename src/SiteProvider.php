<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

use Garden\Sites\Exceptions\ClusterNotFoundException;
use Garden\Sites\Exceptions\SiteNotFoundException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class for loading sites and clusters.
 *
 * @template-covariant  TSite of Site
 * @template-covariant  TCluster of Cluster
 */
abstract class SiteProvider
{
    protected \Symfony\Contracts\Cache\CacheInterface $cache;

    protected string $userAgent = "vanilla-sites-package";

    /** @var string[] The region sites should be loaded from. */
    protected array $regionIDs;

    /**
     * Constructor.
     *
     * @param string[] $regionIDs One or more of the {@link Cluster::REGION_*} constants
     */
    public function __construct(array $regionIDs)
    {
        $this->cache = new ArrayAdapter();
        $this->regionIDs = $regionIDs;
    }

    /**
     * Set the region we should be filtering too.
     *
     * @param string[] $regionIDs
     *
     * @return void
     */
    public function setRegionIDs(array $regionIDs): void
    {
        $this->regionIDs = $regionIDs;
    }

    /**
     * Apply a symfony cache contract.
     *
     * @param \Symfony\Contracts\Cache\CacheInterface $cache
     * @return void
     */
    public function setCache(\Symfony\Contracts\Cache\CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Get the user agent to use for all network requests originating from the provider.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Set the user agent used in network requests originating from the provider.
     *
     * @param string $userAgent
     */
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

        $filteredClusters = $this->getClusters();
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
     * Method to load and return all clusters from its original data source.
     * Results will be cached.
     *
     * @return array<string, TCluster>
     */
    abstract protected function loadAllClusters(): array;

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
        $clusters = $this->getClusters();
        $cluster = $clusters[$clusterID] ?? null;
        if ($cluster === null) {
            throw new ClusterNotFoundException($clusterID);
        }

        return $cluster;
    }

    /**
     * Get a list of clusters.
     *
     * @return array<string, TCluster>
     */
    public function getClusters(): array
    {
        $cacheKey = "allClusterRecords." . str_replace("\\", "_", get_class($this));
        $allClusters = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(60); // Short cache duration.
            $loadedClusters = $this->loadAllClusters();

            return $loadedClusters;
        });

        $filteredClusters = array_filter($allClusters, function (Cluster $cluster) {
            return in_array($cluster->getRegionID(), $this->regionIDs);
        });

        return $filteredClusters;
    }
}
