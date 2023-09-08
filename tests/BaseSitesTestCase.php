<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests;

use Garden\Http\HttpResponse;
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Sites\Exceptions\BadApiCredentialsException;
use Garden\Sites\Exceptions\ClusterNotFoundException;
use Garden\Sites\Exceptions\SiteNotFoundException;
use Garden\Sites\SiteProvider;
use Garden\Sites\Tests\Fixtures\ExpectedSite;
use PHPUnit\Framework\TestCase;

/**
 * Base tests case with common tests between different providers.
 */
abstract class BaseSitesTestCase extends TestCase
{
    const SID_INVALID = 142141;

    /**
     * Create a configured site provider instance.
     *
     * @return SiteProvider
     */
    abstract public function siteProvider(): SiteProvider;

    /**
     *  Provide expected sites to various tests.
     *
     * @return iterable<array-key, array<ExpectedSite>>
     */
    abstract public function provideExpectedSites(): iterable;

    /**
     * @param ExpectedSite $expectedSite
     *
     * @return void
     * @dataProvider provideExpectedSites
     */
    public function testSiteClientBaseUrl(ExpectedSite $expectedSite)
    {
        $provider = $this->siteProvider();
        $provider->setRegionAndNetwork($expectedSite->expectedRegion, $expectedSite->expectedNetwork);
        $site = $provider->getSite($expectedSite->getSiteID());
        $siteClient = $site->httpClient();
        $siteClient->setThrowExceptions(false);

        $mockHandler = new MockHttpHandler();
        $siteClient->setHandler($mockHandler);

        // Base URL is added.
        $response = $siteClient->get("/hello-world");
        $this->assertEquals("{$expectedSite->getBaseUrl()}/hello-world", $response->getRequest()->getUrl());
    }

    /**
     * @param ExpectedSite $expectedSite
     * @return void
     * @dataProvider provideExpectedSites
     */
    public function testSiteClientAuth(ExpectedSite $expectedSite): void
    {
        $provider = $this->siteProvider();
        $provider->setRegionAndNetwork($expectedSite->expectedRegion, $expectedSite->expectedNetwork);
        $site = $provider->getSite($expectedSite->getSiteID());
        $siteClient = $site->httpClient();
        $siteClient->setThrowExceptions(false);
        $mockHandler = new MockHttpHandler();
        $siteClient->setHandler($mockHandler);

        if (!$expectedSite->expectSystemToken) {
            $this->expectException(BadApiCredentialsException::class);
        }

        /** @var HttpResponse $request */
        $request = $siteClient
            ->withSystemAuth()
            ->get("/some-url")
            ->getRequest();
        $this->assertEquals("Bearer {$site->getSystemAccessToken()}", $request->getHeader("Authorization"));

        /** @var HttpResponse $request */
        $request = $siteClient
            ->withNoAuth()
            ->get("/some-url")
            ->getRequest();
        $this->assertEmpty($request->getHeader("Authorization"));
    }

    /**
     * @param ExpectedSite $expectedSite
     * @return void
     * @dataProvider provideExpectedSites
     */
    public function testValidSites(ExpectedSite $expectedSite): void
    {
        $provider = $this->siteProvider();
        $provider->setRegionAndNetwork($expectedSite->expectedRegion, $expectedSite->expectedNetwork);
        $site = $provider->getSite($expectedSite->getSiteID());
        $expectedSite->assertMatchesSite($site);
        $expectedSite->assertConfigsMatchSite($site);

        // Sites have a cluster
        $cluster = $site->getCluster();
        $this->assertEquals($expectedSite->getClusterID(), $cluster->getClusterID());
    }

    public function testGetInvalidSite()
    {
        $this->expectException(SiteNotFoundException::class);
        $this->siteProvider()->getSite(self::SID_INVALID);
    }

    public function testClusterNotFound(): void
    {
        $this->expectException(ClusterNotFoundException::class);
        $this->siteProvider()->getCluster("cl54124141");
    }
}
