<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Local;

use Garden\Sites\Cluster;

/**
 * Implements a single local cluster.
 */
class LocalCluster extends Cluster
{
    public const DEFAULT_CLUSTER_ID = "cl00000";

    public function __construct(string $clusterID = self::DEFAULT_CLUSTER_ID)
    {
        parent::__construct($clusterID, Cluster::REGION_LOCALHOST);
    }
}
