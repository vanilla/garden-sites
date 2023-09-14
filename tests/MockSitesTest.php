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
     * @return void
     */
    public function testSharedInstances()
    {
        $site1 = new MockSite("https://some-url.com", 1);
        $site2 = new MockSite("https://some-url.com", 2);
        $siteProvider = new MockSiteProvider($site1);
        $siteProvider->addSite($site2);

        $this->assertSame($site1, $siteProvider->getSite(1));
        $this->assertSame($site2, $siteProvider->getSite(2));
    }
}
