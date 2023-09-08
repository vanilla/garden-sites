<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests\Fixtures;

use Garden\Sites\Site;
use Garden\Sites\SiteRecord;
use PHPUnit\Framework\TestCase;

class ExpectedSite extends SiteRecord
{
    private array $expectedConfigs;

    public bool $expectSystemToken = true;

    public string $expectedRegion = "localhost";
    public string $expectedNetwork = "localhost";

    public function __construct(int $siteID, int $accountID, string $clusterID, string $baseUrl, array $expectedConfigs)
    {
        parent::__construct($siteID, $accountID, $clusterID, $baseUrl);
        $this->expectedConfigs = $expectedConfigs;
    }

    public function getExpectedConfigs(): array
    {
        return $this->expectedConfigs;
    }

    /**
     * @return $this
     */
    public function expectNoSystemToken(): ExpectedSite
    {
        $this->expectSystemToken = false;
        return $this;
    }

    public function expectNetworkAndRegion(string $region, string $network): self
    {
        $this->expectedRegion = $region;
        $this->expectedNetwork = $network;
        return $this;
    }

    public function assertMatchesSite(Site $site): void
    {
        $suffix = "to match expected site '{$this->getBaseUrl()}'";
        TestCase::assertEquals($this->getSiteID(), $site->getSiteID(), "Expected siteID {$suffix}.");
        TestCase::assertEquals($this->getAccountID(), $site->getAccountID(), "Expected accountID {$suffix}.");
        TestCase::assertEquals($this->getClusterID(), $site->getClusterID(), "Expected clusterID {$suffix}.");
        TestCase::assertEquals($this->getBaseUrl(), $site->getBaseUrl(), "Expected baseUrl {$suffix}.");
    }

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
