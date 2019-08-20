<?php
declare(strict_types=1);

namespace SetBased\Stratum\Exception;

use SetBased\Exception\RuntimeException;

/**
 * Exception for situations where the execution of s SQL query has failed.
 */
abstract class DataLayerException extends RuntimeException
{
  // Nothing to implement.
}

//----------------------------------------------------------------------------------------------------------------------
