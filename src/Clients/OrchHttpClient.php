<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Clients;

use Garden\Http\HttpClient;

/**
 * HTTP Client for communication with vanilla orchestration.
 */
class OrchHttpClient extends HttpClient
{
    public function __construct(string $orchBaseUrl, string $accessToken)
    {
        parent::__construct($orchBaseUrl);
        $this->setThrowExceptions(true);
        $this->setDefaultHeader("X-Access-Token", $accessToken);
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
        $baseUrl = $this->getBaseUrl();
        $parsed = parse_url($baseUrl);
        $hostname = $parsed["host"] ?? "";
        $parsed["host"] = $ipAddress;
        $newBaseUrl = $this->unparseUrl($parsed);
        $this->setBaseUrl($newBaseUrl);
        $this->setDefaultHeader("Host", $hostname);
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

    /**
     * Inverse of `parse_url()`.
     *
     * @param array $parsedUrl Return of {@link parse_url()}
     *
     * @return string
     */
    private function unparseUrl(array $parsedUrl): string
    {
        $scheme = isset($parsedUrl["scheme"]) ? $parsedUrl["scheme"] . "://" : "";

        $host = $parsedUrl["host"] ?? "";

        $port = isset($parsedUrl["port"]) ? ":" . $parsedUrl["port"] : "";

        $user = $parsedUrl["user"] ?? "";

        $pass = isset($parsedUrl["pass"]) ? ":" . $parsedUrl["pass"] : "";

        $pass = $user || $pass ? "$pass@" : "";

        $path = $parsedUrl["path"] ?? "";

        $query = isset($parsedUrl["query"]) ? "?" . $parsedUrl["query"] : "";

        $fragment = isset($parsedUrl["fragment"]) ? "#" . $parsedUrl["fragment"] : "";

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}
