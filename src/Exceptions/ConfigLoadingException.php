<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Exceptions;

use Garden\Utils\ContextException;

class ConfigLoadingException extends ContextException
{
    public function __construct(string $details = "", ?\Throwable $prev = null)
    {
        parent::__construct("Failed to load site config. $details", 500, [], $prev);
    }
}
