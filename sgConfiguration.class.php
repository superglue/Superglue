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
    self::_initPlugins(dirname(__FILE__), self::get('settings', 'enabled_plugins'));   //init core plugins
    $projectConfig = include realpath(sgContext::getInstance()->getRootDir() . '/config/config.php');
    if ($projectConfig && isset($projectConfig['enabled_plugins']))
    {
      self::_initPlugins(sgContext::getInstance()->getRootDir(), $projectConfig['enabled_plugins']);   //init project plugins
    }
    self::loadConfigFromArray('settings', $projectConfig);
    self::$enabledPlugins = self::get('settings', 'enabled_plugins', array()); //reload plugins to make sure we catch the project defined plugins
    self::loadConfig('routing', sgContext::getInstance()->getRootDir() . '/config/routing.php');
    self::_initAutoloader();
    self::_initPluginConfigurations();
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
    if ($checkFileExists && !file_exists($path))  // TODO This probably doesn't matter now, since I am using include
    {
      return false;
    }
    
    $loadedConfig = include $path;
    if (is_array($loadedConfig))
    {
      if (!empty($loadedConfig))
      {
        self::loadConfigFromArray($configType, $loadedConfig);
      }
    }
    else
    {
      throw new Exception('Configuration file "' . $path . '" does not return an array.');
    }
  }
  
  public static function loadConfigFromArray($configType, array $config)
  {
    $newConfig = array($configType => $config);
    self::$config = self::array_merge_recursive_distinct(self::$config, $newConfig);
  }
  
  /*
    TODO This method is terrible. Figure out a better way to handle it!
  */
  private static function _initPlugins($dir, array $plugins)
  {
    //$pendingPlugins = array_diff_assoc(self::get('settings', 'enabled_plugins', array()), self::$enabledPlugins);
    foreach ($plugins as $plugin)
    {
      self::loadConfig('settings', "$dir/plugins/$plugin/config/config.php", true);
      self::loadConfig('routing', "$dir/plugins/$plugin/config/routing.php", true);
    }
    self::$enabledPlugins = self::get('settings', 'enabled_plugins', array());
  }
  
  private static function _initPluginConfigurations()
  {
    $paths = sgAutoloader::getPaths();
    foreach (self::$enabledPlugins as $plugin)
    {
      $class = "{$plugin}Configuration";
      if (isset($paths[$class]))
      {
        //php 5.3 allows $class::init(), but I still want 5.2.x support
        call_user_func(array($class, 'init'));
      }
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
