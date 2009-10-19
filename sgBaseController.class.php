<?php

/**
* Base Controller - all other controllers should extend this class
*/
class sgBaseController
{
  protected $twig;
  
  function __construct($matches = array())
  {
    $loader = new Twig_Loader_Filesystem(sgConfiguration::getRootDir() . '/views', sgConfiguration::get('settings', 'cache_dir'));
    $this->twig = new Twig_Environment($loader);
    $this->matches = $matches;
  }
  
  public function GET()
  {
    return $this->render();
  }
  
  // this needs to be split up
  public function render($template = NULL)
  {
    $route = sgContext::getCurrentRoute();
    $this->base = sgContext::getRelativeBaseUrl();
    
    $templateVars = get_object_vars($this);
    $templateVars['context'] = sgContext::getInstance();
    $templateVars['request'] = array(
      'uri' => $_SERVER['REQUEST_URI'],
      'method' => $_SERVER['REQUEST_METHOD'],
      'ajax' => sgContext::isAjaxRequest(),
    );
    
    // should probably move this out of here at some point
    if (isset($route['title'])) {
      $this->title = $route['title'];
    }
    else if (isset($route['dynamic_title'])) {
      $titlevars = $this->matches;
      array_shift($titlevars);
      $this->title = ucwords(vsprintf($route['dynamic_title'], $titlevars));
    }
    else if (isset($route['name'])) {
      $this->title = ucwords(str_replace('_', ' ', $route['name']));
    }
    
    try {
      if ($template) {
        $view = $this->twig->loadTemplate($template . '.html');
      }
      else if (isset($route['template'])) {
        $view = $this->twig->loadTemplate($route['template'] . '.html');
      }
      else {
        $view = $this->twig->loadTemplate($route['name'] . '.html');
      }
    }
    catch(Exception $e) {
      header("HTTP/1.0 404 Not Found");
      try {
        $view = $this->twig->loadTemplate('404.html');
      }
      catch(Exception $e) {
        $loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/views', sgConfiguration::get('settings', 'cache_dir'));
        $this->twig = new Twig_Environment($loader);
        $view = $this->twig->loadTemplate('404.html');
      }
      print $view->render($templateVars);
      exit();
    }
    
    print $view->render($templateVars);
  }
}