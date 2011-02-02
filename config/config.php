<?php

return array(
  'magic_routing' => true,
  'debug' => false,
  'cache_templates' => false,
  'cache_routes' => false,
  'cache_autoload' => false,
  'cache_dir' => sgConfiguration::getRootDir() . '/cache',
  'autoload_exclusions' => array(
    'Twig',
    '.svn',
    'CVS',
    '.git',
    dirname(__FILE__) . '/../skeleton', 
    dirname(__FILE__) . '/../config'
  ),
  'enabled_plugins' => array('Components'),
  'js_settings' => array(
    'base' => sgContext::getRelativeBaseUrl(),
  ),
);