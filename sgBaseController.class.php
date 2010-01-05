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
    $templateLocations = array(
      sgConfiguration::getRootDir() . '/views',
      dirname(__FILE__) . '/views',
    );
    
    $loader = new Twig_Loader_Filesystem($templateLocations, sgConfiguration::get('settings', 'cache_dir') . '/templates', !sgConfiguration::get('settings', 'cache_templates'));
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
      'vars' => array('GET' => $_GET, 'POST' => $_POST),
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
    TODO Still need to work out workflow for error debugging
  */
  public function throwError($error)
  {
    if (strpos($error->getMessage(), 'Unable to find template') === 0)
    {
      $this->throwErrorCode(404);
    }
    else
    {
      $this->throwErrorCode(500);
    }
    exit();
  }
  
  public function throwErrorCode($httpErrorCode, $header = '')
  {
    $headers = array(
      '404' => 'HTTP/1.0 404 Not Found',
      '500' => 'HTTP/1.0 500 Internal Server Error',
    );
    if (!empty($header))
    {
      header($header);
    }
    else
    {
      header($headers[(string)$httpErrorCode]);
    }
    try
    {
      $view = $this->loadTemplate($httpErrorCode);
      print $view->render($this->getTemplateVars());
    }
    catch (Exception $error)
    {
      // if the error template can't load, display the error or throw a 500 error if debugging is disabled
      if (sgConfiguration::get('settings', 'debug'))
      {
        print '<pre>' . $error->getMessage() . "\n" . $error->getTraceAsString() . '</pre>';
      }
      else
      {
        // if the error is in the overridden 500 template, just display the core 500 template
        if ($httpErrorCode == 500)
        {
          $this->twig->getLoader()->setPaths(end($this->twig->getLoader()->getPaths()));
        }
        $this->throwErrorCode(500);
      }
    }
  }
  
  /*
    TODO add real file checks for templates, as oppossed to try catch block on loadTemplate()
  */
  public function render($template = NULL)
  {
    try
    {
      if ($template)
      {
        $view = $this->loadTemplate($template);
      }
      else if (isset($this->matchedRoute['template']))
      {
        $view = $this->loadTemplate($this->matchedRoute['template']);
      }
      else
      {
        $view = $this->loadTemplate($this->matchedRoute['name']);
      }
    }
    catch(Exception $e)
    {
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