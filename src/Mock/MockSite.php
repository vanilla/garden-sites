<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Mock;

use Garden\Sites\Local\LocalCluster;
use Garden\Sites\Site;
use Garden\Sites\SiteRecord;
use Garden\Utils\ArrayUtils;

/**
 * A mock local site for testing purposes.
 *
 * @extends Site<MockSite, MockCluster>
 */
class MockSite extends Site
{
    public const MOCK_CLUSTER_ID = "cl00000";

    /** @var int */
    public int $maxJobConcurrency = 25;

    /** @var string */
    protected string $baseUrl;

    /** @var array<array-key, mixed>|\ArrayAccess  */
    protected $config;

    /**
     * Constructor.
     *
     * @param string $baseUrl
     * @param int $siteID
     * @param array $config
     * @param string $clusterID
     * @param int $accountID
     * @param int|null $multisiteID
     */
    public function __construct(
        string $baseUrl,
        int $siteID = MockSiteProvider::MOCK_SITE_ID,
        array $config = [],
        string $clusterID = self::MOCK_CLUSTER_ID,
        int $accountID = 1,
        ?int $multisiteID = null
    ) {
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $siteRecord = new SiteRecord($siteID, $accountID, $multisiteID, $clusterID, $baseUrl);
        parent::__construct($siteRecord, new MockSiteProvider());
        $this->generateKey();
        $this->setSystemToken("test123");
        $this->setConfigs([
            "Vanilla.AccountID" => $accountID,
            "Vanilla.SiteID" => $siteID,
            "queue.disableFeedback" => true,
        ]);
    }

    /**
     * @param MockSiteProvider $mockSiteProvider
     * @return void
     */
    public function setSiteProvider(MockSiteProvider $mockSiteProvider): void
    {
        $this->siteProvider = $mockSiteProvider;
    }

    /**
     * @return SiteRecord
     */
    public function getSiteRecord(): SiteRecord
    {
        return $this->siteRecord;
    }

    /**
     * @inheritDoc
     */
    protected function loadSiteConfig(): array
    {
        return $this->config;
    }

    /**
     * Set config values for the site.
     *
     * @param array<string, mixed> $configs
     *
     * @return void
     */
    public function setConfigs(array $configs): void
    {
        foreach ($configs as $key => $val) {
            ArrayUtils::setByPath($key, $this->config, $val);
        }
        $this->clearConfigCache();
    }

    /**
     * Generate a random private key.
     */
    public function generateKey(): void
    {
        $privateKey = bin2hex(random_bytes(32));
        $this->setPrivateKey($privateKey);
    }

    /**
     * Set the config private key.
     *
     * @param string|null $key
     */
    public function setPrivateKey(?string $key): void
    {
        $this->config["VanillaQueue"]["Keys"]["Private"] = $key;
    }

    /**
     * Set a value for the Garden.Scheduler.Token config field.
     *
     * @param string|null $value
     * @return void
     */
    public function setSchedulerToken(?string $value): void
    {
        $this->config["Garden"]["Scheduler"]["Token"] = $value;
    }

    /**
     * Set Api Key
     *
     * @param string|null $key
     * @return void
     */
    public function setSystemToken(?string $key): void
    {
        ArrayUtils::setByPath(self::CONF_SYSTEM_ACCESS_TOKEN, $this->config, $key);
    }

    /**
     * Set Site Account id
     *
     * @param int $accountID
     * @return void
     */
    public function setAccountID(int $accountID): void
    {
        $this->config["Vanilla"]["AccountID"] = $accountID;
    }

    /**
     * Set Elastic Secret
     *
     * @param ?string $secret
     * @return void
     */
    public function setElasticSecret(?string $secret): void
    {
        $this->config["ElasticDev"]["Secret"] = $secret;
    }

    /**
     * Set the site Plugins.
     *
     * @param array $plugins
     * @return void
     */
    public function setPlugins(array $plugins): void
    {
        $this->config["Plugins"] = $plugins;
    }

    /**
     * @return string
     */
    public function getSearchServiceBaseUrl(): string
    {
        return "https://fake-search-service.test";
    }
}
