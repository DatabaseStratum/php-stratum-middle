<?php
declare(strict_types=1);

namespace SetBased\Stratum\Exception;

use SetBased\Exception\NamedException;

/**
 * Exception for situations where the execution of s SQL query has failed.
 */
interface DataLayerException extends NamedException
{
  // Nothing to implement.
}

//----------------------------------------------------------------------------------------------------------------------
