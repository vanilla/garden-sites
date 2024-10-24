<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Dashboard;

use Garden\Sites\Clients\OrchClusterClient;
use Garden\Sites\Cluster;

/**
 * Implementation of a cluster loaded from orchestration.
 */
class DashboardCluster extends Cluster
{
    /**
     * Constructor.
     *
     * @param string $clusterID
     * @param string $regionID
     */
    public function __construct(string $clusterID, string $regionID)
    {
        parent::__construct($clusterID, $regionID);
    }
}
