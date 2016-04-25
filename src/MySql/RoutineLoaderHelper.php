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
namespace SetBased\Stratum\MySql;

use phpDocumentor\Reflection\DocBlock;
use SetBased\Exception\FallenException;
use SetBased\Exception\RuntimeException;
use SetBased\Stratum\MySql\Exception\DataLayerException;
use SetBased\Stratum\MySql\MetadataDataLayer as DataLayer;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for loading a single stored routine into a MySQL instance from pseudo SQL file.
 */
class RoutineLoaderHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The default character set under which the stored routine will be loaded and run.
   *
   * @var string
   */
  private $characterSet;

  /**
   * The default collate under which the stored routine will be loaded and run.
   *
   * @var string
   */
  private $collate;

  /**
   * The key or index columns (depending on the designation type) of the stored routine .
   *
   * @var string[]
   */
  private $columns;

  /**
   * The column types of columns of the table for bulk insert of the stored routine.
   *
   * @var string[]
   */
  private $columnsTypes;

  /**
   * The designation type of the stored routine.
   *
   * @var string
   */
  private $designationType;

  /**
   * All DocBlock parts as found in the source of the stored routine.
   *
   * @var array
   */
  private $docBlockPartsSource = [];

  /**
   * The DocBlock parts to be used by the wrapper generator.
   *
   * @var array
   */
  private $docBlockPartsWrapper;

  /**
   * Information about parameters with specific format (string in CSV format etc.) pass to the stored routine.
   *
   * @var array
   */
  private $extendedParameters;

  /**
   * The keys in the PHP array for bulk insert.
   *
   * @var string[]
   */
  private $fields;

  /**
   * The last modification time of the source file.
   *
   * @var int
   */
  private $filemtime;

  /**
   * The Output decorator
   *
   * @var StratumStyle
   */
  private $io;

  /**
   * The information about the parameters of the stored routine.
   *
   * @var array[]
   */
  private $parameters = [];

  /**
   * The metadata of the stored routine. Note: this data is stored in the metadata file and is generated by PhpStratum.
   *
   * @var array
   */
  private $phpStratumMetadata;

  /**
   * The old metadata of the stored routine.  Note: this data comes from the metadata file.
   *
   * @var array
   */
  private $phpStratumOldMetadata;

  /**
   * The old metadata of the stored routine. Note: this data comes from information_schema.ROUTINES.
   *
   * @var array
   */
  private $rdbmsOldRoutineMetadata;

  /**
   * The replace pairs (i.e. placeholders and their actual values, see strst).
   *
   * @var array
   */
  private $replace = [];

  /**
   * A map from placeholders to their actual values.
   *
   * @var array
   */
  private $replacePairs = [];

  /**
   * The name of the stored routine.
   *
   * @var string
   */
  private $routineName;

  /**
   * The source code as a single string of the stored routine.
   *
   * @var string
   */
  private $routineSourceCode;

  /**
   * The source code as an array of lines string of the stored routine.
   *
   * @var array
   */
  private $routineSourceCodeLines;

  /**
   * The stored routine type (i.e. procedure or function) of the stored routine.
   *
   * @var string
   */
  private $routineType;

  /**
   * The extension of the source file of the stored routine.
   *
   * @var string
   */
  private $sourceFileExtension;

  /**
   * The source filename holding the stored routine.
   *
   * @var string
   */
  private $sourceFilename;

  /**
   * The SQL mode under which the stored routine will be loaded and run.
   *
   * @var string
   */
  private $sqlMode;

  /**
   * If designation type is bulk_insert the table name for bulk insert.
   *
   * @var string
   */
  private $tableName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param StratumStyle $io                            The output for log messages.
   * @param string       $routineFilename               The filename of the source of the stored routine.
   * @param string       $routineFileExtension          The extension of the source file of the stored routine.
   * @param array        $phpStratumMetadata            The metadata of the stored routine from PhpStratum.
   * @param array        $replacePairs                  A map from placeholders to their actual values.
   * @param array        $rdbmsOldRoutineMetadata       The old metadata of the stored routine from MySQL.
   * @param string       $sqlMode                       The SQL mode under which the stored routine will be loaded and
   *                                                    run.
   * @param string       $characterSet                  The default character set under which the stored routine will
   *                                                    be loaded and run.
   * @param string       $collate                       The key or index columns (depending on the designation type) of
   *                                                    the stored routine.
   */
  public function __construct($io,
                              $routineFilename,
                              $routineFileExtension,
                              $phpStratumMetadata,
                              $replacePairs,
                              $rdbmsOldRoutineMetadata,
                              $sqlMode,
                              $characterSet,
                              $collate
  )
  {
    $this->io                      = $io;
    $this->sourceFilename          = $routineFilename;
    $this->sourceFileExtension     = $routineFileExtension;
    $this->phpStratumMetadata      = $phpStratumMetadata;
    $this->replacePairs            = $replacePairs;
    $this->rdbmsOldRoutineMetadata = $rdbmsOldRoutineMetadata;
    $this->sqlMode                 = $sqlMode;
    $this->characterSet            = $characterSet;
    $this->collate                 = $collate;
  }

//--------------------------------------------------------------------------------------------------------------------
  /**
   * Loads the stored routine into the instance of MySQL.
   *
   * @return array|false If the stored routine is loaded successfully the new mata data of the stored routine. Otherwise
   *                     false.
   */
  public function loadStoredRoutine()
  {
    try
    {
      // We assume that the basename of the routine file and routine name are equal.
      $this->routineName = basename($this->sourceFilename, $this->sourceFileExtension);

      // Save old metadata.
      $this->phpStratumOldMetadata = $this->phpStratumMetadata;

      // Get modification time of the source file.
      $this->filemtime = filemtime($this->sourceFilename);

      // Load the stored routine into MySQL only if the source has changed or the value of a placeholder.
      $load = $this->getMustReload();
      if ($load)
      {
        $this->io->text(sprintf('Loading routine <dbo>%s</dbo>', OutputFormatter::escape($this->routineName)));

        // Read the stored routine source code.
        $this->routineSourceCode = file_get_contents($this->sourceFilename);

        // Split the stored routine source code into lines.
        $this->routineSourceCodeLines = explode("\n", $this->routineSourceCode);
        if ($this->routineSourceCodeLines===false) return false;

        // Extract placeholders from the stored routine source code.
        $ok = $this->getPlaceholders();
        if ($ok===false) return false;

        // Extract the designation type and key or index columns from the stored routine source code.
        $ok = $this->getDesignationType();
        if ($ok===false) return false;

        // Extract the stored routine type (procedure or function) and stored routine name from the source code.
        $ok = $this->getName();
        if ($ok===false) return false;

        // Load the stored routine into MySQL.
        $this->loadRoutineFile();

        // If the stored routine is a bulk insert stored procedure, enhance metadata with table columns information.
        if ($this->designationType=='bulk_insert')
        {
          $this->getBulkInsertTableColumnsInfo();
        }

        // Get info about parameters with specific layout like cvs string etc. form the stored routine.
        $this->getExtendedParametersInfo();

        // Get the parameters types of the stored routine from metadata of MySQL.
        $this->getRoutineParametersInfo();

        // Compose the DocBlock parts to be used by the wrapper generator.
        $this->getDocBlockPartsWrapper();

        // Validate the parameters found the DocBlock in the source of the stored routine against the parameters from
        // the metadata of MySQL.
        $this->validateParameterLists();

        // Update Metadata of the stored routine.
        $this->updateMetadata();
      }

      return $this->phpStratumMetadata;
    }
    catch (DataLayerException $e)
    {
      if ($e->isQueryError())
      {
        // Exception is caused by a SQL error. Log the message and the SQL statement with highlighting the error.
        $this->io->error($e->getShortMessage());
        $this->io->text($e->getMarkedQuery());
      }
      else
      {
        $this->io->error($e->getMessage());
      }
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Converts MySQL data type to the PHP data type.
   *
   * @param string[] $parameterInfo
   *
   * @return string
   * @throws \Exception
   */
  private function columnTypeToPhpType($parameterInfo)
  {
    switch ($parameterInfo['data_type'])
    {
      case 'tinyint':
      case 'smallint':
      case 'mediumint':
      case 'int':
      case 'bigint':

      case 'year':

      case 'bit':
        $php_type = 'int';
        break;

      case 'decimal':
        $php_type = ($parameterInfo['numeric_scale']=='0') ? 'int' : 'float';
        break;

      case 'float':
      case 'double':
        $php_type = 'float';
        break;

      case 'varbinary':
      case 'binary':

      case 'char':
      case 'varchar':

      case 'time':
      case 'timestamp':

      case 'date':
      case 'datetime':

      case 'enum':
      case 'set':

      case 'tinytext':
      case 'text':
      case 'mediumtext':
      case 'longtext':

      case 'tinyblob':
      case 'blob':
      case 'mediumblob':
      case 'longblob':
        $php_type = 'string';
        break;

      case 'list_of_int':
        $php_type = 'string|int[]';
        break;

      default:
        throw new FallenException('column type', $parameterInfo['data_type']);
    }

    return $php_type;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops the stored routine if it exists.
   */
  private function dropRoutine()
  {
    if (isset($this->rdbmsOldRoutineMetadata))
    {
      DataLayer::dropRoutine($this->rdbmsOldRoutineMetadata['routine_type'], $this->routineName);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *  Gets the column names and column types of the current table for bulk insert.
   */
  private function getBulkInsertTableColumnsInfo()
  {
    // Check if table is a temporary table or a non-temporary table.
    $table_is_non_temporary = DataLayer::checkTableExists($this->tableName);

    // Create temporary table if table is non-temporary table.
    if (!$table_is_non_temporary)
    {
      DataLayer::callProcedure($this->routineName);
    }

    // Get information about the columns of the table.
    $columns = DataLayer::describeTable($this->tableName);

    // Drop temporary table if table is non-temporary.
    if (!$table_is_non_temporary)
    {
      DataLayer::dropTemporaryTable($this->tableName);
    }

    // Check number of columns in the table match the number of fields given in the designation type.
    $n1 = count($this->columns);
    $n2 = count($columns);
    if ($n1!=$n2)
    {
      throw new RuntimeException("Number of fields %d and number of columns %d don't match.", $n1, $n2);
    }

    // Fill arrays with column names and column types.
    $tmp_column_types = [];
    $tmp_fields       = [];
    foreach ($columns as $column)
    {
      preg_match('(\\w+)', $column['Type'], $type);
      $tmp_column_types[] = $type['0'];
      $tmp_fields[]       = $column['Field'];
    }

    $this->columnsTypes = $tmp_column_types;
    $this->fields       = $tmp_fields;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the designation type of the stored routine.
   *
   * @return bool True on success. Otherwise returns false.
   */
  private function getDesignationType()
  {
    $ret = true;
    $key = array_search('begin', $this->routineSourceCodeLines);

    if ($key!==false)
    {
      for ($i = 1; $i<$key; $i++)
      {
        $n = preg_match('/^\s*--\s+type:\s*(\w+)\s*(.+)?\s*$/',
                        $this->routineSourceCodeLines[$key - $i],
                        $matches);
        if ($n==1)
        {
          $this->designationType = $matches[1];
          switch ($this->designationType)
          {
            case 'bulk_insert':
              $m = preg_match('/^([a-zA-Z0-9_]+)\s+([a-zA-Z0-9_,]+)$/',
                              $matches[2],
                              $info);
              if ($m==0)
              {
                throw new RuntimeException("Error: Expected: -- type: bulk_insert <table_name> <columns> in file '%s'.",
                                           $this->sourceFilename);
              }
              $this->tableName = $info[1];
              $this->columns   = explode(',', $info[2]);
              break;

            case 'rows_with_key':
            case 'rows_with_index':
              $this->columns = explode(',', $matches[2]);
              break;

            default:
              if (isset($matches[2])) $ret = false;
          }
          break;
        }
        if ($i==($key - 1)) $ret = false;
      }
    }
    else
    {
      $ret = false;
    }

    if ($ret===false)
    {
      $this->io->error(sprintf("Unable to find the designation type of the stored routine in file '%s'",
                               $this->sourceFilename));
    }

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *  Extracts the DocBlock (in parts) from the source of the stored routine.
   */
  private function getDocBlockPartsSource()
  {
    // Get the DocBlock for the source.
    $tmp = '';
    foreach ($this->routineSourceCodeLines as $line)
    {
      $n = preg_match('/create\\s+(procedure|function)\\s+([a-zA-Z0-9_]+)/i', $line);
      if ($n) break;
      else $tmp .= $line."\n";
    }

    $phpdoc = new DocBlock($tmp);

    // Get the short description.
    $this->docBlockPartsSource['sort_description'] = $phpdoc->getShortDescription();

    // Get the long description.
    $this->docBlockPartsSource['long_description'] = $phpdoc->getLongDescription()->getContents();

    // Get the description for each parameter of the stored routine.
    foreach ($phpdoc->getTags() as $key => $tag)
    {
      if ($tag->getName()=='param')
      {
        $content     = $tag->getContent();
        $description = $tag->getDescription();

        // Gets name of parameter from routine doc block.
        $name = trim(substr($content, 0, strlen($content) - strlen($description)));

        $tmp   = [];
        $lines = explode("\n", $description);
        foreach ($lines as $line)
        {
          $tmp[] = trim($line);
        }
        $description = implode("\n", $tmp);

        $this->docBlockPartsSource['parameters'][$key] = ['name'        => $name,
                                                          'description' => $description];
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *  Generates the DocBlock parts to be used by the wrapper generator.
   */
  private function getDocBlockPartsWrapper()
  {
    // Get the DocBlock parts from the source of the stored routine.
    $this->getDocBlockPartsSource();

    // Generate the parameters parts of the DocBlock to be used by the wrapper.
    $parameters = [];
    foreach ($this->parameters as $parameter_info)
    {
      $parameters[] = ['name'                 => $parameter_info['name'],
                       'php_type'             => $this->columnTypeToPhpType($parameter_info),
                       'data_type_descriptor' => $parameter_info['data_type_descriptor'],
                       'description'          => $this->getParameterDocDescription($parameter_info['name'])];
    }

    // Compose all the DocBlock parts to be used by the wrapper generator.
    $this->docBlockPartsWrapper = ['sort_description' => $this->docBlockPartsSource['sort_description'],
                                   'long_description' => $this->docBlockPartsSource['long_description'],
                                   'parameters'       => $parameters];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets info of extended parameters.
   *
   * @throws \Exception
   */
  private function getExtendedParametersInfo()
  {
    $key = array_search('begin', $this->routineSourceCodeLines);

    if ($key!==false)
    {
      for ($i = 1; $i<$key; $i++)
      {
        $k = preg_match('/^\s*--\s+param:(?:\s*(\w+)\s+(\w+)(?:(?:\s+([^\s-])\s+([^\s-])\s+([^\s-])\s*$)|(?:\s*$)))?/',
                        $this->routineSourceCodeLines[$key - $i + 1],
                        $matches);

        if ($k==1)
        {
          $count = count($matches);
          if ($count==3 || $count==6)
          {
            $parameter_name = $matches[1];
            $data_type      = $matches[2];

            if ($count==6)
            {
              $list_delimiter = $matches[3];
              $list_enclosure = $matches[4];
              $list_escape    = $matches[5];
            }
            else
            {
              $list_delimiter = ',';
              $list_enclosure = '"';
              $list_escape    = '\\';
            }

            if (!isset($this->extendedParameters[$parameter_name]))
            {
              $this->extendedParameters[$parameter_name] = ['name'      => $parameter_name,
                                                            'data_type' => $data_type,
                                                            'delimiter' => $list_delimiter,
                                                            'enclosure' => $list_enclosure,
                                                            'escape'    => $list_escape];
            }
            else
            {
              throw new RuntimeException("Duplicate parameter '%s' in file '%s'.",
                                         $parameter_name,
                                         $this->sourceFilename);
            }
          }
          else
          {
            throw new RuntimeException("Error: Expected: -- param: <field_name> <type_of_list> [delimiter enclosure escape] in file '%s'.",
                                       $this->sourceFilename);
          }
        }
      }
    }
  }


  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if the source file must be load or reloaded. Otherwise returns false.
   *
   * @return bool
   */
  private function getMustReload()
  {
    // If this is the first time we see the source file it must be loaded.
    if (!isset($this->phpStratumOldMetadata)) return true;

    // If the source file has changed the source file must be loaded.
    if ($this->phpStratumOldMetadata['timestamp']!=$this->filemtime) return true;

    // If the value of a placeholder has changed the source file must be loaded.
    foreach ($this->phpStratumOldMetadata['replace'] as $place_holder => $old_value)
    {
      if (!isset($this->replacePairs[strtoupper($place_holder)]) ||
        $this->replacePairs[strtoupper($place_holder)]!==$old_value
      )
      {
        return true;
      }
    }

    // If stored routine not exists in database the source file must be loaded.
    if (!isset($this->rdbmsOldRoutineMetadata)) return true;

    // If current sql-mode is different the source file must reload.
    if ($this->rdbmsOldRoutineMetadata['sql_mode']!=$this->sqlMode) return true;

    // If current character is different the source file must reload.
    if ($this->rdbmsOldRoutineMetadata['character_set_client']!=$this->characterSet) return true;

    // If current collation is different the source file must reload.
    if ($this->rdbmsOldRoutineMetadata['collation_connection']!=$this->collate) return true;

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the name of the stored routine and the stored routine type (i.e. procedure or function) source.
   *
   * @todo Skip comments and string literals.
   * @return bool Returns true on success, false otherwise.
   */
  private function getName()
  {
    $ret = true;

    $n = preg_match('/create\\s+(procedure|function)\\s+([a-zA-Z0-9_]+)/i', $this->routineSourceCode, $matches);
    if ($n==1)
    {
      $this->routineType = strtolower($matches[1]);

      if ($this->routineName!=$matches[2])
      {
        $this->io->error(sprintf("Stored routine name '%s' does not corresponds with filename '%s'",
                                 $matches[2],
                                 $this->sourceFilename));
        $ret = false;
      }
    }
    else
    {
      $ret = false;
    }

    if (!isset($this->routineType))
    {
      $this->io->error(sprintf("Unable to find the stored routine name and type in file '%s'",
                               $this->sourceFilename));
    }

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets description by name of the parameter as found in the DocBlock of the stored routine.
   *
   * @param string $name Name of the parameter.
   *
   * @return string
   */
  private function getParameterDocDescription($name)
  {
    if (isset($this->docBlockPartsSource['parameters']))
    {
      foreach ($this->docBlockPartsSource['parameters'] as $parameter_doc_info)
      {
        if ($parameter_doc_info['name']===$name) return $parameter_doc_info['description'];
      }
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the placeholders from the stored routine source.
   *
   * @return bool True if all placeholders are defined, false otherwise.
   */
  private function getPlaceholders()
  {
    $unknown = [];

    preg_match_all('(@[A-Za-z0-9\_\.]+(\%type)?@)', $this->routineSourceCode, $matches);
    if (!empty($matches[0]))
    {
      foreach ($matches[0] as $placeholder)
      {
        if (isset($this->replacePairs[strtoupper($placeholder)]))
        {
          $this->replace[$placeholder] = $this->replacePairs[strtoupper($placeholder)];
        }
        else
        {
          $unknown[] = $placeholder;
        }
      }
    }

    $this->logUnknownPlaceholders($unknown);

    return (empty($unknown));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets info about the parameters of the stored routine.
   */
  private function getRoutineParametersInfo()
  {
    $routine_parameters = DataLayer::getRoutineParameters($this->routineName);
    foreach ($routine_parameters as $key => $routine_parameter)
    {
      if ($routine_parameter['parameter_name'])
      {
        $data_type_descriptor = $routine_parameter['dtd_identifier'];
        if (isset($routine_parameter['character_set_name']))
        {
          $data_type_descriptor .= ' character set '.$routine_parameter['character_set_name'];
        }
        if (isset($routine_parameter['collation_name']))
        {
          $data_type_descriptor .= ' collation '.$routine_parameter['collation_name'];
        }

        $routine_parameter['name']                 = $routine_parameter['parameter_name'];
        $routine_parameter['data_type_descriptor'] = $data_type_descriptor;

        $this->parameters[$key] = $routine_parameter;
      }
    }

    $this->updateParametersInfo();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Loads the stored routine into the database.
   */
  private function loadRoutineFile()
  {
    // Set magic constants specific for this stored routine.
    $this->setMagicConstants();

    // Replace all place holders with their values.
    $lines          = explode("\n", $this->routineSourceCode);
    $routine_source = [];
    foreach ($lines as $i => &$line)
    {
      $this->replace['__LINE__'] = $i + 1;
      $routine_source[$i]        = strtr($line, $this->replace);
    }
    $routine_source = implode("\n", $routine_source);

    // Unset magic constants specific for this stored routine.
    $this->unsetMagicConstants();

    // Drop the stored procedure or function if its exists.
    $this->dropRoutine();

    // Set the SQL-mode under which the stored routine will run.
    DataLayer::setSqlMode($this->sqlMode);

    // Set the default character set and collate under which the store routine will run.
    DataLayer::setCharacterSet($this->characterSet, $this->collate);

    // Finally, execute the SQL code for loading the stored routine.
    DataLayer::loadRoutine($routine_source);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *
   * @param array $unknown The unknown placeholders.
   */
  private function logUnknownPlaceholders($unknown)
  {
    // Return immediately if there are no unknown placeholders.
    if (empty($unknown)) return;

    sort($unknown);
    $this->io->error('Unknown placeholder found');
    $this->io->listing($unknown);

    $replace = [];
    foreach ($unknown as $placeholder)
    {
      $replace[$placeholder] = '<error>'.$placeholder.'</error>';
    }
    $code = strtr(OutputFormatter::escape($this->routineSourceCode), $replace);

    $this->io->text(explode(PHP_EOL, $code));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds magic constants to replace list.
   */
  private function setMagicConstants()
  {
    $real_path = realpath($this->sourceFilename);

    $this->replace['__FILE__']    = "'".DataLayer::realEscapeString($real_path)."'";
    $this->replace['__ROUTINE__'] = "'".$this->routineName."'";
    $this->replace['__DIR__']     = "'".DataLayer::realEscapeString(dirname($real_path))."'";
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes magic constants from current replace list.
   */
  private function unsetMagicConstants()
  {
    unset($this->replace['__FILE__']);
    unset($this->replace['__ROUTINE__']);
    unset($this->replace['__DIR__']);
    unset($this->replace['__LINE__']);
  }


  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates the metadata for the stored routine.
   */
  private function updateMetadata()
  {
    $this->phpStratumMetadata['routine_name'] = $this->routineName;
    $this->phpStratumMetadata['designation']  = $this->designationType;
    $this->phpStratumMetadata['table_name']   = $this->tableName;
    $this->phpStratumMetadata['parameters']   = $this->parameters;
    $this->phpStratumMetadata['columns']      = $this->columns;
    $this->phpStratumMetadata['fields']       = $this->fields;
    $this->phpStratumMetadata['column_types'] = $this->columnsTypes;
    $this->phpStratumMetadata['timestamp']    = $this->filemtime;
    $this->phpStratumMetadata['replace']      = $this->replace;
    $this->phpStratumMetadata['phpdoc']       = $this->docBlockPartsWrapper;
    $this->phpStratumMetadata['spec_params']  = $this->extendedParameters;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Update information about specific parameters of stored routine.
   *
   * @throws \Exception
   */
  private function updateParametersInfo()
  {
    if (!empty($this->extendedParameters))
    {
      foreach ($this->extendedParameters as $spec_param_name => $spec_param_info)
      {
        $param_not_exist = true;
        foreach ($this->parameters as $key => $param_info)
        {
          if ($param_info['name']==$spec_param_name)
          {
            $this->parameters[$key] = array_merge($this->parameters[$key], $spec_param_info);
            $param_not_exist        = false;
            break;
          }
        }
        if ($param_not_exist)
        {
          throw new RuntimeException("Specific parameter '%s' does not exist in file '%s'.",
                                     $spec_param_name,
                                     $this->sourceFilename);
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Validates the parameters found the DocBlock in the source of the stored routine against the parameters from the
   * metadata of MySQL and reports missing and unknown parameters names.
   */
  private function validateParameterLists()
  {
    // Make list with names of parameters used in database.
    $database_parameters_names = [];
    foreach ($this->parameters as $parameter_info)
    {
      $database_parameters_names[] = $parameter_info['name'];
    }

    // Make list with names of parameters used in dock block of routine.
    $doc_block_parameters_names = [];
    if (isset($this->docBlockPartsSource['parameters']))
    {
      foreach ($this->docBlockPartsSource['parameters'] as $parameter)
      {
        $doc_block_parameters_names[] = $parameter['name'];
      }
    }

    // Check and show warning if any parameters is missing in doc block.
    $tmp = array_diff($database_parameters_names, $doc_block_parameters_names);
    foreach ($tmp as $name)
    {
      $this->io->logNote('Parameter <dbo>%s</dbo> is missing from doc block', $name);
    }

    // Check and show warning if find unknown parameters in doc block.
    $tmp = array_diff($doc_block_parameters_names, $database_parameters_names);
    foreach ($tmp as $name)
    {
      $this->io->logNote('Unknown parameter <dbo>%s</dbo> found in doc block', $name);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
