<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Local;

use Garden\Http\CurlHandler;
use Garden\Sites\Exceptions\ConfigLoadingException;
use Garden\Sites\SiteProvider;
use Garden\Sites\SiteRecord;
use Garden\Utils\ArrayUtils;

/**
 * Provider of local sites from the file-system.
 *
 * @extends SiteProvider<LocalSite, LocalCluster>
 */
class LocalSiteProvider extends SiteProvider
{
    const CONF_CLUSTER_ID = "Vanilla.ClusterID";

    const CONF_ACCOUNT_ID = "Vanilla.AccountID";
    const CONF_SITE_ID = "Vanilla.SiteID";

    private string $siteConfigFsBasePath;

    /**
     * @param string $siteConfigFsBasePath
     */
    public function __construct(string $siteConfigFsBasePath)
    {
        parent::__construct("localhost", "localhost");
        $this->siteConfigFsBasePath = $siteConfigFsBasePath;
    }

    /**
     * @return void
     * @throws ConfigLoadingException
     */
    private function ensureConfigBasePath(): void
    {
        if (!file_exists($this->siteConfigFsBasePath)) {
            throw new ConfigLoadingException("Local config directory did not exist " . $this->siteConfigFsBasePath);
        }
    }

    /**
     * @inheritDoc
     */
    protected function loadAllSiteRecords(): array
    {
        $this->ensureConfigBasePath();
        $configPaths = array_merge(
            glob($this->siteConfigFsBasePath . "/*.php") ?: [],
            glob($this->siteConfigFsBasePath . "/**/*.php") ?: [],
        );

        $siteRecordsBySiteID = [];
        foreach ($configPaths as $configPath) {
            $baseUrl = $this->domainForConfigPath($configPath);
            if ($baseUrl === null) {
                continue;
            }

            try {
                $config = $this->loadPhpConfigFile($configPath);
                $siteID = ArrayUtils::getByPath(self::CONF_SITE_ID, $config);
                $accountID = ArrayUtils::getByPath(self::CONF_ACCOUNT_ID, $config);
                $clusterID = ArrayUtils::getByPath(self::CONF_CLUSTER_ID, $config, LocalCluster::DEFAULT_CLUSTER_ID);

                if ($siteID === null || $accountID === null) {
                    continue;
                }

                $siteRecord = new SiteRecord($siteID, $accountID, $clusterID, $baseUrl);
                $siteRecord->setExtra("configPath", $configPath);

                $siteRecordsBySiteID[$siteRecord->getSiteID()] = $siteRecord;
            } catch (ConfigLoadingException $e) {
                // Ignore these.
            }
        }
        return $siteRecordsBySiteID;
    }

    /**
     * @inheritDoc
     */
    public function getSite(int $siteID): LocalSite
    {
        $siteRecord = $this->getSiteRecord($siteID);

        $configPath = $siteRecord->getExtra("configPath") ?? "";

        $localSite = new LocalSite($configPath, $siteRecord, $this, new CurlHandler());
        return $localSite;
    }

    /**
     * @inheritDoc
     */
    public function loadAllClusters(): array
    {
        $this->ensureConfigBasePath();

        $clusterPaths = glob($this->siteConfigFsBasePath . "/clusters/cl*.php");

        $clusters = [LocalCluster::DEFAULT_CLUSTER_ID => new LocalCluster()];
        foreach ($clusterPaths as $clusterPath) {
            if (preg_match("/\/cl(\d{5}).php/", $clusterPath, $matches)) {
                $clusterID = "cl" . $matches[1];
                $clusters[$clusterID] = new LocalCluster($clusterID);
            }
        }

        return $clusters;
    }

    /**
     * Load a site's config.
     *
     * @param string $configPath
     * @return array
     * @throws ConfigLoadingException
     */
    public function loadPhpConfigFile(string $configPath): array
    {
        if (!str_starts_with($configPath, $this->siteConfigFsBasePath)) {
            $configPath = $this->siteConfigFsBasePath . $configPath;
        }
        if (!defined("APPLICATION")) {
            define("APPLICATION", $this->userAgent);
        }
        if (!defined("PATH_CACHE")) {
            define("PATH_CACHE", "/dev/null");
        }
        /** @var array<array-key, mixed> $Configuration */
        $Configuration = [];
        if (file_exists($configPath)) {
            if (function_exists("opcache_invalidate")) {
                opcache_invalidate($configPath);
            }
            try {
                ob_start();
                require $configPath;
            } catch (\Throwable $e) {
                throw new ConfigLoadingException("Failed to read config from '$configPath'.", $e);
            } finally {
                ob_end_clean();
            }
        }
        if (empty($Configuration)) {
            throw new ConfigLoadingException("Loaded an empty config from '$configPath'.");
        }
        $config = $Configuration;

        return $config;
    }

    /**
     * Get the domain given a config path.
     *
     * @param string $configPath
     * @return string|null
     */
    private function domainForConfigPath(string $configPath): ?string
    {
        $configPath = str_replace($this->siteConfigFsBasePath, "", $configPath);
        if (preg_match("/^\\/config.php$/", $configPath)) {
            return "http://dev.vanilla.localhost";
        } elseif (preg_match("/^\\/vanilla\.localhost\\/(.*)\\.php$/", $configPath, $matches)) {
            $nodeName = $matches[1];

            return "http://vanilla.localhost/$nodeName";
        } elseif (preg_match("/^\\/e2e-tests\.vanilla\.localhost\\/(.*)\\.php$/", $configPath, $matches)) {
            $siteName = $matches[1];

            return "http://e2e-tests.vanilla.localhost/$siteName";
        } elseif (preg_match("/^\\/([^\/]+)\.php$/", $configPath, $matches)) {
            $siteName = $matches[1];
            return "http://{$siteName}.vanilla.localhost";
        }

        return null;
    }
}
