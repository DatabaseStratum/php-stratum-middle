<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Stratum\Test\MySql;

use SetBased\Exception\RuntimeException;

//----------------------------------------------------------------------------------------------------------------------
class ExceptionTest extends DataLayerTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @expectedException RuntimeException
   */
  public function test1()
  {
    DataLayer::testIllegalQuery();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------