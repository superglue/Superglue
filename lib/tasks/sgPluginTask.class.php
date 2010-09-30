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
      sgCLI::println($plugin->name, sgCLI::STYLE_INFO);
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
  
  private function pluginOp($pluginName, $op, $actionString)
  {
    $plugins = sgConfiguration::getPlugins();
    if ($plugin = $plugins[$pluginName])
    {
      if (isset($plugin->configuration) && is_object($plugin->configuration) && method_exists($plugin->configuration, $op))
      {
        $opString = sgCLI::formatText($op, array('options' => array('bright', 'underscore')), sgCLI::STYLE_CONFIRM, false);
        if (sgCLI::confirm("Are you sure you want to $opString the plugin \"{$plugin->name}\"?"))
        {
          sgCLI::println(ucwords($actionString) . " Plugin \"{$plugin->name}\":", sgCLI::STYLE_HEADER);
          sgConfiguration::executePluginHook($plugin, $op);
        }
      }
      else
      {
        sgClI::println("Nothing to $op.", sgCLI::STYLE_INFO);
      }
    }
    else
    {
      sgClI::println("$pluginName is not an enabled plugin.", sgCLI::STYLE_ERROR);
    }
  }
}