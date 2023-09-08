<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

use Garden\Utils\ArrayUtils;

/**
 * Class holding a minimum of amount of data identifying a site.
 */
class SiteRecord implements \JsonSerializable
{
    private array $extra = [];

    private int $siteID;

    private int $accountID;

    private string $clusterID;

    private string $baseUrl;

    /**
     * @param int $siteID
     * @param int $accountID
     * @param string $clusterID
     * @param string $baseUrl
     */
    public function __construct(int $siteID, int $accountID, string $clusterID, string $baseUrl)
    {
        $this->siteID = $siteID;
        $this->accountID = $accountID;
        $this->clusterID = $clusterID;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return int
     */
    public function getSiteID(): int
    {
        return $this->siteID;
    }

    /**
     * @return int
     */
    public function getAccountID(): int
    {
        return $this->accountID;
    }

    /**
     * @return string
     */
    public function getClusterID(): string
    {
        return $this->clusterID;
    }

    /**
     * Get the baseURL of the site.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Set an extra meta value on the site record.
     *
     * @param string $path
     * @param mixed $value
     * @return void
     *
     * @psalm-suppress PossiblyInvalidPropertyAssignmentValue
     */
    public function setExtra(string $path, $value): void
    {
        ArrayUtils::setByPath($path, $this->extra, $value);
    }

    /**
     * Get an extra meta value from the site record.
     *
     * @param string $path
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getExtra(string $path, $default = null)
    {
        return ArrayUtils::getByPath($path, $this->extra, $default);
    }

    public function jsonSerialize(): array
    {
        return [
            "siteID" => $this->getSiteID(),
            "baseUrl" => $this->getBaseUrl(),
            "clusterID" => $this->getClusterID(),
        ] + $this->extra;
    }
}
