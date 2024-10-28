<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Clients;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Sites\Utils\HttpUtils;

/**
 * HTTP client for the management API.
 */
class DashboardHttpClient extends HttpClient
{
    private string $userAgent = "garden-sites";

    /**
     * @param string $orchBaseUrl
     * @param string $secret
     * @param string|null $forcedHostname Use to force a hostname on to the HTTP client.
     */
    public function __construct(string $orchBaseUrl, string $secret, ?string $forcedHostname = null)
    {
        parent::__construct($orchBaseUrl);
        $this->setDefaultHeader("content-type", "application/json");
        $this->setThrowExceptions(true);

        if ($forcedHostname !== null) {
            HttpUtils::forceForceHostname($this, $forcedHostname);
        }

        $this->addMiddleware(function (HttpRequest $request, callable $next) use ($secret): HttpResponse {
            // Generate a JWT.
            $jwt = \Firebase\JWT\JWT::encode(
                [
                    "iss" => $this->userAgent,
                    "iat" => time(),
                    "exp" => time() + 60,
                ],
                $secret,
                "HS512",
            );

            $token = "sys:{$this->userAgent}:$jwt";
            // Set the Authorization header.
            $request->setHeader("Authorization", "Bearer $token");

            $response = $next($request);
            return $response;
        });
    }

    /**
     * Set the user agent for the orchestration client to use.
     *
     * @param string $userAgent
     *
     * @return $this For method chaining.
     */
    public function setUserAgent(string $userAgent): DashboardHttpClient
    {
        $this->userAgent = $userAgent;
        $this->setDefaultHeader("User-Agent", $userAgent);
        return $this;
    }
}
