<?php

class sgUtilityTask extends sgTask
{
  public static function configure()
  {
    self::$tasks = array(
      'core' => array(
        'help' => array(
          'description' => 'Display all commands',
          'options' => array(
            'long' => array('namespace=='),
          ),
        ),
        'clear-cache' => array(
          'description' => 'Clears all cache files',
          'aliases' => array('cc'),
        ),
        'fix-perms' => array('description' => 'Fixes file permissions'),
        'init-project' => array('description' => 'Creates new superglue project in current directory'),
      ),
    );
  }
  
  protected function executeCoreHelp($arguments, $options)
  {
    if (isset($options['--namespace']))
    {
      $tasks = array($options['--namespace'] => sgCLI::getInstance()->getTasks($options['--namespace']));
      sgCLI::println(sprintf('Available commands in "%s" namespace:', $options['--namespace']), sgCLI::STYLE_HEADER);
    }
    else
    {
      $tasks = sgCLI::getInstance()->getTasks();
      sgCLI::println('Available commands:', sgCLI::STYLE_HEADER);
    }
    
    sgCLI::println();
    
    $max = 0;
    foreach ($tasks as $taskList)
    {
      $current = strlen(max(array_keys($taskList)));
      if ($current > $max)
      {
        $max = $current;
      }
    }
    
    foreach ($tasks as $namespace => $taskList)
    {
      sgCLI::println($namespace . ':', sgCLI::STYLE_INFO);
      foreach ($taskList as $command => $task)
      {
        $width = $max + strlen(sgCLI::formatText('', sgCLI::STYLE_INFO)) + 4;
        sgCLI::println(sprintf("  %-${width}s %s", sgCLI::formatText($command, sgCLI::STYLE_INFO), $task['description']));
      }
    }
  }
  
  public function executeCoreInitProject($arguments, $options)
  {
    $targetDir = realpath($_SERVER['PWD']);
    $scriptDir = realpath(dirname(__FILE__) . '/../../');
    if (file_exists($targetDir . '/superglue'))
    {
      sgCLI::error('A project already exists in this directory.');
      return false;
    }
    
    if (sgCLI::confirm("Are you sure you want to initialize a superglue project in\n$targetDir"))
    {
      sgCLI::println('Initializing project...', sgCLI::STYLE_HEADER);
      sgToolkit::mkdir($targetDir . '/config', 0755);
      sgToolkit::mkdir($targetDir . '/cache', 0777);
      sgToolkit::mkdir($targetDir . '/cache', 0777);
      sgToolkit::mkdir($targetDir . '/models', 0755);
      sgToolkit::mkdir($targetDir . '/controllers', 0755);
      sgToolkit::mkdir($targetDir . '/views', 0755);
      sgToolkit::mkdir($targetDir . '/web', 0755);
      sgToolkit::copy($scriptDir . '/skeleton/web/htaccess-dist', $targetDir . '/web/.htaccess');
      sgToolkit::copy($scriptDir . '/skeleton/web/index.php-dist', $targetDir . '/web/index.php');
      sgToolkit::copy($scriptDir . '/skeleton/config/ProjectConfiguration.class.php-dist', $targetDir . '/config/ProjectConfiguration.class.php');
      sgToolkit::copy($scriptDir . '/skeleton/config/config.php-dist', $targetDir . '/config/config.php');
      sgToolkit::copy($scriptDir . '/skeleton/config/routing.php-dist', $targetDir . '/config/routing.php');
      sgToolkit::copy($scriptDir . '/skeleton/views/index.html', $targetDir . '/views/index.html');
      if (sgToolkit::checkFileLocation($scriptDir, $targetDir))
      {
        sgToolkit::symlink($this->relPath($scriptDir, $targetDir) . '/superglue', $targetDir . '/superglue');
      }
      else
      {
        sgToolkit::mkdir($targetDir . '/lib', 0755);
        sgToolkit::symlink($scriptDir, $targetDir . '/lib/superglue');
        sgToolkit::symlink($scriptDir, $targetDir . '/superglue');
      }
      sgToolkit::chmod($targetDir . '/superglue', 0755);
      sgCLI::println('Done.', sgCLI::STYLE_INFO);
    }
  }
  
  public function executeCoreClearCache($arguments, $options)
  {
    $path = realpath(sgContext::getInstance()->getRootDir() . '/cache/');
    $files = sgToolkit::getFiles($path);
    sgToolkit::remove($files);
  }
  
  public function executeCoreFixPerms($arguments, $options)
  {
    $path = realpath(sgContext::getInstance()->getRootDir() . '/cache/');
    chmod($path, 0777);
    $files = sgToolkit::getFiles($path);
    sgToolkit::chmod($files, 0777);
  }
  
  // relative path function by Santosh Patnaik (http://www.php.net/manual/en/function.realpath.php#77203)
  private function relPath($dest, $root = '', $dir_sep = '/')
  {
   $root = explode($dir_sep, $root);
   $dest = explode($dir_sep, $dest);
   $path = '.';
   $fix = '';
   $diff = 0;
   for($i = -1; ++$i < max(($rC = count($root)), ($dC = count($dest)));)
   {
    if(isset($root[$i]) and isset($dest[$i]))
    {
     if($diff)
     {
      $path .= $dir_sep. '..';
      $fix .= $dir_sep. $dest[$i];
      continue;
     }
     if($root[$i] != $dest[$i])
     {
      $diff = 1;
      $path .= $dir_sep. '..';
      $fix .= $dir_sep. $dest[$i];
      continue;
     }
    }
    elseif(!isset($root[$i]) and isset($dest[$i]))
    {
     for($j = $i-1; ++$j < $dC;)
     {
      $fix .= $dir_sep. $dest[$j];
     }
     break;
    }
    elseif(isset($root[$i]) and !isset($dest[$i]))
    {
     for($j = $i-1; ++$j < $rC;)
     {
      $fix = $dir_sep. '..'. $fix;
     }
     break;
    }
   }
    return $path. $fix;
  }
}
