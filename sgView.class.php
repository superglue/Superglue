<?php

/**
* View - provides a wrapper for Twig
*/
class sgView
{
  private $twig;
  private $loader;
  private $view;
  
  public function __construct($templatePaths = array(), $overridePaths = FALSE)
  {
    if (!$overridePaths)
    {
      $templatePaths[] = sgConfiguration::getRootDir() . '/views';
      $templatePaths[] = dirname(__FILE__) . '/views';
    }
    
    $this->loader = new Twig_Loader_Filesystem($templatePaths);
    $this->twig = new Twig_Environment($this->loader, array(
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
  
  public function getTwig()
  {
    return $this->twig;
  }
  
  //wonder if removing some of the dependency injection from twig is really a good idea
  public function loadTemplate($template)
  {
    $this->view = $this->twig->loadTemplate($template);
    
    return $this->view;
  }
  
  public function getView()
  {
    return $this->view;
  }
  
  public function render($variables = array())
  {
    if (!$this->view)
    {
      throw new Exception('No view is defined. You must call loadTemplate() before you can render a view.');
    }
    
    return $this->view->render($variables);
  }
}