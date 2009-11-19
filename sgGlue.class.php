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
  static protected $cachedRoutes = null;
  /**
   * stick
   *
   * the main static function of the Glue class.
   *
   * @param   array  	$urls  	  The regex-based url to class mapping
   * @throws  Exception         Thrown if corresponding class is not found
   * @throws  Exception         Thrown if no match is found
   * @throws  BadMethodCallException  Thrown if a corresponding GET,POST is not found
   *
   */
  static public function checkRouteCache($path, $method)
  {
    if ($routes = self::getCachedRoutes() && isset($routes["$method $path"]))
    {
      self::dispatch($routes["$method $path"], $routes["$method $path"]['matches']);
    }
    
    return false;
  }
  
  static public function getCachedRoutes()
  {
    if (!is_null(self::$cachedRoutes)) {
      return self::$cachedRoutes;
    }
    $cacheFile = sgConfiguration::get('settings', 'cache_dir') . '/sgRouteCache.cache';
    if (is_file($cacheFile) && $contents = file_get_contents($cacheFile)) {
      self::$cachedRoutes = unserialize($contents);
      return $cachedRoutes;
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
          $obj->$method();
        } else {
          throw new BadMethodCallException("Method, $method, not supported.");
        }
      } else {
        throw new Exception("Class, $class, not found.");
      }
    }
    else {
      $obj = new sgBaseController($matches);
      $obj->$method();
    }
  }
  
  static public function saveCachedRoutes()
  {
    
    // self::$cacheRoutes['']
    // print '<pre>';
    // print_r('sdf');
    // print '</pre>';
    // exit;
    $data = serialize(self::$cachedRoutes);
    file_put_contents(sgConfiguration::get('settings', 'cache_dir') . '/sgRouteCache.cache', $data);
  }
  
  static function stick($routes)
  {
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    $path = sgContext::getCurrentPath();
    $found = false;
    //self::checkRouteCache($path, $method);
    foreach ($routes as $name => $route) {
      $regex = str_replace('/', '\/', $route['path']);
      $regex = '^' . $regex . '\/?$';
      if (preg_match("/$regex/i", $path, $matches)) {
        $found = true;
        $route['name'] = $name;
        $route['matches'] = $matches;
        self::$cachedRoutes["$method $path"] = $route;
        //self::saveCachedRoutes();
        self::dispatch($route, $method, $matches);
        break;
      }
    }
    if (!$found) {
      $route = array(
        'class' => 'sgStaticController',
        'method' => $method,
      );
      sgContext::setCurrentRoute($route);
      $obj = new sgStaticController();
      $obj->$method();
    }
    
    //self::saveCachedRoutes();
  }
}