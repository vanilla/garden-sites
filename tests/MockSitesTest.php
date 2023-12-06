<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests;

use Garden\Sites\Mock\MockSite;
use Garden\Sites\Mock\MockSiteProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the mock sites.
 */
class MockSitesTest extends TestCase
{
    /**
     * Test that instances of a `MockSite` are shared and not copied.
     * Additionallity test reading values from a site.
     *
     * @return void
     */
    public function testSharedInstances()
    {
        $site1 = new MockSite("https://some-url.com", 1);
        $site2 = new MockSite("https://some-url.com", 2, ["foo" => ["bar" => true]], "cl00002", 5032, 100);
        $siteProvider = new MockSiteProvider($site1);
        $siteProvider->addSite($site2);

        $this->assertSame($site1, $siteProvider->getSite(1));
        $this->assertSame($site2, $siteProvider->getSite(2));

        $this->assertEquals(true, $site2->getConfigValueByKey("foo.bar"));
        $this->assertEquals(2, $site2->getSiteID());
        $this->assertEquals(5032, $site2->getAccountID());
        $this->assertEquals(100, $site2->getMultisiteID());
        $this->assertEquals("cl00002", $site2->getClusterID());
    }

    /**
     * Test various methods for fetching filtered lists of sites.
     *
     * @return void
     */
    public function testGetSitesFiltered()
    {
        $site1 = new MockSite("https://site1.com", 1, [], "cl00000", 100);
        $site2 = new MockSite("https://company.com/site2", 2, [], "cl00000", 101, 500);
        $site3 = new MockSite("https://company.com/site3", 3, [], "cl00000", 101, 500);
        $site4 = new MockSite("https://site4.company.com", 4, [], "cl00000", 101);

        $siteProvider = new MockSiteProvider($site1, $site2, $site3, $site4);

        $this->assertEquals([$site1], $siteProvider->getSitesByAccountID(100));
        $this->assertEquals([$site2, $site3, $site4], $siteProvider->getSitesByAccountID(101));
        $this->assertEquals([], $siteProvider->getSitesByAccountID(451234));
        $this->assertEquals([$site2, $site3], $siteProvider->getSitesByMultisiteID(500));
        $this->assertEquals([], $siteProvider->getSitesByMultisiteID(451234));
    }
}
