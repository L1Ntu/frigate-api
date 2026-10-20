<?php

namespace L1Ntu\Frigate\Exceptions;

use Exception;

/**
 * Base exception for everything the client throws.
 * For HTTP errors, getCode() returns the response status code.
 */
class FrigateException extends Exception
{
}
