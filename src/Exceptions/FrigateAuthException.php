<?php

namespace L1Ntu\Frigate\Exceptions;

/**
 * Login was rejected, returned no session token, or the request was unauthorized (HTTP 401).
 */
class FrigateAuthException extends FrigateException
{
}
