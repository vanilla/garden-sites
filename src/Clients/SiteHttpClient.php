<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Clients;

use Garden\Http\HttpClient;
use Garden\Http\HttpHandlerInterface;
use Garden\Http\HttpResponse;
use Garden\Sites\Cluster;
use Garden\Sites\Site;

/**
 * HTTP Client for making network requests to a site.
 */
class SiteHttpClient extends HttpClient
{
    private const HEADER_AUTH = "Authorization";

    /**
     * @var Site
     */
    private Site $site;

    /**
     * @param Site $site
     * @param HttpHandlerInterface|null $handler
     */
    public function __construct(Site $site, HttpHandlerInterface $handler = null)
    {
        $this->site = $site;
        parent::__construct($site->getBaseUrl(), $handler);

        $this->setBaseUrl($this->getBaseUrl());
        $this->setDefaultHeader("content-type", "application/json");
        $this->setDefaultOption("timeout", 20);
        $this->setThrowExceptions(true);
    }

    /**
     * Get a copy of the client.
     * This copy will use an access token to communicate with the site as the System user.
     *
     * @return SiteHttpClient
     */
    public function withSystemAuth(): SiteHttpClient
    {
        $client = clone $this;
        $client->setDefaultHeader(self::HEADER_AUTH, "Bearer " . $client->site->getSystemAccessToken());
        return $client;
    }

    /**
     * Get a copy of the client.
     * This copy will not have any authentication method configured.
     *
     * @return SiteHttpClient
     */
    public function withNoAuth(): SiteHttpClient
    {
        $client = clone $this;
        unset($client->defaultHeaders[self::HEADER_AUTH]);
        return $client;
    }

    /**
     * Run a callback payload from a long-runner response.
     */
    public function runCallbackPayload(string $callbackPayload): HttpResponse
    {
        // Clear the default auth header.
        $response = $this->withNoAuth()->post(
            "/api/v2/calls/run",
            $callbackPayload,
            [
                "content-type" => "application/system+jwt",
            ],
            [
                "timeout" => 25,
                "throw" => false,
            ],
        );
        return $response;
    }
}
