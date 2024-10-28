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
        $clusterId = $cluster->getClusterID();
        $url = preg_match("/^cl[123456]00[0-9]{2}$/", $clusterId)
            ? "https://data.{$clusterId}.vanilladev.com"
            : "http://data.{$clusterId}.service.consul";

        parent::__construct($url);
        $this->setDefaultHeaders([
            "X-Access-Token" => $cluster->getSecret(),
            "Content-Type" => "application/json",
            "verifyPeer" => false,
        ]);
    }
}
