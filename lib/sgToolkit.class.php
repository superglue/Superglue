<?php

class sgToolkit
{
  public static function camelCase($string)
  {
    $string = str_replace(' ', '', ucwords(preg_replace('/[^A-Za-z0-9]/', ' ', $string)));
    $string{0} = strtolower($string{0});

    return $string;
  }
  
  public static function chmod($files = array(), $mode)
  {
    if (is_string($files))
    {
      $files = array($files);
    }
    foreach ($files as $file)
    {
      chmod($file, $mode);
      sgCLI::printAction(sprintf('chmod %o', $mode), $file);
    }
  }

  public static function remove($files = array())
  {
    if (is_string($files))
    {
      $files = array($files);
    }
    foreach ($files as $file)
    {
      if (is_dir($file) && !is_link($file))
      {
        if(@!rmdir($file))
        {
          sgCLI::printAction('remove dir', sgCLI::formatText($file, sgCLI::STYLE_ERROR));
        }
        else
        {
          sgCLI::printAction('remove dir', $file);
        }
      }
      else
      {
        if (@!unlink($file))
        {
          sgCLI::printAction('remove file', sgCLI::formatText($file, sgCLI::STYLE_ERROR));
        }
        else
        {
          sgCLI::printAction('remove file', $file);
        }
      }
    }
  }
  
  public static function mkdir($files = array(), $mode = 0755)
  {
    if (is_string($files))
    {
      $files = array($files);
    }
    foreach ($files as $file)
    {
      //mkdir($file, $mode);
      sgCLI::printAction('mkdir', $file);
    }
  }
  
  public static function copy($source, $target)
  {
    //copy($source, $destination);
    sgCLI::printAction('create', $target);
  }
  
  public static function symlink($source, $target)
  {
    //symlink($source, $destination);
    sgCLI::printAction('link', $target);
  }
  
  public function getFiles($path, &$data = array())
  {
    $files = new RecursiveDirectoryIterator($path);
    foreach ($files as $file)
    {
      array_unshift($data, $file->getPathname());
      if ($file->isDir() && !$file->isLink())
      {
        $this->getFiles($file->getPathname(), $data);
      }
    }
    
    return $data;
  }
  
  // based on file_check_location from drupal: http://api.drupal.org/api/function/file_check_location/6
  public function checkFileLocation($source, $directory = '') {
    $check = realpath($source);
    if ($check && file_exists($check)) {
      $source = $check;
    }
    else {
      // This file does not yet exist
      $source = realpath(dirname($source)) .'/'. basename($source);
    }
    $directory = realpath($directory);
    if ($directory && file_exists($directory) && strpos($source, $directory) !== 0) {
      return 0;
    }
    return $source;
  }
}
