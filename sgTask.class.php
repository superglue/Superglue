<?php

/**
* Abstract Base Task
*/
abstract class sgTask
{
  protected static $tasks = array();
  
  public function __construct()
  {
    $this->configure();
  }
  
  abstract public static function configure();
  
  public final function execute($namespace, $task, $cliParams = array())
  {
    //special case for init-project
    if ("$namespace:$task" !== 'core:init-project' && "$namespace:$task" !== 'core:help')
    {
      if (!file_exists(sgContext::getInstance()->getRootDir() . '/config/ProjectConfiguration.class.php'))
      {
        sgCLI::error("Task \"$namespace:$task\" can only be executed in a project directory.");
        return false;
      }
    }
    if ($params = $this->parseParams($namespace, $task, $cliParams))
    {
      $executeMethod = sgToolkit::camelCase("execute $namespace $task");
      $this->$executeMethod($params['arguments'], $params['options']);
      return true;
    }
    
    return false;
  }
  
  protected function parseParams($namespace, $task, $cliParams = array())
  {
    sgAutoloader::loadFile('Console_Getopt', dirname(__FILE__) . '/vendor/Console/Getopt.php');
    $arguments = array();
    $options = array();
    $taskDefinition = self::getTask($namespace, $task);

    if (isset($taskDefinition['options']) || isset($taskDefinition['arguments']))
    {
      if (!isset($taskDefinition['arguments']))
      {
        $taskDefinition['arguments'] = array();
      }
      if (!isset($taskDefinition['options']['short']))
      {
        $taskDefinition['options']['short'] = null;
      }
      if (!isset($taskDefinition['options']['long']))
      {
        $taskDefinition['options']['long'] = array();
      }
      
      try
      {
        $params = Console_Getopt::getopt($cliParams, $taskDefinition['options']['short'], $taskDefinition['options']['long']);
        if (!empty($taskDefinition['arguments']) && (!isset($params[1]) || count($taskDefinition['arguments']) !== count($params[1])))
        {
          throw new Exception('Missing required argument.');
        }
        
        $arguments = array();
        if (!empty($taskDefinition['arguments']))
        {
          $arguments = array_combine($taskDefinition['arguments'], $params[1]);
        }
        
        $options = array();
        foreach ($params[0] as $param)
        {
          $options[$param[0]] = $param[1];
        }
      }
      catch (Exception $e)
      {
        $error = array();
        $error[] = $e->getMessage();
        if (isset($taskDefinition['usage']))
        {
          $error[] = 'Usage: ' . $taskDefinition['usage'];
        }
        sgCLI::error($error);
        return false;
      }
    }
    
    return array('arguments' => $arguments, 'options' => $options);
  }
  
  public static function getTask($namespace, $task)
  {
    return self::$tasks[$namespace][$task];
  }
  
  public static function getTasks()
  {
    return self::$tasks;
  }
  
}
