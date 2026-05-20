<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

use Garden\Utils\ArrayUtils;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\UriFactory;

/**
 * Class holding a minimum of amount of data identifying a site.
 */
class SiteRecord implements \JsonSerializable
{
    private array $extra = [];

    private int $siteID;

    private int $accountID;

    private ?int $multisiteID;

    private string $clusterID;

    private string $baseUrl;

    private string $name;

    private ?string $domain;

    /**
     * @param int $siteID
     * @param int $accountID
     * @param string $clusterID
     * @param string $baseUrl
     * @param string $name
     * @param string|null $domain
     */
    public function __construct(
        int $siteID,
        int $accountID,
        ?int $multisiteID,
        string $clusterID,
        string $baseUrl,
        string $name,
        ?string $domain = null
    ) {
        $this->siteID = $siteID;
        $this->accountID = $accountID;
        $this->multisiteID = $multisiteID;
        $this->clusterID = $clusterID;
        $this->baseUrl = $baseUrl;
        $this->name = $name;
        $this->domain = $domain;
    }

    /**
     * Derive a site name and domain from a base URL.
     *
     * Sites without a path segment use the host as the name with a null domain.
     * Path-based sites use the host as the domain and a multi-{path} name.
     *
     * @return array{name: string, domain: string|null}
     */
    public static function deriveNameAndDomainFromBaseUrl(string $baseUrl): array
    {
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $path = trim(parse_url($baseUrl, PHP_URL_PATH) ?? "", "/");

        if ($path === "") {
            return [
                "name" => $host,
                "domain" => null,
            ];
        }

        return [
            "name" => "multi-{$path}.{$host}",
            "domain" => $host,
        ];
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
     * @return int|null
     */
    public function getMultisiteID(): ?int
    {
        return $this->multisiteID;
    }

    /**
     * @param int|null $multisiteID
     * @return void
     */
    public function setMultisiteID(?int $multisiteID): void
    {
        $this->multisiteID = $multisiteID;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * @return UriInterface
     */
    public function getBaseUri(): UriInterface
    {
        $uriFactory = new UriFactory();
        return $uriFactory->createUri($this->getBaseUrl());
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
            "name" => $this->getName(),
            "domain" => $this->getDomain(),
        ] + $this->extra;
    }
}
