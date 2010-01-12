<?php

/**
 * Glue
 *
 * Provides an easy way to map URLs to classes. URLs can be literal
 * strings or regular expressions.
 *
 * When the URLs are processed:
 *    * deliminators (/) are automatically escaped: (\/)
 *    * The beginning and end are anchored (^ $)
 *    * An optional end slash is added (/?)
 *	  * The i option is added for case-insensitive searches
 *
 * Example:
 *
 * $urls = array(
 *   '/' => 'index',
 *   '/page/(\d+) => 'page'
 * );
 *
 * class page {
 *    function GET($matches) {
 *      echo "Your requested page " . $matches[1];
 *    }
 * }
 *
 * Glue::stick($urls);
 *
 */
class sgGlue {
  static public $cachedRoutes = null;
  
  static public function checkRouteCache($path, $method)
  {
    if (self::getCachedRoutes() && isset(self::$cachedRoutes["$method $path"]))
    {
      return self::$cachedRoutes["$method $path"];
    }
    
    return false;
  }
  
  static public function getCachedRoutes()
  {
    if (!is_null(self::$cachedRoutes)) {
      return self::$cachedRoutes;
    }
    $cacheFile = sgConfiguration::get('settings', 'cache_dir') . '/sgRouteCache.cache';
    if (is_file($cacheFile) && $contents = file_get_contents($cacheFile))
    {
      self::$cachedRoutes = unserialize($contents);
      
      return true;
    }
    
    return false;
  }
  
  static public function dispatch($route, $method, $matches)
  {
    sgContext::setCurrentRoute($route);
    if (isset($route['class'])) {
      if (class_exists($route['class'])) {
        $obj = new $route['class']($matches);
        if (method_exists($obj, $method)) {
          print $obj->$method();
        } else {
          throw new BadMethodCallException("Method, $method, not supported.");
        }
      } else {
        throw new Exception("Class, $class, not found.");
      }
    }
    else {
      $obj = new sgBaseController($matches);
      print $obj->$method();
    }
  }
  
  static public function saveCachedRoutes()
  {
    $data = serialize(self::$cachedRoutes);
    file_put_contents(sgConfiguration::get('settings', 'cache_dir') . '/sgRouteCache.cache', $data);
  }
  
  static function stick($routes)
  {
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    $path = sgContext::getCurrentPath();
    $matchedRoute = null;
    
    if (sgConfiguration::get('settings', 'cache_routes'))
    {
      $matchedRoute = self::checkRouteCache($path, $method);
    }
    
    if (!$matchedRoute)
    {
      foreach ($routes as $name => $route)
      {
        $matches = array();
        $regex = str_replace('/', '\/', $route['path']);
        $regex = '^' . $regex . '\/?$';
        if (preg_match("/$regex/i", $path, $matches))
        {
          $route['name'] = $name;
          $route['matches'] = $matches;
          $route['path'] = $path;
          $matchedRoute = $route;
          self::$cachedRoutes["$method $path"] = $matchedRoute;
          break;
        }
      }
    }
    
    if (!$matchedRoute)
    {
      $matchedRoute = array(
        'path' => $path,
        'class' => 'sgStaticController',
        'method' => $method,
        'matches' => array(),
      );
      
      self::$cachedRoutes["$method $path"] = $matchedRoute;
    }
    
    self::dispatch($matchedRoute, $method, $matchedRoute['matches']);
    self::shutdown();
    sgAutoloader::shutdown();
  }
  
  public static function shutdown()
  {
    if (sgConfiguration::get('settings', 'cache_routes'))
    {
      self::saveCachedRoutes();
    }
  }
}