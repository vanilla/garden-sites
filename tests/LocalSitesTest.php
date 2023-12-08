<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests;

use Garden\Sites\Exceptions\ConfigLoadingException;
use Garden\Sites\Local\LocalSiteProvider;
use Garden\Sites\Tests\Fixtures\ExpectedSite;

/**
 * Tests for the {@link LocalSiteProvider}
 */
class LocalSitesTest extends BaseSitesTestCase
{
    const SID_CFG_PHP = 100;
    const SID_VALID = 101;
    const SID_E2E = 102;
    const SID_NO_SYS_TOKEN = 103;
    const SID_OTHER_CLUSTER = 105;

    const SID_HUB = 10000;
    const SID_NODE1 = 10001;

    /**
     * @return array<int, ExpectedSite>
     */
    private function expectedSites(): array
    {
        $commonConfig = [
            "Config1" => "val1",
            "Nested.Nested1" => "valnested1",
            "DefaultConfig" => "foo",
            "DockerConfig" => "bar",
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
                    "ClusterConfig.SomeKey" => "cluster1",
                    "MergeWithMe.Key1" => "val1",
                    "MergeWithMe.Key2" => "val2",
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
            self::SID_HUB => new ExpectedSite(
                self::SID_HUB,
                10000,
                "cl00000",
                "http://vanilla.localhost/hub",
                $commonConfig + [],
                294952213, // crc32(vanilla.localhost)
            ),
            self::SID_NODE1 => new ExpectedSite(
                self::SID_NODE1,
                10000,
                "cl00000",
                "http://vanilla.localhost/node1",
                $commonConfig + [],
                294952213, // crc32(vanilla.localhost)
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function provideExpectedSites(): iterable
    {
        foreach ($this->expectedSites() as $expectedSite) {
            yield $expectedSite->getBaseUrl() => [$expectedSite];
        }
    }

    /**
     * @inheritDoc
     */
    public function siteProvider(): LocalSiteProvider
    {
        $dir = realpath(__DIR__ . "/configs");
        $siteProvider = new LocalSiteProvider($dir);
        return $siteProvider;
    }

    /**
     * Test loading of all sites.
     *
     * @return void
     */
    public function testAllSites()
    {
        $allSites = $this->siteProvider()->getSites();

        foreach ($this->expectedSites() as $expectedSite) {
            $expectedSite->assertMatchesSite($allSites[$expectedSite->getSiteID()] ?? null);
        }
    }

    /**
     * Test sites that have an invalid cluster config.
     *
     * @return void
     */
    public function testInvalidClusterConfig()
    {
        // Site 105 has an invalid config file.
        $site = $this->siteProvider()->getSite(105);
        $this->assertNull($site->getConfigValueByKey("SomeConfig"));
    }

    /**
     * Test sites with an invalid base path.
     *
     * @return void
     */
    public function testInvalidBasePath()
    {
        $siteProvider = new LocalSiteProvider("/not-a-path");
        $this->expectException(ConfigLoadingException::class);
        $siteProvider->loadAllClusters();
    }

    /**
     * Test serialization o site records.
     *
     * @return void
     */
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
