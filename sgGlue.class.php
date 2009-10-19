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
  static function stick ($routes) {
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    $path = sgContext::getCurrentPath();
    $found = false;

    foreach ($routes as $name => $route) {
      $regex = str_replace('/', '\/', $route['path']);
      $regex = '^' . $regex . '\/?$';
      if (preg_match("/$regex/i", $path, $matches)) {
        $found = true;
        $route['name'] = $name;
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
        break;
      }
    }
    if (!$found) {
      sgContext::setCurrentRoute(false);
      $obj = new sgStaticController();
      $obj->$method();
      //throw new Exception("URL, $path, not found.");
    }
  }
}