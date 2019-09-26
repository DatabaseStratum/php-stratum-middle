<?php
declare(strict_types=1);

namespace SetBased\Stratum\Middle\Exception;

/**
 * Exception for (syntax) errors in SQL statements.
 */
interface QueryErrorException extends DataLayerException
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns an array with the lines of the SQL statement. The line(s) where the error occurred will be styled.
   *
   * @param string $style The style for highlighting the line with error.
   *
   * @return array The lines of the SQL statement.
   *
   * @since 4.0.0
   * @api
   */
  public function styledQuery(string $style = 'error'): array;

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
