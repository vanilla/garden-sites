<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Exceptions;

use Garden\Utils\ContextException;

class SiteNotFoundException extends ContextException
{
    public function __construct(int $siteID)
    {
        parent::__construct("Site not found. siteID: {$siteID}", 404, ["siteID" => $siteID]);
    }
}
