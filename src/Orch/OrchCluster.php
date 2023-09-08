<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Orch;

use Garden\Sites\Clients\OrchClusterClient;
use Garden\Sites\Cluster;

/**
 * Implementation of a cluster loaded from orchestration.
 */
class OrchCluster extends Cluster
{
    /** @var string Secret used in communication with the cluster. */
    private string $secret;

    /**
     * Constructor.
     *
     * @param string $clusterID
     * @param string $regionID
     * @param string $secret
     */
    public function __construct(string $clusterID, string $regionID, string $secret)
    {
        $this->secret = $secret;
        parent::__construct($clusterID, $regionID);
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
