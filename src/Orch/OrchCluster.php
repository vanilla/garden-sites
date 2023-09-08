<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Orch;

use Garden\Sites\Clients\OrchClusterClient;
use Garden\Sites\Cluster;

class OrchCluster extends Cluster
{
    public const REGION_YUL1 = "mtl";
    public const REGION_AMS1 = "ams-1";
    public const REGION_SJC = "sfo";

    public const NETWORK_PRODUCTION = "production";
    public const NETWORK_DEVELOPMENT = "development";

    /** @var string Secret used in communication with the cluster. */
    private string $secret;

    public function __construct(string $clusterID, string $region, string $network, string $secret)
    {
        $this->secret = $secret;
        parent::__construct($clusterID, $region, $network);
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Get an authenticated cluster http client.
     *
     * @return OrchClusterClient
     */
    public function getClient(): OrchClusterClient
    {
        return new OrchClusterClient($this);
    }
}
