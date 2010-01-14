<?php
require(dirname(__FILE__) . '/sgFilteredDirectoryIterator.class.php');

class sgAutoloader
{
  private static $instance;
  private static $_paths = array();
  private static $_exclusions = array();
  private static $_cache = array();
  private static $isCached = false;
  
  
  public function __construct()
  {
    //init
  }
  
  public static function setExclusions($exclusions = array(), $append = true)
  {
    foreach ($exclusions as &$exclusion)
    {
      if (strpos($exclusion, '/') !== false)
      {
        $exclusion = realpath($exclusion);
      }
    }
    if ($append)
    {
      self::$_exclusions = array_merge(self::$_exclusions, $exclusions);
    }
    else
    {
      self::$_exclusions = $exclusions;
    }
  }
  
  public static function loadPaths(array $paths, $extension = '.class.php', $clearCache = false)
  {
    if ($clearCache)
    {
      self::$_cache = array();
    }
    foreach ($paths as $path)
    {
      $path = realpath($path);
      $files = new RecursiveIteratorIterator(new sgFilteredDirectoryIterator($path, $extension, self::$_exclusions));
      foreach ($files as $file)
      {
        self::loadFile($file->getBaseName($extension), $file->getPathname());
      }
    }
  }
  
  public static function loadFile($name, $path)
  {
    self::$_cache[$name] = $path;
  }
  
  public static function getPaths()
  {
    return self::$_cache;
  }
  
  public static function getInstance()
  {
    if (!isset(self::$instance)) {
        $c = __CLASS__;
        self::$instance = new $c;
    }
    return self::$instance;
  }
  
  //only bootstrap the bare essentials
  public static function register()
  {
    self::loadFile('sgContext', realpath(dirname(__FILE__) . '/sgContext.class.php'));
    self::loadFile('sgConfiguration', realpath(dirname(__FILE__) . '/sgConfiguration.class.php'));
    spl_autoload_register(array(__CLASS__, 'loadClass'));
  }
  
  public static function checkCache()
  {
    if (sgConfiguration::get('settings', 'cache_autoload'))
    {
      $cacheFile = sgConfiguration::get('settings', 'cache_dir') . '/sgAutoloadCache.cache';
      if (file_exists($cacheFile))
      {
        self::$isCached = true;
        self::$_cache = unserialize(file_get_contents($cacheFile));
        return true;
      }
    }
    self::$isCached = false;
    //self::loadPaths(array(dirname(__FILE__) . '/../'));
    return false;
  }
  
  public static function loadClass($className)
  {
    if (isset(self::$_cache[$className]))
    {
      include(self::$_cache[$className]);
      return true;
    }
    
    return false;
  }
  
  public static function shutdown()
  {
    if (sgConfiguration::get('settings', 'cache_autoload'))
    {
      $cacheFile = sgConfiguration::get('settings', 'cache_dir') . '/sgAutoloadCache.cache';
      if (!file_exists($cacheFile))
      {
        $data = serialize(self::$_cache);
        file_put_contents($cacheFile, $data);
      }
    }
  }
}
