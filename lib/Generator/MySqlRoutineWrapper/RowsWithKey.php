<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\DataLayer\Generator\MySqlRoutineWrapper;

use SetBased\DataLayer\Generator\MySqlRoutineWrapper;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class RowsWithKey
 *
 * @package SetBased\DataLayer\Generator\MySqlRoutineWrapper
 *
 * Class for generating a wrapper function around a stored procedure that selects 0 or more rows. The rows are
 * returned as nested arrays.
 */
class RowsWithKey extends MySqlRoutineWrapper
{
  //--------------------------------------------------------------------------------------------------------------------
  protected function writeResultHandler( $theRoutine )
  {
    $routine_args = $this->getRoutineArgs( $theRoutine );

    $key = '';
    foreach ($theRoutine['columns'] as $column)
    {
      $key .= '[$row[\''.$column.'\']]';
    }

    $this->writeLine( '$result = self::query( \'CALL '.$theRoutine['routine_name'].'('.$routine_args.')\');' );
    $this->writeLine( '$ret = array();' );
    $this->writeLine( 'while($row = $result->fetch_array( MYSQLI_ASSOC )) $ret'.$key.' = $row;' );
    $this->writeLine( '$result->close();' );
    $this->writeLine( 'self::$ourMySql->next_result();' );
    $this->writeLine( 'return  $ret;' );
  }

  //--------------------------------------------------------------------------------------------------------------------
  protected function writeRoutineFunctionLobFetchData( $theRoutine )
  {
    $key = '';
    foreach ($theRoutine['columns'] as $column)
    {
      $key .= '[$new[\''.$column.'\']]';
    }

    $this->writeLine( '$row = array();' );
    $this->writeLine( 'self::bindAssoc( $stmt, $row );' );
    $this->writeLine();
    $this->writeLine( '$ret = array();' );
    $this->writeLine( 'while (($b = $stmt->fetch()))' );
    $this->writeLine( '{' );
    $this->writeLine( '$new = array();' );
    $this->writeLine( 'foreach( $row as $key => $value )' );
    $this->writeLine( '{' );
    $this->writeLine( '$new[$key] = $value;' );
    $this->writeLine( '}' );
    $this->writeLine( '$ret'.$key.' = $new;' );
    $this->writeLine( '}' );
    $this->writeLine();
  }

  //--------------------------------------------------------------------------------------------------------------------
  protected function writeRoutineFunctionLobReturnData()
  {
    $this->writeLine( 'if ($b===false) self::sqlError( \'mysqli_stmt::fetch\' );' );
    $this->writeLine();
    $this->writeLine( 'return $ret;' );
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------