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
     * If given a URL containing the siteBaseUrl, remove it.
     * 
     * @inheritdoc
     */
    public function createRequest(string $method, string $uri, $body, array $headers = [], array $options = []): \Garden\Http\HttpRequest {
        // Replace the base url (it will get re-appended). The URL may have been on the site directly, but we actually want to use the configured internal URL.
        $uri = str_replace($this->site->getBaseUrl(), "", $uri);
        return parent::createRequest($method, $uri, $body, $headers, $options);
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
    public function runCallbackPayload(string $callbackPayload, array $options = []): HttpResponse
    {
        // Clear the default auth header.
        $response = $this->withNoAuth()->post(
            "/api/v2/calls/run",
            $callbackPayload,
            [
                "content-type" => "application/system+jwt",
            ],
            array_merge(
                [
                    "timeout" => 30,
                    "throw" => false,
                ],
                $options,
            ),
        );
        return $response;
    }
}
