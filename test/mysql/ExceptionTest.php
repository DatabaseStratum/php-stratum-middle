<?php
//----------------------------------------------------------------------------------------------------------------------
/**
 * PhpStratum
 *
 * @copyright 2005-2015 Paul Water / Set Based IT Consultancy (https://www.setbased.nl)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link
 */
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
