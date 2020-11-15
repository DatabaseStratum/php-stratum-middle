<?php
declare(strict_types=1);

namespace SetBased\Stratum\Middle\Helper;

use SetBased\Exception\LogicException;

/**
 * Utility class for operations on row sets.
 *
 * In this class all comparisons are done witch strict identical operators (i.e. === and !==).
 */
class RowSetHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a subset of a row set for which each row a column has a specific value.
   *
   * @param array[] $rows       The row set.
   * @param string  $columnName The column name (or in PHP terms the key in an row (i.e. array) in the row set).
   * @param mixed   $value      The value to be found.
   * @param bool    $negate     If true each row in the subset the column has not the specific value.
   *
   * @return array
   *
   * @since 4.0.0
   * @api
   */
  public static function filter(array $rows, string $columnName, $value, bool $negate = false): array
  {
    $ret = [];

    foreach ($rows as $row)
    {
      if ($row[$columnName]===$value xor $negate)
      {
        $ret[] = $row;
      }
    }

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the key of the first row in a row set for which a column has a specified value. Throws an exception
   * when the value is not found.
   *
   * @param array[] $rows       The row set.
   * @param string  $columnName The column name (or in PHP terms the key in an row (i.e. array) in the row set).
   * @param mixed   $value      The specified value to be found.
   * @param bool    $negate     If true returns the key of the first row for which the column has not the specified
   *                            value.
   *
   * @return int
   *
   * @since 4.0.0
   * @api
   */
  public static function findInRowSet(array $rows, string $columnName, $value, bool $negate = false): int
  {
    $key = static::searchInRowSet($rows, $columnName, $value, $negate);
    if ($key===null)
    {
      throw new LogicException("Value '%s' not found", $value);
    }

    return $key;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the key of the first row in a row set for which a column has a specified value. Returns null if no row is
   * found.
   *
   * @param array[] $rows       The row set.
   * @param string  $columnName The column name (or in PHP terms the key in an row (i.e. array) in the row set).
   * @param mixed   $value      The value to be found.
   * @param bool    $negate     If true returns the key of the first row for which the column has not the specified
   *                            value.
   *
   * @return int|null
   *
   * @since 4.0.0
   * @api
   */
  public static function searchInRowSet(array $rows, string $columnName, $value, bool $negate = false): ?int
  {
    foreach ($rows as $key => $row)
    {
      if ($row[$columnName]===$value xor $negate)
      {
        return $key;
      }
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
