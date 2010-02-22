<?php

class sgContext
{
  private static $instance;
  
  protected static $currentRoute;
  protected static $environment;
  protected static $rootDir;
  protected $controller;
  
  private function __construct()
  {
    self::$environment = $_SERVER;
    return;
  }
  
  public static function getInstance()
  {
    if (!isset(self::$instance)) {
        $c = __CLASS__;
        self::$instance = new $c;
    }
    return self::$instance;
  }
  
  public static function getCurrentPath()
  {
    if (isset($_GET['q'])) {
      return '/' . $_GET['q'];
    }
    
    return '/';
  }
  
  public static function getEnvironment()
  {
    return self::$environment;
  }
  
  public static function getRelativeBaseUrl()
  {
    return str_replace('/index.php', '', $_SERVER['PHP_SELF']);
  }
  
  public static function setRootDir($dir)
  {
    self::$rootDir = $dir;
  }
  
  public static function getRootDir()
  {
    return self::$rootDir;
  }
  
  public static function setCurrentRoute($route)
  {
    self::$currentRoute = $route;
  }
  
  public static function getCurrentRoute()
  {
    return self::$currentRoute;
  }
  
  public function setController($controller)
  {
    $this->controller = $controller;
  }
  
  public function getController()
  {
    return $this->controller;
  }
  
  public static function isAjaxRequest()
  {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
      if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        return true;
      }
    }

    return false;
  }
  
  public function __clone()
  {
    trigger_error('Only one instance of a singleton is allowed.', E_USER_ERROR);
  }
}
