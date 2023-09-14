<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Mock;

use Garden\Sites\Cluster;

/**
 * Cluster for usage in mocks.
 */
class MockCluster extends Cluster
{
    /**
     * @param string $clusterID
     */
    public function __construct(string $clusterID)
    {
        parent::__construct($clusterID, Cluster::REGION_MOCK);
    }
}
