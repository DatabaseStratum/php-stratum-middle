<?php
declare(strict_types=1);

namespace SetBased\Stratum\Middle\Exception;

use SetBased\Exception\NamedException;

/**
 * Exception for situations where the execution of SQL query has failed.
 */
interface DataLayerException extends NamedException
{
  // Nothing to implement.
}

//----------------------------------------------------------------------------------------------------------------------
