<?php

/**
* Base Controller - all other controllers should extend this class
*/
class sgBaseController
{
  protected $twig;
  public $matchedRoute;
  public $matches;
  public $base;
  
  function __construct($matches = array())
  {
    $loader = new Twig_Loader_Filesystem(sgConfiguration::getRootDir() . '/views', sgConfiguration::get('settings', 'cache_dir') . '/templates', sgConfiguration::get('settings', 'debug'));
    $this->twig = new Twig_Environment($loader);
    $this->matches = $matches;
    $this->matchedRoute = sgContext::getCurrentRoute();
    $this->base = sgContext::getRelativeBaseUrl();
    $this->title = $this->guessTitle();
  }
  
  public function GET()
  {
    return $this->render();
  }
  
  public function getTemplateVars()
  {
    /*
      TODO see if taking out the twig var significantly speeds rendering up (I'm sure it does)
    */
    $templateVars = get_object_vars($this);
    unset($templateVars['twig']);
    $templateVars['context'] = sgContext::getInstance();
    $templateVars['request'] = array(
      'uri' => $_SERVER['REQUEST_URI'],
      'method' => $_SERVER['REQUEST_METHOD'],
      'ajax' => sgContext::isAjaxRequest(),
    );
    
    return $templateVars;
  }
  
  public function guessTitle()
  {
    $title = '';
    if (isset($this->matchedRoute['title'])) {
      $title = $this->matchedRoute['title'];
    }
    else if (isset($this->matchedRoute['dynamic_title'])) {
      $titlevars = $this->matches;
      array_shift($titlevars);
      $title = ucwords(vsprintf($this->matchedRoute['dynamic_title'], $titlevars));
    }
    else if (isset($route['name'])) {
      $title = ucwords(str_replace('_', ' ', $this->matchedRoute['name']));
    }
    
    return $title;
  }
  
  /*
    TODO Clean up potential endless loops in error throwing and refactor for cleaner code
  */
  public function throw404Error()
  {
    header("HTTP/1.0 404 Not Found");
    try {
      $view = $this->twig->loadTemplate('404.html');
    }
    catch(Exception $e) {
      if (strpos($e->getMessage(), 'Unable to find template') === 0) {
        $loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/views', sgConfiguration::get('settings', 'cache_dir') . '/templates', sgConfiguration::get('settings', 'debug'));
        $this->twig = new Twig_Environment($loader);
        $view = $this->twig->loadTemplate('404.html');
      }
      else {
        $this->throwError($e);
      }
    }
    print $view->render($this->getTemplateVars());
    exit();
  }
  
  public function throwError($error)
  {
    if (strpos($error->getMessage(), 'Unable to find template') === 0) {
      $this->throw404Error();
    }
    header("HTTP/1.0 500 Internal Server Error");
    if (sgConfiguration::get('settings', 'debug')) {
      $loader = new Twig_Loader_String();
      $this->twig = new Twig_Environment($loader);
      $view = $this->twig->loadTemplate('<pre>' . $error->getMessage() . "\n" . $error->getTraceAsString() . '</pre>');
      print $view->render();
      exit();
    }
    try {
      $view = $this->twig->loadTemplate('500.html');
    }
    catch(Exception $thisError) {
      if (strpos($thisError->getMessage(), 'Unable to find template') === 0) {
        $loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/views', sgConfiguration::get('settings', 'cache_dir') . '/templates', sgConfiguration::get('settings', 'debug'));
        $this->twig = new Twig_Environment($loader);
        $view = $this->twig->loadTemplate('500.html');
      }
      else {
        sgConfiguration::set('settings', 'debug', true);
        $this->throwError($thisError);
      }
    }
    print $view->render($this->getTemplateVars());
    exit();
  }
  
  /*
    TODO add real file checks for templates, as oppossed to try catch block on loadTemplate()
  */
  public function render($template = NULL)
  {
    try {
      if ($template) {
        $view = $this->twig->loadTemplate($template . '.html');
      }
      else if (isset($route['template'])) {
        $view = $this->twig->loadTemplate($this->matchedRoute['template'] . '.html');
      }
      else if (sgConfiguration::getRootDir() . '/views/'){
        $view = $this->twig->loadTemplate($this->matchedRoute['name'] . '.html');
      }
    }
    catch(Exception $e) {
      $this->throwError($e);
    }
    
    print $view->render($this->getTemplateVars());
  }
}