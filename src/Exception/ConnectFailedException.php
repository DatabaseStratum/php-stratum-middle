<?php
declare(strict_types=1);

namespace SetBased\Stratum\Middle\Exception;

use SetBased\Exception\NamedException;

/**
 * Exception for situations where an attempt to connect to a database (server) failed.
 */
interface ConnectFailedException extends NamedException
{
  // Nothing to implement.
}

//----------------------------------------------------------------------------------------------------------------------
