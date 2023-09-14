<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Sites\Clients\OrchHttpClient;
use Garden\Sites\Cluster;
use Garden\Sites\FileUtils;
use Garden\Sites\Orch\OrchCluster;
use Garden\Sites\Orch\OrchSiteProvider;
use Garden\Sites\Tests\Fixtures\ExpectedSite;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Tests for sites loaded from orchestration.
 */
class OrchSitesTest extends BaseSitesTestCase
{
    private ?MockHttpHandler $mockHandler = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHandler = new MockHttpHandler();
    }

    /**
     * Create a mocked {@link OrchSiteProvider}.
     *
     * Network requests are mocked from /tests/mock-orch
     *
     * @return OrchSiteProvider
     */
    public function siteProvider(): OrchSiteProvider
    {
        $baseUrl = "https://orch.vanilla.localhost";
        $orchClient = new OrchHttpClient($baseUrl, "tokenhere");
        if ($this->mockHandler === null) {
            $this->fail("Mock handler wasn't configured");
        }
        $orchClient->setHandler($this->mockHandler);
        $orchProvider = new OrchSiteProvider($orchClient, [Cluster::REGION_YUL1_DEV1]);

        $requestRoot = __DIR__ . "/mock-orch";
        $requestPaths = iterator_to_array(FileUtils::iterateFiles($requestRoot, "/.*\.json$/"));
        foreach ($requestPaths as $rawRequestPath) {
            $requestPath = str_replace([$requestRoot, ".json"], "", $rawRequestPath);
            $requestPath = str_replace("/?", "?", $requestPath);

            $responseBody = file_get_contents($rawRequestPath);
            $this->mockHandler->addMockRequest(
                new HttpRequest("GET", $baseUrl . $requestPath),
                new HttpResponse(200, ["content-type" => "application/json"], $responseBody),
            );
        }

        return $orchProvider;
    }

    /**
     * Generate a set of expected sites.
     *
     * @return array<ExpectedSite>
     */
    public function expectedSites(): array
    {
        $commonConfigs = [
            "allsite.havethis" => "everyone",
            "ReplaceByCluster" => [1, 2, 3],
            "MergeWithCluster.a" => 1,
            "MergeWithCluster.c" => 2,
        ];

        return [
            100 => (new ExpectedSite(
                100,
                100,
                "cl10001",
                "https://site1.vanillatesting.com",
                $commonConfigs,
            ))->expectRegion(Cluster::REGION_YUL1_DEV1),
            4000001 => (new ExpectedSite(
                4000001,
                50000,
                "cl10001",
                "https://test.vanilla.community/hub",
                $commonConfigs,
            ))->expectRegion(Cluster::REGION_YUL1_DEV1),

            4000002 => (new ExpectedSite(
                4000002,
                50000,
                "cl10001",
                "https://test.vanilla.community/node1",
                $commonConfigs,
            ))->expectRegion(Cluster::REGION_YUL1_DEV1),
            4000003 => (new ExpectedSite(
                4000003,
                60000,
                "cl40011",
                "https://yul1.vanilla.community",
                $commonConfigs,
            ))->expectRegion(Cluster::REGION_YUL1_PROD1),
            4000004 => (new ExpectedSite(
                4000004,
                60000,
                "cl40015",
                "https://ams1.vanilla.community",
                $commonConfigs,
            ))->expectRegion(Cluster::REGION_AMS1_PROD1),
            4000005 => (new ExpectedSite(
                4000005,
                70000,
                "cl10001",
                "https://no-system.vanilla.community",
                $commonConfigs,
            ))
                ->expectNoSystemToken()
                ->expectRegion(Cluster::REGION_YUL1_DEV1),
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
     * @return iterable
     */
    public function provideSiteFiltering(): iterable
    {
        yield "yul1 dev" => [Cluster::REGION_YUL1_DEV1, [100, 4000001, 4000002, 4000005]];
        yield "yul1 prod" => [Cluster::REGION_YUL1_PROD1, [4000003]];
        yield "ams1 prod" => [Cluster::REGION_AMS1_PROD1, [4000004]];
    }

    /**
     * Test that sites are properly filtered by network and region.
     *
     * @param string $regionID
     * @param array $expectedSiteIDs
     * @return void
     *
     * @dataProvider provideSiteFiltering
     */
    public function testSiteFiltering(string $regionID, array $expectedSiteIDs)
    {
        $siteProvider = $this->siteProvider();
        $siteProvider->setRegionIDs([$regionID]);
        $allSites = $siteProvider->getSites();

        $this->assertCount(count($expectedSiteIDs), $allSites);

        foreach ($allSites as $actualSite) {
            if (!in_array($actualSite->getSiteID(), $expectedSiteIDs)) {
                continue;
            }
            $expectedSite = $this->expectedSites()[$actualSite->getSiteID()];
            $expectedSite->assertMatchesSite($actualSite);
        }
    }

    /**
     * Test caching and error's thrown if sites fail to load from orchestration.
     *
     * @return void
     */
    public function testLoadSitesError()
    {
        $provider = $this->siteProvider();
        // Load up sites in to cache.
        $site = $provider->getSite(100);
        $this->expectedSites()[100]->assertMatchesSite($site);

        // Now if we reset the mock we should be using the cache.
        $provider->getOrchHttpClient()->setHandler(new MockHttpHandler());
        $site = $provider->getSite(100);
        $this->expectedSites()[100]->assertMatchesSite($site);

        // If our cache is cleared we will now error out though.
        $this->expectExceptionCode(404);
        $provider->setCache(new ArrayAdapter());
        $provider->getSite(100);
    }

    /**
     * Test caching and error's thrown if clusters fail to load from orchestration.
     *
     * @return void
     */
    public function testLoadClusters()
    {
        $provider = $this->siteProvider();
        // Load up sites in to cache.
        $cluster = $provider->getCluster("cl10001");
        $this->assertEquals("cl10001", $cluster->getClusterID());

        // Now if we reset the mock we should be using the cache.
        $provider->getOrchHttpClient()->setHandler(new MockHttpHandler());
        $cluster = $provider->getCluster("cl10001");
        $this->assertEquals("cl10001", $cluster->getClusterID());

        // If our cache is cleared we will now error out though.
        $this->expectExceptionCode(404);
        $provider->setCache(new ArrayAdapter());
        $provider->getCluster("cl10001");
    }

    /**
     * Test that user agent is applied to our http client.
     */
    public function testUserAgent()
    {
        $provider = $this->siteProvider();
        $provider->setUserAgent("hello-user");

        $site1Client = $provider->getSite(100)->httpClient();
        $this->assertEquals(
            "hello-user",
            $site1Client
                ->get("/hello", [], [], ["throw" => false])
                ->getRequest()
                ->getHeader("user-agent"),
        );

        $this->assertEquals(
            "hello-user",
            $provider
                ->getOrchHttpClient()
                ->get("/test", [], [], ["throw" => false])
                ->getRequest()
                ->getHeader("user-agent"),
        );
    }
}
