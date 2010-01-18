<?php

/**
* Base Component - all other components should extend this class
* format: {% component 'class|method' with ['array', 'of', 'args'] %}
*/
class sgBaseComponent
{
  protected $view;
  public $args;
  public $base;
  public $class;
  public $method;
  
  function __construct($class, $method, $args = array())
  {
    $this->view = new sgView();
    $this->class = $class;
    $this->method = $method;
    $this->args = $args;
    $this->base = sgContext::getRelativeBaseUrl();
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
    if (is_null($template))
    {
      $template = '_' . $this->class . '_' . $this->method;
    }
    
    try
    {
      $this->loadTemplate($template);
    }
    /*
      FIXME recent changes in twig make it impossible to catch errors and throw a 500 error when debug is turned off
    */
    catch(Exception $error)
    {
      if (sgConfiguration::get('settings', 'debug'))
      {
        return('<pre>' . $error->getMessage() . "\n" . $error->getTraceAsString() . '</pre>');
      }
    }
    
    return $this->view->render($this->getTemplateVars());
  }
  
  public function loadTemplate($name)
  {
    //set view so that exception can be thrown without setting template_name
    $this->view->loadTemplate("$name.html");
    $this->template_structure = explode('/', $name);
    $this->template_name = end($this->template_structure);
  }
}