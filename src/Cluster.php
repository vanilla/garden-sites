<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

use Garden\Sites\Orch\OrchCluster;

/**
 * Class representing a cluster of sites.
 */
class Cluster
{
    private string $clusterID;
    private string $region;
    private string $network;

    /**
     * @param string $clusterID
     * @param string $region
     * @param string $network
     */
    public function __construct(string $clusterID, string $region, string $network)
    {
        $this->clusterID = $clusterID;
        $this->region = $region;
        $this->network = $network;
    }

    /**
     * Get a cluster's ID.
     *
     * @return string
     */
    public function getClusterID(): string
    {
        return $this->clusterID;
    }

    /**
     * Get a string identifying the region that the cluster belongs to. This is normally an acronym for some geo-located datacenter.
     *
     * Examples:
     * - mtl - {@link OrchCluster::REGION_YUL1}
     * - sjc / sfo - {@link OrchCluster::REGION_SJC}
     * - ams - {@link OrchCluster::REGION_AMS1}
     *
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * Get a string identifying the type of network the cluster runs on.
     *
     * Examples
     * - production {@link OrchCluster::NETWORK_PRODUCTION}
     * - development {@link OrchCluster::NETWORK_DEVELOPMENT}
     * - localhost
     *
     * @return string
     */
    public function getNetwork(): string
    {
        return $this->network;
    }
}
