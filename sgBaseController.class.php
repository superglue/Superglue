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
    $this->initTwig();
    $this->matches = $matches;
    $this->matchedRoute = sgContext::getCurrentRoute();
    $this->base = sgContext::getRelativeBaseUrl();
    $this->title = $this->guessTitle();
  }

  public function initTwig()
  {
    $loader = new Twig_Loader_Filesystem(sgConfiguration::getRootDir() . '/views', sgConfiguration::get('settings', 'cache_dir') . '/templates', !sgConfiguration::get('settings', 'cache_templates'));
    $this->twig = new Twig_Environment($loader, array('debug' => sgConfiguration::get('settings', 'debug')));
    
    foreach (sgAutoloader::getPaths() as $class => $path)
    {
      if (strpos($class, 'Twig_Extension') === 0)
      {
        $this->twig->addExtension(new $class());
      }
    }
  }
  
  public function GET()
  {
    return $this->render(array());
  }
  
  public function getTemplateVars()
  {
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
  
  /*
    TODO figure out better way to handle titles
  */
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
      $title = ucwords(str_replace(array('_', '-'), ' ', $this->matchedRoute['name']));
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
      print '<pre>';
      print_r($view);
      print '</pre>';
      exit;
    }
    catch(Exception $e) {
      if (strpos($e->getMessage(), 'Unable to find template') === 0) {
        $loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/views', sgConfiguration::get('settings', 'cache_dir') . '/templates', sgConfiguration::get('settings', 'debug'));
        $this->twig = new Twig_Environment($loader, array('debug' => sgConfiguration::get('settings', 'debug')));
        $view = $this->loadTemplate('404');
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
      if (!sgConfiguration::get('settings', 'debug')) {
        $this->throw404Error();
      }
    }
    header("HTTP/1.0 500 Internal Server Error");
    if (sgConfiguration::get('settings', 'debug')) {
      $loader = new Twig_Loader_String();
      $this->twig = new Twig_Environment($loader, array('debug' => sgConfiguration::get('settings', 'debug')));
      $view = $this->twig->loadTemplate('<pre>' . $error->getMessage() . "\n" . $error->getTraceAsString() . '</pre>');
      print $view->render(array());
      exit();
    }
    try {
      $view = $this->twig->loadTemplate('500.html');
    }
    catch(Exception $thisError) {
      if (strpos($thisError->getMessage(), 'Unable to find template') === 0) {
        $loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/views', sgConfiguration::get('settings', 'cache_dir') . '/templates', sgConfiguration::get('settings', 'debug'));
        $this->twig = new Twig_Environment($loader, array('debug' => sgConfiguration::get('settings', 'debug')));
        $view = $this->loadTemplate('500');
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
        $view = $this->loadTemplate($template);
      }
      else if (isset($this->matchedRoute['template'])) {
        $view = $this->loadTemplate($this->matchedRoute['template']);
      }
      else {
        $view = $this->loadTemplate($this->matchedRoute['name']);
      }
    }
    /*
      FIXME recent changes in twig make it impossible to catch errors and throw a 500 error when debug is turned off
    */
    catch(Exception $e) {
      $this->throwError($e);
    }
    
    print $view->render($this->getTemplateVars());
    exit();
  }
  
  public function loadTemplate($name)
  {
    //set view so that exception can be thrown without setting template_name
    $view = $this->twig->loadTemplate($name . '.html');
    $this->template_structure = explode('/', $name);
    $this->template_name = end($this->template_structure);
    
    return $view;
  }
} 