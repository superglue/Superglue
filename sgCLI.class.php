<?php

/**
* CLI class
*/
class sgCLI
{
  const
    STYLE_ERROR = 'error',
    STYLE_HEADER = 'header',
    STYLE_INFO = 'info',
    STYLE_COMMENT = 'comment',
    STYLE_CONFIRM = 'confirm';
  
  private static $instance;
  
  protected $tasks = array();
  protected $aliases = array();
  
  protected static 
    $newline = PHP_EOL,
    $ansiCodes = array(
      'fg' => array(
        'black' => '30', 'red' => '31', 'green' => '32', 'yellow' => '33',
        'blue' => '34', 'magenta' => '35', 'cyan' => '36', 'white' => '37'
      ),
      'bg' => array(
        'black' => '40', 'red' => '41', 'green' => '42', 'yellow' => '43',
        'blue' => '44', 'magenta' => '45', 'cyan' => '46', 'white' => '47'
      ),
      'options' => array(
        'normal' => '0', 'bright' => '1', 'dim' => '2', 'underscore' => '4',
        'blink' => '5', 'reverse' => '7', 'hidden' => '8'
      ),
    ),
    $styles = array(
      'error'    => array('bg' => 'red', 'fg' => 'white', 'options' => array('bright')),
      'info'     => array('fg' => 'green', 'options' => array('bright')),
      'header'   => array('fg' => 'green', 'options' => array('bright', 'underscore')),
      'comment'  => array('fg' => 'yellow', 'options' => array('bright')),
      'confirm'  => array('bg' => 'cyan', 'fg' => 'black'),
    );
  
  private function __construct()
  {
    foreach (sgAutoloader::getPaths() as $class => $path)
    {
      $class = basename($class);
      
      //ignore the base task class
      if ($class == 'sgTask')
      {
        continue;
      }
      
      if (stripos(strrev($class), 'ksaT') === 0)
      {
        //This is needed because PHP 5.2.x does not support late static binding
        call_user_func(array($class, 'configure'));
        $currentTaskGroup = call_user_func(array($class, 'getTasks'));
        foreach ($currentTaskGroup as $namespace => $taskList)
        {
          if (!isset($this->tasks[$namespace]))
          {
            $this->tasks[$namespace] = array();
          }
          foreach ($taskList as $command => $settings)
          {
            if (isset($settings['aliases']))
            {
              foreach ($settings['aliases'] as $alias)
              {
                $this->aliases[$namespace] = array($alias => $command);
              }
            }
            $this->tasks[$namespace][$command] = $settings;
            $this->tasks[$namespace][$command]['class'] = $class;
          }
        }
      }
    }
  }
  
  public static function getInstance()
  {
    if (!isset(self::$instance)) {
        $c = __CLASS__;
        self::$instance = new $c;
    }
    return self::$instance;
  }
  
  public function __clone()
  {
    trigger_error('Only one instance of a singleton is allowed.', E_USER_ERROR);
  }
  
  public function getParameters()
  {
    $params = array();
    sgAutoloader::loadFile('Console_Getopt', dirname(__FILE__) . '/vendor/Console/Getopt.php');
    $cg = new Console_Getopt();
    $params = $cg->readPHPArgv();
    array_shift($params);

    return $params;
  }
  
  public function getTasks()
  {
    return $this->getInstance()->tasks;
  }
  
  public function execute()
  {
    $namespace = 'core';
    $task = 'help';
    $cliParams = $this->getInstance()->getParameters();
    $parameters = array();
    
    if (isset($cliParams[0]))
    {
      if (strpos($cliParams[0], ':') !== false)
      {
        list($namespace, $task) = explode(':', $cliParams[0], 2);
      }
      else
      {
        $task = $cliParams[0];
      }
      
      $parameters = array_slice($cliParams, 2);
    }
    
    if (isset($this->tasks[$namespace][$task]))
    {
      return self::executeTask($this->tasks[$namespace][$task]['class'], $namespace, $task, $cliParams);
    }
    else if (isset($this->aliases[$namespace][$task]))
    {
      $task = $this->aliases[$namespace][$task];
      return self::executeTask($this->tasks[$namespace][$task]['class'], $namespace, $task, $cliParams);
    }
    
    self::error('Task "' . $cliParams[0] . '" does not exist. Run "superglue help" to get a list of available tasks.');
    return false;
  }
  
  public static function executeTask($taskClass, $namespace, $task, $cliParams = array())
  {
    $taskObject = new $taskClass();
    return $taskObject->execute($namespace, $task, $cliParams);
  }
  
  public static function println($text = '', $style = null)
  {
    if ($style)
    {
      $text = self::formatText($text, $style);
    }
    
    fputs(STDOUT, $text . self::$newline);
  }
  
  public static function formatText($text, $style)
  {
    $activeCodes = array();
    if (isset(self::$styles[$style]['options']))
    {
      foreach (self::$styles[$style]['options'] as $option)
      {
        $activeCodes[] = self::$ansiCodes['options'][$option];
      }
    }
    if (isset(self::$styles[$style]['bg']))
    {
      $activeCodes[] = self::$ansiCodes['bg'][self::$styles[$style]['bg']];
    }
    if (isset(self::$styles[$style]['fg']))
    {
      $activeCodes[] = self::$ansiCodes['fg'][self::$styles[$style]['fg']];
    }
    
    return "\033[" . implode(';', $activeCodes) . 'm' . $text . "\033[0m";
  }
  
  public static function error($text)
  {
    self::printBlock($text, self::STYLE_ERROR);
  }
  
  public static function confirm($text)
  {
    if (is_array($text))
    {
      $text[count($text) - 1] = $text[count($text) - 1] . ' (y/N)';
    }
    else
    {
      $text .= ' (y/N)';
    }
    self::printBlock($text, self::STYLE_CONFIRM);
    $confirm = fgets(STDIN);
    if (trim($confirm) == 'y')
    {
      return true;
    }
    self::println('cancelled', self::STYLE_INFO);
    self::shutdown();
  }
  
  public static function printAction($action, $line)
  {
    $width = 9 + strlen(sgCLI::formatText('', sgCLI::STYLE_INFO));
    $line = sgCLI::println(sprintf("%-${width}s %s", sgCLI::formatText($action, sgCLI::STYLE_INFO), $line));
  }
  
  public static function printBlock($text, $style = null)
  {
    $width = 0;
    
    if (!is_array($text))
    {
      $text = preg_split('/[\r\n]/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }
    
    foreach ($text as $line)
    {
      if (strlen($line) > $width)
      {
        $width = strlen($line);
      }
    }
    $blank = str_repeat(' ', $width + 4);
    self::println($blank, $style);
    foreach ($text as $line)
    {
      $spaces = '';
      if (strlen($line) < $width)
      {
        $spaces = str_repeat(' ', $width - strlen($line));
      }
      self::println("  $line$spaces  ", $style);
    }
    self::println($blank, $style);
  }
  
  public static function handleException($exception)
  {
    sgCLI::error($exception->getMessage());
    sgCLI::println($exception);
  }
  
  public static function shutdown($statusCode = 0)
  {
    exit($statusCode);
  }
}
