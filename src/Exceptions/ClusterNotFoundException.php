<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Exceptions;

use Garden\Utils\ContextException;

/**
 * Exception thrown when a cluster could not be loaded.
 */
class ClusterNotFoundException extends ContextException
{
    public function __construct(string $clusterID)
    {
        parent::__construct("Cluster not found. clusterID: {$clusterID}", 404, ["clusterID" => $clusterID]);
    }
}
