<?php
require(dirname(__FILE__) . '/sgContext.class.php');
require(dirname(__FILE__) . '/vendor/Twig/Autoloader.php');

class sgConfiguration
{
  private static $instance;
  protected static $config;
  
  private function __construct()
  {
    sgContext::getInstance()->setRootDir(self::getRootDir());
    require_once(sgContext::getInstance()->getRootDir() . '/config/config.php');
    require_once(sgContext::getInstance()->getRootDir() . '/config/routing.php');
    self::add('settings', array('cache_dir' => sgConfiguration::getRootDir() . '/cache'));
    self::_initAutoloader();
    
    return;
  }
  
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
    sgAutoloader::setClassPaths(array(
      dirname(__FILE__) . '/../',
      sgContext::getRootDir() . '/config/',
      sgContext::getRootDir() . '/lib/',
      sgContext::getRootDir() . '/controllers/',
      sgContext::getRootDir() . '/models/',
    ));
    sgAutoloader::setCacheFilePath(self::get('settings', 'cache_dir') . '/sgAutoloadCache.cache');
    Twig_Autoloader::register();
  }
  
  public static function getInstance()
  {
    if (!isset(self::$instance)) {
        $c = __CLASS__;
        self::$instance = new $c;
    }
    return self::$instance;
  }
  
  public static function add($configType, $settings)
  {
    self::$config[$configType] = $settings;
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
