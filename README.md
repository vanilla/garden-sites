# vanilla/garden-sites

Library for managing providing a list of sites and clusters from localhost or an orchestration server.

## Installation

```shell
composer require vanilla/garden-sites
```

## Usage

The entrypoint to usage of this library is through either the `Garden\Sites\LocalSiteProvider`, `Garden\Sites\DashboardSiteProvider`, `Garden\Sites\OrchSiteProvider`.

### Configuring in Laravel

To configure this library in Laravel you need to setup the following 2 things.

**conf/orch.php**

```php
use Garden\Sites\LaravelProviderFactory;

// This will inject your env variables into laravel config.
return LaravelProviderFactory::createLaravelConfigFromEnv("env");
```

**Usage in the app**

```php
use Garden\Sites\LaravelProviderFactory;use Garden\Sites\SiteProvider;

/** @var SiteProvider $provider */
$provider = LaravelProviderFactory::providerFromLaravelConfig([\Config::class, 'get'])

/**
 * Site providers do various caching of results. By default an in-memory cache is used, but especially with an orch-client
 * it is recommended to configure a persistent cache like memcached or redis.
 * Caches must implement {@link CacheInterface}
 */

$cache = new RedisAdapter(/** Configuration here. */);
// or
$cache = new MemcachedAdapter(/** Configuration here. */);

$provider->setCache($cache);
```

### LocalSiteProvider

This provider reads site configurations from a local directory. The provider is configured with a path to the directory containing the site configurations.

**.env**

```env
ORCH_TYPE="local"
ORCH_LOCAL_DIRECTORY_PATH="/path/to/site/configs"
```

Notably the path to the site configs must be a readable directory to the PHP process.

The local site provider works be reading php-based config files from a given directory. This reads all sites recognized by the `vnla docker` setup. For a site config to be recognized it must meet the following criteria

-   The file has name matching one of the following patterns
    -   `/*.php` - Becomes `*.vanilla.local`
    -   `/vanilla.local/*.php` - Becomes `vanilla.local/*`
    -   `/e2e-tests.vanilla.local/*.php` - Becomes `e2e-tests.vanilla.local/*`
-   The file contains a valid PHP configuration files.
-   The configuration contains the following values
    -   `Vanilla.SiteID`
    -   `Vanilla.AccountID`
    -   Optional `Vanilla.ClusterID`. Defaults to `cl00000`

**Clusters**

By default all local sites are on the same cluster `cl00000`. You can bypass this by adding a `Vanilla.ClusterID` config to the site.

Cluster configurations may be added into the configs `/clusters` directory and are named `/clusters/cl*.php`. These should php config files just like sites. The configurations in these files will be merged with the site configs.

### OrchSiteProvider

The orch site provider loads sites and clusters from a remote orchestration http server. Sites, clusters, and configs are cached for a 1 minute period.

**.env**

```env
ORCH_TYPE="orchestration"
ORCH_BASE_URL="https://orchestration.vanilladev.com"
ORCH_SECRET="SECRET_HERE"
# Optional hostname to force for orchestration (Force Proxy from localhost)
ORCH_HOSTNAME="ORCH_HOSTNAME";
# CSV of region IDs to accept sites from.
ORCH_REGION_IDS="yul1-prod1,sjc1-prod1";
ORCH_USER_AGENT="my-service";
```

### DashboardSiteProvider

The orch site provider loads sites and clusters from a remote management-dashboard http server. Sites, clusters, and configs are cached for a 1 minute period.

**.env**

```env
ORCH_TYPE="dashboard"
ORCH_BASE_URL="https://management-dashboard.vanilladev.com"
# JWT secret for management dashboard
ORCH_SECRET="SECRET_HERE"
# Optional hostname to force for management dashboard (Force Proxy from localhost)
ORCH_HOSTNAME="ORCH_HOSTNAME";
# CSV of region IDs to accept sites from.
ORCH_REGION_IDS="yul1-prod1,sjc1-prod1";
ORCH_USER_AGENT="my-service";
```

### Using site providers

Both `OrchSiteProvider` and `LocalSiteProvider` extend from `SiteProvider` and implement similar functionality.

```php
use Garden\Sites\Exceptions\ClusterNotFoundException;
use Garden\Sites\Exceptions\SiteNotFoundException;
use Garden\Sites\SiteProvider;

function doSomethingWithProvider(SiteProvider $siteProvider)
{
    /**
     * Look up a site by ID.
     * Can throw an {@link SiteNotFoundException}
     */
    $site = $siteProvider->getSite(100);

    // List all sites
    $allSites = $siteProvider->getSites();

    /**
     * Look up a cluster by ID.
     * Can throw an {@link ClusterNotFoundException}
     */
    $cluster = $siteProvider->getCluster("cl10001");

    // List all clusters
    $allClusters = $siteProvider->getClusters();
}
```

### Using Clusters and Sites

```php
use Garden\Sites\Site;
use Garden\Sites\Cluster;

function doSomethingWithCluster(Cluster $cluster)
{
    // A few getters
    $clusterID = $cluster->getClusterID();
    $regionID = $cluster->getRegionID();
}

function doSomethingWithSite(Site $site)
{
    // A few getters
    $siteID = $site->getSiteID();
    $accountID = $site->getAccountID();
    $clusterID = $site->getClusterID();
    $baseUrl = $site->getBaseUrl();

    // HTTP Requests
    // This is a `garden-http` configured with the site's baseUrl
    // and set to throw on errors.
    $response = $site->httpClient()->get("/url", ["query" => "params"]);
    $response = $site->httpClient()->post("/url", ["body" => "here"]);
    $response = $site->httpClient()->patch("/url", ["body" => "here"]);
    $response = $site->httpClient()->put("/url", ["body" => "here"]);
    $response = $site->httpClient()->delete("/url");

    // System auth for an http request
    $site
        ->httpClient()
        ->withSystemAuth()
        ->get("/some-resource");

    // Ensure there is no auth on a request
    $site
        ->httpClient()
        ->withNoAuth()
        ->get("/some-resource");

    // Access the cluster
    $cluster = $site->getCluster();

    // Configs
    $configVal = $site->getConfigValueByKey("some.key.here", "fallback");

    // Configs are cached on the `Garden\Sites\Site` instance
    // You can clear them here.
    $site->clearConfigCache();

    // Check services hostnames the site should be using.
    $baseUrl = $site->getQueueServiceBaseUrl();
    $baseUrl = $site->getSearchServiceBaseUrl();
}
```
