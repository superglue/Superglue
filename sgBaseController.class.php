<?php

/**
* Base Controller - all other controllers should extend this class
*/
class sgBaseController
{
  protected $view;
  public $matchedRoute;
  public $matches;
  public $base;
  
  
  function __construct($matches = array())
  {
    $this->view = new sgView();
    $this->matches = $matches;
    $this->matchedRoute = sgContext::getCurrentRoute();
    $this->base = sgContext::getRelativeBaseUrl();
    $this->title = $this->guessTitle();
    $this->site_name = sgConfiguration::get('settings', 'site_name');
  }
  
  public function GET()
  {
    return $this->render(array());
  }
  
  public function getTemplateVars()
  {
    $templateVars = get_object_vars($this);
    unset($templateVars['view']);
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
  
  public function throwError($error)
  {
    if (strpos($error->getMessage(), 'Unable to find template') === 0)
    {
      return $this->throwErrorCode(404, $error);
    }
    return $this->throwErrorCode(500, $error);
  }
  
  public function throwErrorCode($httpErrorCode, $error = NULL, $header = '')
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
    
    if (sgConfiguration::get('settings', 'debug'))
    {
      exit('<pre>' . $error->getMessage() . "\n" . $error->getTraceAsString() . '</pre>');
    }
    
    try
    {
      $this->loadTemplate($httpErrorCode);
      return $this->view->render($this->getTemplateVars());
    }
    catch (Exception $error)
    {
      // if the error is in the overridden 500 template, just display the core 500 template
      if ($httpErrorCode == 500)
      {
        $this->view->getTwig()->getLoader()->setPaths(end($this->view->getTwig()->getLoader()->getPaths()));
      }
      $this->throwErrorCode(500);
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
        $this->loadTemplate($template);
      }
      else if (isset($this->matchedRoute['template']))
      {
        $this->loadTemplate($this->matchedRoute['template']);
      }
      else
      {
        $this->loadTemplate($this->matchedRoute['name']);
      }
    }
    catch(Exception $e)
    {
      return $this->throwError($e);
    }

    // update route cache with appropriate template
    if (sgConfiguration::get('settings', 'cache_routes'))
    {
      $method = strtoupper($_SERVER['REQUEST_METHOD']);
      $path = sgContext::getCurrentPath();
      sgGlue::$cachedRoutes["$method $path"]['template'] = str_replace('.html', '', $this->view->getView()->getName());
    }
    
    return $this->view->render($this->getTemplateVars());
  }
  
  public function loadTemplate($name)
  {
    //set view so that exception can be thrown without setting template_name
    $this->view->loadTemplate($name . '.html');
    $this->template_structure = explode('/', $name);
    $this->template_name = end($this->template_structure);
  }
} 