<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Exceptions;

use Garden\Utils\ContextException;

/**
 * Exception thrown when required API credentials are missing for a network request.
 */
class BadApiCredentialsException extends ContextException
{
}
