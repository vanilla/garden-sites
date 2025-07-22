<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

/**
 * Factory for creating a site provider from Laravel configuration.
 */
class LaravelProviderFactory
{
    public const ORCH_TYPE = "ORCH_TYPE";
    public const ORCH_BASE_URL = "ORCH_BASE_URL";
    public const ORCH_HOSTNAME = "ORCH_HOSTNAME";
    public const ORCH_SECRET = "ORCH_SECRET";
    public const ORCH_REGION_IDS = "ORCH_REGION_IDS";
    public const ORCH_USER_AGENT = "ORCH_USER_AGENT";

    public const ORCH_LOCAL_DIRECTORY_PATH = "ORCH_LOCAL_DIRECTORY_PATH";

    /**
     * @param callable(string, mixed=): mixed $envFunction
     * @return array
     */
    public static function createLaravelConfigFromEnv(callable $envFunction): array
    {
        $orchType = $envFunction(self::ORCH_TYPE) ?: "local";

        $validation = match ($orchType) {
            "dashboard", "orchestration" => [
                self::ORCH_TYPE => ["required", "in:dashboard,orchestration"],
                self::ORCH_BASE_URL => ["required", "url"],
                self::ORCH_USER_AGENT => ["required", "string"],
                self::ORCH_HOSTNAME => ["string", "nullable"],
                self::ORCH_SECRET => ["required", "string"],
                self::ORCH_REGION_IDS => ["array"],
            ],
            "local" => [
                self::ORCH_TYPE => ["required", "in:local"],
                self::ORCH_LOCAL_DIRECTORY_PATH => ["string", "required"],
            ],
            default => throw new \Exception("Unknown orch type: $orchType"),
        };

        return [
            self::ORCH_TYPE => $orchType,
            self::ORCH_BASE_URL => $envFunction(self::ORCH_BASE_URL),
            self::ORCH_HOSTNAME => $envFunction(self::ORCH_HOSTNAME) ?: null,
            self::ORCH_SECRET => $envFunction(self::ORCH_SECRET),
            self::ORCH_REGION_IDS => array_filter(explode(",", $envFunction("ORCH_REGION_IDS", "")), "trim"),
            self::ORCH_USER_AGENT => $envFunction(self::ORCH_USER_AGENT),
            "__validation__" => $validation,
        ];
    }

    /**
     * Gien a laravel config function, fetch configs and create a site provider.
     *
     * @param callable $configFunction
     *
     * @return SiteProvider
     */
    public static function providerFromLaravelConfig(callable $configFunction): SiteProvider
    {
        $orchType = $configFunction("orch." . self::ORCH_TYPE);
        $orchBaseUrl = $configFunction("orch." . self::ORCH_BASE_URL);
        $orchHostname = $configFunction("orch." . self::ORCH_HOSTNAME);
        $secret = $configFunction("orch." . self::ORCH_SECRET);
        $regionIDs = $configFunction("orch." . self::ORCH_REGION_IDS);
        $userAgent = $configFunction("orch." . self::ORCH_USER_AGENT);

        $provider = match ($orchType) {
            "dashboard" => new Dashboard\DashboardSiteProvider(
                new Clients\DashboardHttpClient($orchBaseUrl, $secret, $orchHostname),
                $regionIDs,
            ),
            "orchestration" => new Orch\OrchSiteProvider(
                new Clients\OrchHttpClient($orchBaseUrl, $secret, $orchHostname),
                $regionIDs,
            ),
            "local" => new Local\LocalSiteProvider($configFunction("orch." . self::ORCH_LOCAL_DIRECTORY_PATH)),
            default => throw new \InvalidArgumentException("Unknown orch type: $orchType"),
        };

        $provider->setUserAgent($userAgent);
        $provider->setRegionIDs($regionIDs);

        return $provider;
    }
}
