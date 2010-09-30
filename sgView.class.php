<?php

/**
* View - provides a wrapper for Twig
*/
class sgView
{
  private static $instance;
  
  protected
    $twig,
    $loader,
    $view,
    $templatePaths;
  
  private function __construct()
  {
    $this->setPaths();
    $this->loader = new Twig_Loader_Filesystem($this->templatePaths);
    $this->twig = new Twig_Environment($this->loader, array(
      'debug' => sgConfiguration::get('settings.debug'),
      'cache' => sgConfiguration::get('settings.cache_dir') . '/templates',
      'auto_reload' => !sgConfiguration::get('settings.cache_templates'),  // TODO maybe this should instead dictate the cache setting
    ));
    
    foreach (sgConfiguration::get('settings.enabled_twig_extensions') as $extension)
    {
      $this->twig->addExtension(new $extension());
    }
    
    // foreach (sgAutoloader::getPaths() as $class => $path)
    // {
    //   if (strpos($class, 'Twig_Extension') === 0)
    //   {
    //     $this->twig->addExtension(new $class());
    //   }
    // }
  }
  
  public static function getInstance()
  {
    if (!isset(self::$instance)) {
        $c = __CLASS__;
        self::$instance = new $c;
    }
    return self::$instance;
  }
  
  public function __clone()
  {
    trigger_error('Only one instance of a singleton is allowed.', E_USER_ERROR);
  }
  
  public function setPaths(array $templatePaths = array(), $overridePaths = FALSE)
  {
    if (!$overridePaths)
    {
      $this->templatePaths[] = sgConfiguration::getRootDir() . '/views';
      $enabledPlugins = sgConfiguration::get('settings.enabled_plugins', array());
      foreach ($enabledPlugins as $plugin)
      {
        if (is_dir(sgConfiguration::getRootDir() . "/plugins/$plugin/views"))
        {
          $this->templatePaths[] = sgConfiguration::getRootDir() . "/plugins/$plugin/views";
        }
      }
      $this->templatePaths[] = dirname(__FILE__) . '/views';
    }
    if (isset($this->twig))
    {
      $this->twig->getLoader()->setPaths($this->templatePaths);
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