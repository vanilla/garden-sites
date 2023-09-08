<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Clients;

use Garden\Http\HttpClient;
use Garden\Sites\Orch\OrchCluster;

/**
 * HTTP client for making requests to a cluster's API.
 */
class OrchClusterClient extends HttpClient
{
    public function __construct(OrchCluster $cluster)
    {
        parent::__construct("https://data.{$cluster->getClusterID()}.vanilladev.com");
        $this->setDefaultHeaders([
            "X-Access-Token" => $cluster->getSecret(),
            "Content-Type" => "application/json",
            "verifyPeer" => false,
        ]);
    }
}
