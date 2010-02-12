<?php

/*
  TODO Bring these in with the autoloader
*/
require(dirname(__FILE__) . '/sgContext.class.php');
require(dirname(__FILE__) . '/lib/sgToolkit.class.php');
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
    $projectConfig = null;
    if (file_exists(realpath(sgContext::getInstance()->getRootDir() . '/config/config.php')))
    {
      $projectConfig = include realpath(sgContext::getInstance()->getRootDir() . '/config/config.php');
      if (isset($projectConfig['enabled_plugins']))
      {
        self::_initPlugins(sgContext::getInstance()->getRootDir(), $projectConfig['enabled_plugins']);   //init project plugins
      }
      self::loadConfigFromArray('settings', $projectConfig);
    }
    self::$enabledPlugins = self::get('settings', 'enabled_plugins', array()); //reload plugins to make sure we catch the project defined plugins
    if (file_exists(realpath(sgContext::getInstance()->getRootDir() . '/config/routing.php')))
    {
      self::loadConfig('routing', sgContext::getInstance()->getRootDir() . '/config/routing.php');
    }
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
    try
    {
      $r = new ReflectionClass('ProjectConfiguration');
    }
    catch (Exception $e)
    {
      return getcwd();
    }
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
      if (class_exists('ProjectConfiguration'))
      {
        self::$instance = new ProjectConfiguration();
      }
      else
      {
         $c = __CLASS__;
         self::$instance = new $c;
      }
    }
    return self::$instance;
  }
  
  public static function loadConfig($configType, $path, $checkFileExists = false)
  {
    $loadedConfig = self::loadConfigFile($path, $checkFileExists);

    if ($loadedConfig)
    {
      self::loadConfigFromArray($configType, $loadedConfig);
    }
    else
    {
      return false;
    }
  }
  
  public static function loadConfigFile($path, $checkFileExists = false)
  {
    $path = realpath($path);
    if ($checkFileExists && !file_exists($path))  // TODO This probably doesn't matter now, since I am using include
    {
      return false;
    }
    
    $loadedConfig = include $path;
    if (!is_array($loadedConfig) || empty($loadedConfig))
    {
      throw new Exception('Configuration file "' . $path . '" does not return an array.');
    }
    
    return $loadedConfig;
  }
  
  public static function loadConfigFromArray($configType, array $config)
  {
    $newConfig = array($configType => $config);
    self::$config = sgToolkit::arrayMergeRecursiveDistinct(self::$config, $newConfig);
  }
  
  public static function getPlugins()
  {
    return self::$enabledPlugins;
  }
  
  public static function executePluginHook($plugins, $hook)
  {
    if (!is_array($plugins))
    {
      $plugins = array($plugins);
    }
    
    foreach ($plugins as $plugin)
    {
      $class = "{$plugin}Configuration";
      if (method_exists($class, $hook))
      {
        //php 5.3 allows $class::init(), but I still want 5.2.x support
        call_user_func(array($class, $hook));
      }
    }
  }
  
  /*
    TODO This method is terrible. Figure out a better way to handle it!
  */
  private static function _initPlugins($dir, array $plugins)
  {
    $names = $plugins;
    array_walk($plugins, create_function('&$plugin, $key, $dir', '$plugin = "$dir/plugins/$plugin";'), $dir);
    $plugins = array_combine($names, $plugins);
    sgAutoloader::loadPaths($plugins);
    
    self::executePluginHook(array_keys($plugins), 'preConfig');
    
    foreach ($plugins as $name => $path)
    {
      self::loadConfig('settings', "$path/config/config.php", true);
      self::loadConfig('routing', "$path/config/routing.php", true);
    }
    self::$enabledPlugins = self::get('settings', 'enabled_plugins', array());

    self::executePluginHook(array_keys($plugins), 'postConfig');
  }
  
  private static function _initPluginConfigurations()
  {
    $plugins = self::getPlugins();
    self::executePluginHook($plugins, 'init');
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
