<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Exceptions;

use Garden\Sites\Cluster;
use Garden\Utils\ContextException;

/**
 * Exception thrown when we detect an invalid region.
 */
class InvalidRegionException extends ContextException
{
    /**
     * @param string $invalidRegion
     */
    public function __construct(string $invalidRegion)
    {
        $message =
            "Invalid region '$invalidRegion' detected. Valid regions are " .
            implode(", ", Cluster::VALID_REGIONS) .
            ".";
        parent::__construct($message, 500, [
            "invalidRegion" => $invalidRegion,
            "validRegions" => Cluster::VALID_REGIONS,
        ]);
    }
}
