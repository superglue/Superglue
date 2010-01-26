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
 *
 * Glue::stick($urls);
 *
 */
class sgGlue {
  public static $cachedRoutes = null;
  public static $allowedMethods = array('GET', 'POST', 'PUT', 'DELETE');
  
  
  public static function checkRouteCache($path, $method)
  {
    if (self::getCachedRoutes() && isset(self::$cachedRoutes["$method $path"]))
    {
      return self::$cachedRoutes["$method $path"];
    }
    
    return false;
  }
  
  public static function getCachedRoutes()
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
  
  public static function dispatch($route, $method, $matches)
  {
    sgContext::setCurrentRoute($route);
    if (isset($route['disabled']) && $route['disabled'] == true)
    {
      if (sgConfiguration::get('settings', 'debug'))
      {
        exit('<pre>Route "' . $route['name'] . '" is disabled.' . "\n</pre>");
      }
      $obj = new sgBaseController($matches);
      print $obj->throwErrorCode('404');
    }
    else
    {
      if (isset($route['class']))
      {
        if (class_exists($route['class']))
        {
          $obj = new $route['class']($matches);
          if (method_exists($obj, $method))
          {
            print $obj->$method();
          }
          else
          {
            throw new BadMethodCallException("Method, $method, not supported");
          }
        }
        else
        {
          throw new Exception('Class, ' . $route['class'] . ', not found');
        }
      }
      else
      {
        $obj = new sgBaseController($matches);
        print $obj->$method();
      }
    }
  }
  
  public static function saveCachedRoutes()
  {
    $data = serialize(self::$cachedRoutes);
    file_put_contents(sgConfiguration::get('settings', 'cache_dir') . '/sgRouteCache.cache', $data);
  }
  
  public static function stick($routes)
  {
    $method = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) : strtoupper($_SERVER['REQUEST_METHOD']);
    if (!in_array($method, self::$allowedMethods))
    {
      throw new BadMethodCallException("Method, $method, not supported");
      exit();
    }
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
      if (sgConfiguration::get('settings', 'magic_routing'))
      {
        $matchedRoute = array(
          'path' => $path,
          'class' => 'sgMagicController',
          'method' => $method,
          'matches' => array(),
        );
        self::$cachedRoutes["$method $path"] = $matchedRoute;
      }
      else
      {
        $obj = new sgBaseController($matches);
        print $obj->throwErrorCode('404');
      }
    }
    
    if ($matchedRoute)
    {
      if (!sgConfiguration::get('settings', 'magic_routing') && $matchedRoute['class'] == 'sgMagicController')
      {
        $obj = new sgBaseController($matchedRoute['matches']);
        print $obj->throwErrorCode('404');
      }
      else
      {
        self::dispatch($matchedRoute, $method, $matchedRoute['matches']);
      }
    }

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