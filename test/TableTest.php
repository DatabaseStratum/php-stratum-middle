<?php
//----------------------------------------------------------------------------------------------------------------------
class TableTest extends DataLayerTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Stored routine with designation type table must show table.
   */
  public function test1()
  {

    $template_table = '
+---------+---------+---------+---------+---------------------+------+------+
| tst_c00 | tst_c01 | tst_c02 | tst_c03 |       tst_c04       |  t   |  s   |
+---------+---------+---------+---------+---------------------+------+------+
| Hello   |       1 |   0.543   1.23450   2014-03-27 00:00:00   4444      1 |
| World   |       3 |   0.00003   0.00000   2014-03-28 00:00:00             1 |
+---------+---------+---------+---------+---------------------+------+------+
';

    ob_start();
    DataLayer::testTable();
    $table = ob_get_contents();
    ob_end_clean();

    print_r($table);

   // $this->assertEquals( $table, $template_table );
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
