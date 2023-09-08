<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests\Fixtures;

use Garden\Sites\Site;
use Garden\Sites\SiteRecord;
use PHPUnit\Framework\TestCase;

/**
 * Class holding expecations about sites from a provider.
 */
class ExpectedSite extends SiteRecord
{
    private array $expectedConfigs;

    public bool $expectSystemToken = true;

    public string $expectedRegion = "localhost";
    public string $expectedNetwork = "localhost";

    /**
     * @param int $siteID
     * @param int $accountID
     * @param string $clusterID
     * @param string $baseUrl
     * @param array $expectedConfigs
     */
    public function __construct(int $siteID, int $accountID, string $clusterID, string $baseUrl, array $expectedConfigs)
    {
        parent::__construct($siteID, $accountID, $clusterID, $baseUrl);
        $this->expectedConfigs = $expectedConfigs;
    }

    /**
     * @return $this
     */
    public function expectNoSystemToken(): ExpectedSite
    {
        $this->expectSystemToken = false;
        return $this;
    }

    /**
     * @param string $region
     * @param string $network
     * @return $this
     */
    public function expectNetworkAndRegion(string $region, string $network): self
    {
        $this->expectedRegion = $region;
        $this->expectedNetwork = $network;
        return $this;
    }

    /**
     * @param Site $site
     * @return void
     */
    public function assertMatchesSite(Site $site): void
    {
        $suffix = "to match expected site '{$this->getBaseUrl()}'";
        TestCase::assertEquals($this->getSiteID(), $site->getSiteID(), "Expected siteID {$suffix}.");
        TestCase::assertEquals($this->getAccountID(), $site->getAccountID(), "Expected accountID {$suffix}.");
        TestCase::assertEquals($this->getClusterID(), $site->getClusterID(), "Expected clusterID {$suffix}.");
        TestCase::assertEquals($this->getBaseUrl(), $site->getBaseUrl(), "Expected baseUrl {$suffix}.");
    }

    /**
     * @param Site $site
     * @return void
     */
    public function assertConfigsMatchSite(Site $site): void
    {
        foreach ($this->expectedConfigs as $key => $value) {
            TestCase::assertEquals(
                $value,
                $site->getConfigValueByKey($key),
                "Expected site's config '$key' to match expectation.",
            );
        }
    }
}
