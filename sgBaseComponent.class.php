<?php

/**
* Base Component - all other components should extend this class
* format: {% component 'class|method' with ['array', 'of', 'args'] %}
*/
class sgBaseComponent
{
  protected $twig;
  public $args;
  public $base;
  
  
  function __construct($args = array())
  {
    $this->initTwig();
    $this->args = $args;
    $this->base = sgContext::getRelativeBaseUrl();
  }
  
  public function initTwig()
  {
    $loader = new Twig_Loader_Filesystem(sgConfiguration::getRootDir() . '/views');
    $this->twig = new Twig_Environment($loader, array(
      'debug' => sgConfiguration::get('settings', 'debug'),
      'cache' => sgConfiguration::get('settings', 'cache_dir') . '/templates',
      'auto_reload' => !sgConfiguration::get('settings', 'cache_templates'),  // TODO maybe this should instead dictate the cache setting
    ));
    
    foreach (sgAutoloader::getPaths() as $class => $path)
    {
      if (strpos($class, 'Twig_Extension') === 0)
      {
        $this->twig->addExtension(new $class());
      }
    }
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
    array_merge($templateVars, $this->args);
    
    return $templateVars;
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
    catch(Exception $error) {
      if (sgConfiguration::get('settings', 'debug'))
      {
        return('<pre>' . $error->getMessage() . "\n" . $error->getTraceAsString() . '</pre>');
      }
    }
    
    return $view->render($this->getTemplateVars());
  }
  
  public function loadTemplate($name)
  {
    //set view so that exception can be thrown without setting template_name
    $view = $this->twig->loadTemplate("_$name.html");
    $this->template_structure = explode('/', $name);
    $this->template_name = end($this->template_structure);
    
    return $view;
  }
}