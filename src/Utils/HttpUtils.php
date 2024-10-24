<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Utils;

use Garden\Http\HttpClient;

/**
 * Utilities for HttpClients.
 */
class HttpUtils
{
    /**
     * Given an HttpClient, replace it's hostname with the provided one and set the Host header to the original hostname.
     *
     * @param HttpClient $client
     * @param string $forcedHostname
     * @return void
     */
    public static function forceForceHostname(HttpClient $client, string $forcedHostname)
    {
        $baseUrl = $client->getBaseUrl();
        $parsed = parse_url($baseUrl);
        $hostname = $parsed["host"] ?? "";
        $parsed["host"] = $forcedHostname;
        $newBaseUrl = self::unparseUrl($parsed);
        $client->setBaseUrl($newBaseUrl);
        $client->setDefaultHeader("Host", $hostname);
    }

    /**
     * Inverse of `parse_url()`.
     *
     * @param array $parsedUrl Return of {@link parse_url()}
     *
     * @return string
     */
    private static function unparseUrl(array $parsedUrl): string
    {
        $scheme = isset($parsedUrl["scheme"]) ? $parsedUrl["scheme"] . "://" : "";

        $host = $parsedUrl["host"] ?? "";

        $port = isset($parsedUrl["port"]) ? ":" . $parsedUrl["port"] : "";

        $user = $parsedUrl["user"] ?? "";

        $pass = isset($parsedUrl["pass"]) ? ":" . $parsedUrl["pass"] : "";

        $pass = $user || !empty($pass) ? "$pass@" : "";

        $path = $parsedUrl["path"] ?? "";

        $query = isset($parsedUrl["query"]) ? "?" . $parsedUrl["query"] : "";

        $fragment = isset($parsedUrl["fragment"]) ? "#" . $parsedUrl["fragment"] : "";

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}
