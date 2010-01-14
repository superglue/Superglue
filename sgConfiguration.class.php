<?php
require(dirname(__FILE__) . '/sgContext.class.php');
require(dirname(__FILE__) . '/vendor/Twig/lib/Twig/Autoloader.php');

class sgConfiguration
{
  private static $instance;
  protected static $config = array();
  protected static $enabledPlugins = array();
  
  private function __construct()
  {
    sgContext::getInstance()->setRootDir(self::getRootDir());
    
    self::loadConfig('settings', dirname(__FILE__) . '/config/config.php');
    self::loadConfig('settings', sgContext::getInstance()->getRootDir() . '/config/config.php');
    
    self::$enabledPlugins = self::get('settings', 'enabled_plugins', array());
    foreach (self::$enabledPlugins as $plugin)
    {
      self::loadConfig('settings', sgContext::getInstance()->getRootDir() . "/plugins/$plugin/config/config.php", true);
      self::loadConfig('routing', sgContext::getInstance()->getRootDir() . "/plugins/$plugin/config/routing.php", true);
    }
    
    self::loadConfig('routing', sgContext::getInstance()->getRootDir() . '/config/routing.php');
    
    self::_initAutoloader();
    
    $this->init();
    
    return;
  }
  
  public function init() {}
  
  public function execute()
  {
    sgGlue::stick(sgConfiguration::getRouting());
  }
  
  public static function getRootDir()
  {
    $r = new ReflectionClass('ProjectConfiguration');
    return realpath(dirname($r->getFileName()) . '/..');
  }

  private static function _initAutoloader()
  {
    if (!sgAutoloader::checkCache())
    {
      $paths = array(dirname(__FILE__) . '/../');
      foreach (self::$enabledPlugins as $plugin)
      {
        $paths[] = sgContext::getRootDir() . "/plugins/$plugin/";
      }
      $paths[] = sgContext::getRootDir() . '/config/';
      $paths[] = sgContext::getRootDir() . '/lib/';
      $paths[] = sgContext::getRootDir() . '/controllers/';
      $paths[] = sgContext::getRootDir() . '/models/';
      
      sgAutoloader::setExclusions(self::get('settings', 'autoload_exclusions', array()));
      sgAutoloader::loadPaths($paths);
    }
    Twig_Autoloader::register();
  }
  
  public static function getInstance()
  {
    if (!isset(self::$instance))
    {
      //$c = __CLASS__;
      /*
        TODO figure out a better way to do this (LSB in 5.3 maybe)
      */
      self::$instance = new ProjectConfiguration();
    }
    return self::$instance;
  }
  
  public static function loadConfig($configType, $path, $checkFileExists = false)
  {
    $path = realpath($path);
    if ($checkFileExists && !file_exists($path))
    {
      return false;
    }
    
    $loadedConfig = require_once $path;
    if (is_array($loadedConfig))
    {
      if (!empty($loadedConfig))
      {
        $config = array($configType => $loadedConfig);
        self::$config = self::array_merge_recursive_distinct(self::$config, $config);
      }
    }
    else
    {
      throw new Exception('Configuration file "' . $path . '" does not return an array.');
    }
  }
  
  // modified from http://us.php.net/manual/en/function.array-merge-recursive.php#92195
  private static function array_merge_recursive_distinct(array &$array1, array &$array2)
  {
    $merged = $array1;
    
    foreach ($array2 as $key => &$value)
    {
      if (is_int($key))
      {
        $merged[] = $value;
      }
      else if (is_array($value) && !empty($value) && isset($merged[$key]) && is_array($merged[$key]))
      {
        $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
      }
      else
      {
        $merged[$key] = $value;
      }
    }
    
    return $merged;
  }
  
  public static function set($configType, $setting, $value)
  {
    self::$config[$configType][$setting] = $value;
  }
  
  public static function get($configType, $setting = null, $default = null)
  {
    if ($setting) {
      return isset(self::$config[$configType][$setting]) ? self::$config[$configType][$setting] : $default;
    }
    else {
      return self::$config[$configType];
    }
  }
  
  public static function getRouting()
  {
    return self::get('routing');
  }
  
  public function __clone()
  {
      trigger_error('Only one instance of a singleton is allowed.', E_USER_ERROR);
  }
}
