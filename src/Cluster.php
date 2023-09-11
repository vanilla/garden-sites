<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

/**
 * Class representing a cluster of sites.
 */
class Cluster
{
    public const REGION_YUL1_DEV1 = "yul1-dev1";
    public const REGION_YUL1_PROD1 = "yul1-prod1";
    public const REGION_AMS1_PROD1 = "ams1-prod1";
    public const REGION_SJC1_PROD1 = "sjc1-prod1";
    public const REGION_LOCALHOST = "localhost";
    public const VALID_REGIONS = [
        self::REGION_YUL1_DEV1,
        self::REGION_YUL1_PROD1,
        self::REGION_AMS1_PROD1,
        self::REGION_SJC1_PROD1,
        self::REGION_LOCALHOST,
    ];

    private string $clusterID;
    private string $regionID;

    /**
     * @param string $clusterID
     * @param string $regionID One of {@link self::REGION*} constants.
     */
    public function __construct(string $clusterID, string $regionID)
    {
        $this->clusterID = $clusterID;
        $this->regionID = $regionID;
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
     * One of {@link self::VALID_REGIONS}
     *
     * @return string
     */
    public function getRegionID(): string
    {
        return $this->regionID;
    }
}
