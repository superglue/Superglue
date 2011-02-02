<?php

/**
* Base Controller - all other controllers should extend this class
*/
class sgBaseController
{
  public $matchedRoute;
  public $matches;
  public $base;
  
  
  function __construct($matches = array())
  {
    $this->matches = $matches;
    $this->matchedRoute = sgContext::getCurrentRoute();
    $this->base = sgContext::getRelativeBaseUrl();
    $this->title = $this->guessTitle();
    $this->site_name = sgConfiguration::get('settings.site_name');
    $this->scripts = array();
    $this->styles = array();
    $this->js_settings = array('base' => $this->base);
  }
  
  public function GET()
  {
    return $this->render();
  }
  
  public function getTemplateVars()
  {
    $templateVars = get_object_vars($this);
    $templateVars['context'] = sgContext::getInstance();
    $templateVars['configuration'] = sgConfiguration::getInstance();
    $templateVars['request'] = array(
      'uri' => $_SERVER['REQUEST_URI'],
      'method' => $_SERVER['REQUEST_METHOD'],
      'ajax' => sgContext::isAjaxRequest(),
      'vars' => array('GET' => $_GET, 'POST' => $_POST),
    );
    $templateVars['js_settings'] = json_encode($this->js_settings);
    //str_replace() needed for incorrect slash escaping in php 5.2
    $js_settings = str_replace('\/', '/', json_encode($this->js_settings));
    $templateVars['js_settings'] =
<<<END
<script type="text/javascript" charset="utf-8">
  <!--//--><![CDATA[//><!--
    superglue = new Object();
    superglue.settings = $js_settings;
  //--><!]]>
  </script>
END;
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
    
    /*
      TODO figure out why I have this here
    */
    if (!empty($header))
    {
      header($header);
    }
    
    if (!isset($headers[(string)$httpErrorCode]))
    {
      header($error);
    }
    else
    {
      header($headers[(string)$httpErrorCode]);
    }
    
    if (sgConfiguration::get('settings.debug') && is_object($error))
    {
      exit('<pre>' . $error->getMessage() . "\n" . $error->getTraceAsString() . '</pre>');
    }
    
    try
    {
      $this->loadTemplate($httpErrorCode);
      return sgView::getInstance()->render($this->getTemplateVars());
    }
    catch (Exception $error)
    {
      // if the error is in the overridden 500 template, just display the core 500 template
      if ($httpErrorCode == 500)
      {
        sgView::getInstance()->getTwig()->getLoader()->setPaths(end(sgView::getInstance()->getTwig()->getLoader()->getPaths()));
      }
      $this->throwErrorCode(500);
    }
  }
  
  /*
    TODO add real file checks for templates, as oppossed to try catch block on loadTemplate()
  */
  public function render($template = NULL)
  {
    $plugins = sgConfiguration::getInstance()->getPlugins();
    foreach ($plugins as $plugin)
    {
      if (isset($plugin->configuration))
      {
        sgToolkit::executeMethod($plugin->configuration, 'preRender');
      }
    }
    sgToolkit::executeMethod(sgConfiguration::getInstance(), 'preRender');
    sgToolkit::executeMethod($this, 'preRender');
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
    if (sgConfiguration::get('settings.cache_routes'))
    {
      $method = strtoupper($_SERVER['REQUEST_METHOD']);
      $path = sgContext::getCurrentPath();
      sgGlue::$cachedRoutes["$method $path"]['template'] = str_replace('.html', '', $this->view->getView()->getName());
    }
    
    return sgView::getInstance()->render($this->getTemplateVars());
  }
  
  public function loadTemplate($name)
  {
    //set view so that exception can be thrown without setting template_name
    sgView::getInstance()->loadTemplate($name . '.html');
    $this->template_structure = explode('/', $name);
    $this->template_name = end($this->template_structure);
  }
} 