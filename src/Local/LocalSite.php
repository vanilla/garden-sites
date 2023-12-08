<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Local;

use Garden\Http\HttpHandlerInterface;
use Garden\Sites\Exceptions\ConfigLoadingException;
use Garden\Sites\Site;
use Garden\Sites\SiteRecord;
use Garden\Utils\ArrayUtils;

/**
 * Localhost site implementation.
 *
 * @extends Site<LocalSite, LocalCluster>
 *
 * @property LocalSiteProvider $siteProvider
 */
class LocalSite extends Site
{
    private string $configPath;

    /**
     * @param string $configPath
     * @param SiteRecord $siteRecord
     * @param LocalSiteProvider $siteProvider
     *
     * @psalm-suppress InvalidArgument
     */
    public function __construct(string $configPath, SiteRecord $siteRecord, LocalSiteProvider $siteProvider)
    {
        $this->configPath = $configPath;
        parent::__construct($siteRecord, $siteProvider);
    }

    /**
     * @inheritDoc
     */
    protected function loadSiteConfig(): array
    {
        // Config defaults
        $configDefaults = $this->siteProvider->loadPhpConfigFile(
            $this->siteProvider->siteConfigFsBasePath . "/config-defaults.php",
        );

        $dockerDefaultsPath = $this->siteProvider->siteConfigFsBasePath . "/docker-defaults.php";
        $dockerDefaults = file_exists($dockerDefaultsPath)
            ? $this->siteProvider->loadPhpConfigFile($dockerDefaultsPath)
            : [];

        try {
            $clusterConfigPath = "/clusters/" . $this->getClusterID() . ".php";
            $clusterConfig = $this->siteProvider->loadPhpConfigFile($clusterConfigPath);
        } catch (ConfigLoadingException $ex) {
            $clusterConfig = [];
        }

        $siteConfig = $this->siteProvider->loadPhpConfigFile($this->configPath);

        $configs = [$configDefaults, $dockerDefaults, $clusterConfig, $siteConfig];

        $finalConfig = array_reduce(
            $configs,
            function (array $acc, array $new) {
                return ArrayUtils::mergeRecursive($acc, $new, function ($a, $b) {
                    return $b;
                });
            },
            [],
        );

        return $finalConfig;
    }
}
