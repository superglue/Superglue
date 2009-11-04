<?php
require(dirname(__FILE__) . '/sgFilteredDirectoryIterator.class.php');

class sgAutoloader
{
  private static $_cache = array();
  private static $instance;
  private static $_paths = array();
  private static $_exclusions = array('Twig', '.svn', 'CVS');
  
  
  function __construct()
  {
    //init
  }
  
  public static function setExclusions($exclusions = array(), $append = true)
  {
    if ($append)
    {
      self::$_exclusions = array_merge(self::$_exclusions, $exclusions);
    }
    else
    {
      self::$_exclusions = $exclusions;
    }
  }
  
  public static function loadPaths(array $paths, $clearCache = false)
  {
    if ($clearCache)
    {
      self::$_cache = array();
    }
    
    self::$_paths = $paths;
    foreach ($paths as $path)
    {
      $files = new RecursiveIteratorIterator(new sgFilteredDirectoryIterator($path, self:: $_exclusions));
      foreach ($files as $file)
      {
        self::$_cache[$file->getBaseName('.class.php')] = $file->getPathname();
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
  
  public static function register()
  {
    self::getInstance()->loadPaths(array(dirname(__FILE__) . '/../'));
    spl_autoload_register(array(__CLASS__, 'loadClass'));
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
}
