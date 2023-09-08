<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests;

use Garden\Http\Mocks\MockHttpHandler;
use Garden\Sites\Exceptions\ConfigLoadingException;
use Garden\Sites\Exceptions\SiteNotFoundException;
use Garden\Sites\Local\LocalSite;
use Garden\Sites\Local\LocalSiteProvider;
use Garden\Sites\SiteProvider;
use Garden\Sites\Tests\Fixtures\ExpectedSite;

class LocalSitesTest extends BaseSitesTestCase
{
    const SID_CFG_PHP = 100;
    const SID_VALID = 101;
    const SID_E2E = 102;
    const SID_NO_SYS_TOKEN = 103;
    const SID_OTHER_CLUSTER = 105;

    /**
     * @return array<int, ExpectedSite>
     */
    private function expectedSites(): array
    {
        $commonConfig = [
            "Config1" => "val1",
            "Nested.Nested1" => "valnested1",
            "MergedWithMe" => ["val1", "val2"],
        ];
        return [
            self::SID_CFG_PHP => new ExpectedSite(
                self::SID_CFG_PHP,
                100,
                "cl00000",
                "http://dev.vanilla.localhost",
                $commonConfig,
            ),
            self::SID_VALID => new ExpectedSite(
                self::SID_VALID,
                101,
                "cl00000",
                "http://vanilla.localhost/valid",
                $commonConfig + [
                    "SomeArr" => [3, 4, 5],
                    "ClusterConfig.SomeKey" => "cluster2",
                ],
            ),
            self::SID_NO_SYS_TOKEN => (new ExpectedSite(
                self::SID_NO_SYS_TOKEN,
                101,
                "cl00000",
                "http://vanilla.localhost/no-system-token",
                $commonConfig,
            ))->expectNoSystemToken(),
            self::SID_E2E => new ExpectedSite(
                self::SID_E2E,
                102,
                "cl00000",
                "http://e2e-tests.vanilla.localhost/site1",
                $commonConfig,
            ),
            self::SID_OTHER_CLUSTER => new ExpectedSite(
                self::SID_OTHER_CLUSTER,
                105,
                "cl00001",
                "http://other-cluster.vanilla.localhost",
                $commonConfig + [
                    "ClusterConfig.SomeKey" => "cluster2",
                ],
            ),
        ];
    }

    public function provideExpectedSites(): iterable
    {
        foreach ($this->expectedSites() as $expectedSite) {
            yield $expectedSite->getBaseUrl() => [$expectedSite];
        }
    }

    public function getValidSiteIDs(): array
    {
        return [self::SID_CFG_PHP, self::SID_VALID, self::SID_E2E];
    }

    public function siteProvider(): SiteProvider
    {
        $dir = realpath(__DIR__ . "/configs");
        $siteProvider = new LocalSiteProvider($dir);
        return $siteProvider;
    }

    public function testAllSites()
    {
        $allSites = $this->siteProvider()->getSites("localhost", "localhost");

        foreach ($this->expectedSites() as $expectedSite) {
            $expectedSite->assertMatchesSite($allSites[$expectedSite->getSiteID()] ?? null);
        }
    }

    public function testInvalidClusterConfig()
    {
        // Site 105 has an invalid config file.
        $site = $this->siteProvider()->getSite(105);
        $this->assertNull($site->getConfigValueByKey("SomeConfig"));
    }

    public function testInvalidBasePath()
    {
        $siteProvider = new LocalSiteProvider("/not-a-path");
        $this->expectException(ConfigLoadingException::class);
        $siteProvider->loadAllClusters();
    }

    public function testSerializeSite(): void
    {
        $site = $this->siteProvider()->getSite(self::SID_VALID);
        $expected = <<<JSON
        {
            "siteID": 101,
            "baseUrl": "http:\/\/vanilla.localhost\/valid",
            "clusterID": "cl00000",
            "configPath": "\/vanilla.localhost\/valid.php"
        }
        JSON;

        $this->assertEquals($expected, json_encode($site, JSON_PRETTY_PRINT));
    }
}
