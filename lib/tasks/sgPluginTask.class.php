<?php

class sgPluginTask extends sgTask
{
  public static function configure()
  {
    self::$tasks = array(
      'plugin' => array(
        'list-enabled' => array('description' => 'Lists enabled plugins'),
        'install' => array(
          'description' => 'Installs plugin',
          'usage' => './superglue plugin:install pluginName',
          'arguments' => array('pluginName'),
        ),
        'uninstall' => array(
          'description' => 'Uninstalls plugin',
          'usage' => './superglue plugin:uninstall pluginName',
          'arguments' => array('pluginName'),
        ),
      ),
    );
  }
  
  public function executePluginListEnabled($arguments, $options)
  {
    $plugins = sgConfiguration::getInstance()->getPlugins();
    foreach ($plugins as $plugin)
    {
      sgCLI::println($plugin, sgCLI::STYLE_INFO);
    }
  }
  
  public function executePluginInstall($arguments, $options)
  {
    $this->pluginOp($arguments['pluginName'], 'install', 'installing');
  }
  
  public function executePluginUninstall($arguments, $options)
  {
    $this->pluginOp($arguments['pluginName'], 'uninstall', 'uninstalling');
  }
  
  private function pluginOp($plugin, $op, $actionString)
  {
    $pluginConfigClass = $plugin . 'Configuration';
    if (class_exists($pluginConfigClass))
    {
      if (method_exists($pluginConfigClass, $op))
      {
        if (sgCLI::confirm("Are you sure you want to $op the plugin \"$plugin\"?"))
        {
          sgCLI::println(ucwords($actionString) . " Plugin \"$plugin\":", sgCLI::STYLE_HEADER);
          call_user_func(array($pluginConfigClass, 'init'));
          call_user_func(array($pluginConfigClass, $op));
          sgClI::println('Done.', sgCLI::STYLE_INFO);
        }
      }
      else
      {
        sgClI::println("Nothing to $op.", sgCLI::STYLE_INFO);
      }
    }
    else
    {
      sgCLI::error('Plugin $plugin does not exist.');
    }
  }
}