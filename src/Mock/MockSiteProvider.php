<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Mock;

use Garden\Sites\Cluster;
use Garden\Sites\Exceptions\SiteNotFoundException;
use Garden\Sites\Local\LocalCluster;
use Garden\Sites\Site;
use Garden\Sites\SiteProvider;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * A Mock LocalSiteProvider for testing purposes.
 *
 * @extends SiteProvider<MockSite, MockCluster>
 */
class MockSiteProvider extends SiteProvider
{
    const MOCK_SITE_ID = 123;

    /** @var array<int, MockSite> */
    private array $mockSites = [];

    /**
     * Constructor.
     *
     * @param MockSite ...$mockSites One or more mock sites.
     */
    public function __construct(MockSite ...$mockSites)
    {
        foreach ($mockSites as $mockLocalSite) {
            $this->mockSites[$mockLocalSite->getSiteID()] = $mockLocalSite;
        }
        parent::__construct([Cluster::REGION_MOCK]);
        $this->setCache(new NullAdapter());
    }

    /**
     * Add a site.
     *
     * @param MockSite $mockSite
     * @return void
     */
    public function addSite(MockSite $mockSite): void
    {
        $mockSite->setSiteProvider($this);
        $this->mockSites[$mockSite->getSiteID()] = $mockSite;
    }

    /**
     * @inheritDoc
     */
    protected function loadAllSiteRecords(): array
    {
        $siteRecords = [];
        foreach ($this->mockSites as $siteID => $site) {
            $siteRecords[$siteID] = $site->getSiteRecord();
        }

        return $siteRecords;
    }

    /**
     * @inheritDoc
     */
    public function getSite(int $siteID): Site
    {
        $site = $this->mockSites[$siteID] ?? null;
        if ($site === null) {
            throw new SiteNotFoundException($siteID);
        }
        return $site;
    }

    /**
     * @inheritDoc
     */
    protected function loadAllClusters(): array
    {
        return [MockSite::MOCK_CLUSTER_ID => new MockCluster(MockSite::MOCK_CLUSTER_ID)];
    }
}
