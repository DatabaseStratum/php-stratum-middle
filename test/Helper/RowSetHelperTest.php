<?php
declare(strict_types=1);

namespace SetBased\Stratum\Middle\Test\Helper;

use PHPUnit\Framework\TestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;

/**
 * Test cases for class RowSetHelper.
 */
class RowSetHelperTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method filter with empty row set.
   */
  public function testFilterWithEmptySet(): void
  {
    $rows = [];

    $filtered = RowSetHelper::filter($rows, 'emp_name', 'John Doe');
    self::assertSame([], $filtered);

    $filtered = RowSetHelper::filter($rows, 'emp_name', 'John Doe', true);
    self::assertSame([], $filtered);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method filter with a row set.
   */
  public function testFilterWithNonEmptySet(): void
  {
    $rows = [['emp_id'   => 1,
              'emp_name' => 'Jane Doe'],
             ['emp_id'   => 2,
              'emp_name' => 'John Doe'],
             ['emp_id'   => 3,
              'emp_name' => 'John Wayne']];

    $filtered = RowSetHelper::filter($rows, 'emp_name', 'John Doe');
    self::assertSame([['emp_id' => 2, 'emp_name' => 'John Doe']], $filtered);

    $filtered = RowSetHelper::filter($rows, 'emp_name', 'John Doe', true);
    self::assertSame([['emp_id'   => 1,
                       'emp_name' => 'Jane Doe'],
                      ['emp_id'   => 3,
                       'emp_name' => 'John Wayne']], $filtered);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method filter with a row set.
   */
  public function testFilterWithNonEmptySetNoResult(): void
  {
    $rows = [['emp_id'   => 1,
              'emp_name' => 'Jane Doe'],
             ['emp_id'   => 2,
              'emp_name' => 'John Doe']];

    $filtered = RowSetHelper::filter($rows, 'emp_name', 'John Wayne');
    self::assertSame([], $filtered);

    $filtered = RowSetHelper::filter($rows, 'emp_name', 'John Wayne', true);
    self::assertSame($rows, $filtered);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method findInRowSet with a row set.
   */
  public function testFindInRowSet(): void
  {
    $rows = [['emp_id'   => 1,
              'emp_name' => 'Jane Doe'],
             ['emp_id'   => 2,
              'emp_name' => 'John Doe']];

    $key = RowSetHelper::findInRowSet($rows, 'emp_name', 'John Doe');
    self::assertSame(1, $key);

    $key = RowSetHelper::findInRowSet($rows, 'emp_name', 'Jane Doe');
    self::assertSame(0, $key);

    $key = RowSetHelper::findInRowSet($rows, 'emp_name', 'John Doe', true);
    self::assertSame(0, $key);

    $key = RowSetHelper::findInRowSet($rows, 'emp_name', 'Jane Doe', true);
    self::assertSame(1, $key);

    $key = RowSetHelper::findInRowSet($rows, 'emp_name', 'John Wayne', true);
    self::assertSame(0, $key);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method findInRowSet with a row set with no result.
   */
  public function testFindInRowSetNoResult1(): void
  {
    $rows = [['emp_id'   => 1,
              'emp_name' => 'Jane Doe'],
             ['emp_id'   => 2,
              'emp_name' => 'John Doe']];

    self::expectException(\LogicException::class);
    RowSetHelper::findInRowSet($rows, 'emp_name', 'John Wayne');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method findInRowSet with a row set with no result.
   */
  public function testFindInRowSetNoResult2(): void
  {
    $rows = [['emp_id'        => 1,
              'emp_is_person' => true,
              'emp_name'      => 'Jane Doe'],
             ['emp_id'        => 2,
              'emp_is_person' => true,
              'emp_name'      => 'John Doe']];

    self::expectException(\LogicException::class);
    RowSetHelper::findInRowSet($rows, 'emp_is_person', true, true);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method searchInRowSet with a row set.
   */
  public function testSearchInRowSet(): void
  {
    $rows = [['emp_id'   => 1,
              'emp_name' => 'Jane Doe'],
             ['emp_id'   => 2,
              'emp_name' => 'John Doe']];

    $key = RowSetHelper::searchInRowSet($rows, 'emp_name', 'John Doe');
    self::assertSame(1, $key);

    $key = RowSetHelper::searchInRowSet($rows, 'emp_name', 'John Doe', true);
    self::assertSame(0, $key);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method searchInRowSet with a row set with no result.
   */
  public function testSearchInRowSetNoResult(): void
  {
    $rows = [['emp_id'        => 1,
              'emp_is_person' => true,
              'emp_name'      => 'Jane Doe'],
             ['emp_id'        => 2,
              'emp_is_person' => true,
              'emp_name'      => 'John Doe']];

    $key = RowSetHelper::searchInRowSet($rows, 'emp_name', 'John Wayne');
    self::assertNull($key);

    $key = RowSetHelper::searchInRowSet($rows, 'emp_is_person', false);
    self::assertNull($key);

    $key = RowSetHelper::searchInRowSet($rows, 'emp_is_person', true, true);
    self::assertNull($key);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
