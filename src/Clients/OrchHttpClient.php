<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Clients;

use Garden\Http\HttpClient;
use Garden\Sites\Utils\HttpUtils;

/**
 * HTTP Client for communication with vanilla orchestration.
 */
class OrchHttpClient extends HttpClient
{
    /**
     * @param string $orchBaseUrl
     * @param string $accessToken
     */
    public function __construct(string $orchBaseUrl, string $accessToken, string|null $forcedHostname = null)
    {
        parent::__construct($orchBaseUrl);
        $this->setThrowExceptions(true);
        $this->setDefaultHeader("content-type", "application/json");
        $this->setDefaultHeader("X-Access-Token", $accessToken);
        if ($forcedHostname !== null) {
            $this->forceIpAddress($forcedHostname);
        }
    }

    /**
     * Sometimes when calling orchestration from localhost it is necessary to force requests to direct to a particular
     * IP address in order to force VPN resolution. Call this method with your forced IP address to configure this.
     *
     * @param string $ipAddress
     *
     * @return $this For method chaining.
     */
    public function forceIpAddress(string $ipAddress): OrchHttpClient
    {
        HttpUtils::forceForceHostname($this, $ipAddress);
        return $this;
    }

    /**
     * Set the user agent for the orchestration client to use.
     *
     * @param string $userAgent
     *
     * @return $this For method chaining.
     */
    public function setUserAgent(string $userAgent): OrchHttpClient
    {
        $this->setDefaultHeader("User-Agent", $userAgent);
        return $this;
    }
}
