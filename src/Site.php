<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Garden\Http\CurlHandler;
use Garden\Http\HttpHandlerInterface;
use Garden\Sites\Clients\SiteHttpClient;
use Garden\Sites\Exceptions\BadApiCredentialsException;
use Garden\Sites\Exceptions\ClusterNotFoundException;
use Garden\Utils\ArrayUtils;
use Garden\Utils\ContextException;

/**
 * Interface classes representing a site.
 *
 * @template-covariant  TSite of Site
 * @template-covariant  TCluster of Cluster
 */
abstract class Site implements \JsonSerializable
{
    const CONF_SYSTEM_ACCESS_TOKEN = "APIv2.SystemAccessToken";
    const CONF_JWT_SECRET = "Context.Secret";

    protected SiteRecord $siteRecord;

    /** @var SiteProvider<TSite, TCluster> */
    protected SiteProvider $siteProvider;

    protected HttpHandlerInterface $httpHandler;

    /** @var array|null */
    protected ?array $configCache = null;

    /**
     * @param SiteRecord $siteRecord
     * @param SiteProvider<TSite, TCluster> $siteProvider
     */
    public function __construct(SiteRecord $siteRecord, SiteProvider $siteProvider)
    {
        $this->siteRecord = $siteRecord;
        $this->siteProvider = $siteProvider;
        $this->httpHandler = new CurlHandler();
    }

    /**
     * Return a nested array of the site's config.
     *
     * @return array<string, mixed>
     */
    abstract protected function loadSiteConfig(): array;

    /**
     * Get a site's ID.
     *
     * @return int
     */
    public function getSiteID(): int
    {
        return $this->siteRecord->getSiteID();
    }

    /**
     * Get the accountID for the site.
     *
     * @return int
     */
    public function getAccountID(): int
    {
        return $this->siteRecord->getAccountID();
    }

    /**
     * Get the multisiteID for the site. Only hub/node sites have a multisiteID.
     *
     * @return int|null
     */
    public function getMultisiteID(): ?int
    {
        return $this->siteRecord->getMultisiteID();
    }

    /** Get the id of the cluster this site runs on.
     *
     * @return string
     */
    public function getClusterID(): string
    {
        return $this->siteRecord->getClusterID();
    }

    /**
     * @return TCluster
     *
     * @throws ClusterNotFoundException
     */
    public function getCluster(): Cluster
    {
        return $this->siteProvider->getCluster($this->getClusterID());
    }

    /**
     * Get a site's base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->siteRecord->getBaseUrl();
    }

    /**
     * Clear the local config cache.
     *
     * @return void
     */
    public function clearConfigCache(): void
    {
        $this->configCache = null;
    }

    /**
     * Get a site's config value by key.
     *
     * @param string $configKey Dot notation config key.
     * @param mixed|null $fallback Fallback value.
     * @return mixed
     */
    public function getConfigValueByKey(string $configKey, mixed $fallback = null): mixed
    {
        if ($this->configCache === null) {
            $this->configCache = $this->loadSiteConfig();
        }

        $result = ArrayUtils::getByPath($configKey, $this->configCache, $fallback);
        return $result;
    }

    /**
     * Get an HTTP Client for communicating with the site.
     *
     * You likely will want to chain this with an authentication method.
     *
     * @example
     * $this->httpClient()->withSystemAuth()->get();
     */
    public function httpClient(): SiteHttpClient
    {
        $client = new SiteHttpClient($this, $this->httpHandler);
        $client->setDefaultHeader("User-Agent", $this->siteProvider->getUserAgent());
        return $client;
    }

    /**
     * Check if the system access token has been configured for the site.
     *
     * @return bool
     */
    public function hasSystemAccessToken(): bool
    {
        try {
            $this->getSystemAccessToken();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Sites secure system token for Api call
     *
     * @return string
     *
     * @throws BadApiCredentialsException
     */
    public function getSystemAccessToken(): string
    {
        $secret = $this->getConfigValueByKey(self::CONF_JWT_SECRET);
        if ($secret === null) {
            throw new BadApiCredentialsException("Secret not found in site config", 500, [
                "siteID" => $this->getSiteID(),
            ]);
        }

        $token = JWT::encode(
            [
                "svc" => $this->siteProvider->getUserAgent(),
                "iat" => time(),
                "exp" => time() + 60 * 5,
            ],
            $secret,
            "HS512",
        );
        return "vnla_sys.{$token}";
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->siteRecord->jsonSerialize();
    }

    /**
     * Get the hostname of the proper search service to use for the cluster.
     *
     * @return string
     */
    public function getQueueServiceBaseUrl(): string
    {
        $configOverride = $this->getConfigValueByKey("VanillaQueue.BaseUrl", null);
        if (!empty($configOverride)) {
            return $configOverride;
        }

        switch ($this->getCluster()->getRegionID()) {
            case Cluster::REGION_LOCALHOST:
                return "http://queue.vanilla.localhost";
            case Cluster::REGION_YUL1_DEV1:
                return "https://yul1-vanillaqueue-dev1.v-fabric.net";
            case Cluster::REGION_YUL1_PROD1:
                return "https://yul1-vanillaqueue-prod1.v-fabric.net";
            case Cluster::REGION_AMS1_PROD1:
                return "https://ams1-vanillaqueue-prod1.v-fabric.net";
            case Cluster::REGION_SJC1_PROD1:
                return "https://sjc1-vanillaqueue-prod1.v-fabric.net";
            default:
                return "http://web.vanilla-queue.service.consul";
        }
    }

    /**
     * Get the hostname of the proper queue service to use for the cluster.
     *
     * @return string
     */
    public function getSearchServiceBaseUrl(): string
    {
        $configOverride = $this->getConfigValueByKey("Inf.SearchApi.URL", null);
        if (!empty($configOverride)) {
            return $configOverride;
        }

        switch ($this->getCluster()->getRegionID()) {
            case Cluster::REGION_LOCALHOST:
            case Cluster::REGION_YUL1_DEV1: // This is temporary until we have a localhost version of the search api.
                return "https://yul1-dev1-vanillasearch-api.v-fabric.net";
            case Cluster::REGION_YUL1_PROD1:
            case Cluster::REGION_SJC1_PROD1: // Temporarily using the YUL prod instance until https://higherlogic.atlassian.net/browse/PV-229 is completed.
            case Cluster::REGION_AMS1_PROD1: // Temporarily using YUL prod instance into AMS is re-provisioned https://higherlogic.atlassian.net/browse/PV-323
                return "https://yul1-vanillasearch-prod1-api.v-fabric.net";
            default:
                return "http://web.vanilla-search.service.consul";
        }
    }
}
