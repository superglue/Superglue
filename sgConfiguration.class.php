<?php
require(dirname(__FILE__) . '/sgContext.class.php');
require(dirname(__FILE__) . '/vendor/Twig/lib/Twig/Autoloader.php');

class sgConfiguration
{
  private static $instance;
  protected static $config;
  
  private function __construct()
  {
    sgContext::getInstance()->setRootDir(self::getRootDir());
    require_once(sgContext::getInstance()->getRootDir() . '/config/config.php');
    require_once(sgContext::getInstance()->getRootDir() . '/config/routing.php');
    self::set('settings', 'cache_dir', sgConfiguration::getRootDir() . '/cache');
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
      sgAutoloader::loadPaths(array(
        dirname(__FILE__) . '/../',
        sgContext::getRootDir() . '/config/',
        sgContext::getRootDir() . '/lib/',
        sgContext::getRootDir() . '/controllers/',
        sgContext::getRootDir() . '/models/',
      ));
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
  
  // this is used for adding a new config type with settings
  public static function createConfig($configType, $settings = null)
  {
    self::$config[$configType] = $settings;
  }
  
  public static function set($configType, $setting, $value)
  {
    self::$config[$configType][$setting] = $value;
  }
  
  public static function get($configType, $setting = NULL)
  {
    if ($setting) {
      return self::$config[$configType][$setting];
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
