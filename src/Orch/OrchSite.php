<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Orch;

use Garden\Http\HttpHandlerInterface;
use Garden\Sites\Site;
use Garden\Sites\SiteRecord;
use Garden\Utils\ArrayUtils;

/**
 * @extends Site<OrchSite, OrchCluster>
 * @property OrchSiteProvider $siteProvider
 */
class OrchSite extends Site
{
    public function __construct(
        SiteRecord $siteRecord,
        OrchSiteProvider $siteProvider,
        HttpHandlerInterface $httpHandler
    ) {
        parent::__construct($siteRecord, $siteProvider, $httpHandler);
    }

    /**
     * @inheritDoc
     */
    protected function loadSiteConfig(): array
    {
        $cluster = $this->getCluster();
        $clusterConfig = $this->siteProvider->getClusterConfig($cluster)["vanilla"] ?? [];
        $siteConfig = $this->siteProvider->getSiteConfig($this->getSiteID());

        $mergedConfig = ArrayUtils::mergeRecursive($clusterConfig, $siteConfig, function ($a, $b) {
            return $b;
        });
        return $mergedConfig;
    }
}
