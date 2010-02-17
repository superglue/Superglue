<?php

class sgToolkit
{
  public static function url($path, $absolute = false)
  {
    $env = sgContext::getEnvironment();
    if (strpos($path, '/') === 0)
    {
      $path = substr($path, 1);
    }
    
    if ($absolute)
    {
      $protocol = 'http';
      if (isset($env['HTTPS']))
      {
        $protocol .= 's://';
      }
      else
      {
        $protocol .= '://';
      }
      return $protocol . $env['HTTP_HOST'] . sgContext::getRelativeBaseUrl() . '/' . $path;
    }
    
    return sgContext::getRelativeBaseUrl() . '/' . $path;
  }
  
  //if object is an actual object, then the method will be called on that object
  //if it is a string, then method will be called statically
  public static function executeMethod($object, $method, $arguments = array())
  {
    if (method_exists($object, $method))
    {
      return call_user_func_array(array($object, $method), $arguments);
    }
    
    return false;
  }
  
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
      $oldUmask = umask(0000);
      chmod($file, $mode);
      umask($oldUmask);
      sgCLI::printAction(sprintf('chmod %o', $mode), $file);
    }
  }
  
  public static function rmdir($path)
  {
    $path = realpath($path);
    if (!is_dir($path))
    {
      return false;
    }
    $files = sgToolkit::getFiles($path);
    $files[] = $path;
    self::remove($files);
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
          sgCLI::printAction('-dir', sgCLI::formatText($file, sgCLI::STYLE_ERROR));
        }
        else
        {
          sgCLI::printAction('-dir', $file);
        }
      }
      else
      {
        if (@!unlink($file))
        {
          sgCLI::printAction('-file', sgCLI::formatText($file, sgCLI::STYLE_ERROR));
        }
        else
        {
          sgCLI::printAction('-file', $file);
        }
      }
    }
  }
  
  public static function mkdir($files = array(), $mode = 0755, $recursive = true)
  {
    if (is_string($files))
    {
      $files = array($files);
    }
    foreach ($files as $file)
    {
      if (is_dir($file))
      {
        continue;
      }
      $oldUmask = umask(0000);
      mkdir($file, $mode, $recursive);
      umask($oldUmask);
      sgCLI::printAction('+dir', $file);
    }
  }
  
  public static function copy($source, $destination)
  {
    copy($source, $destination);
    sgCLI::printAction('+file', $destination);
  }
  
  public static function symlink($source, $destination)
  {
    symlink($source, $destination);
    sgCLI::printAction('+link', $destination);
  }
  
  public static function touch($path, $time = null, $atime = null)
  {
    touch($path, $time, $atime);
    sgCLI::printAction('+file', $path);
  }
  
  public static function getFiles($path, &$data = array())
  {
    $files = new RecursiveDirectoryIterator($path);
    foreach ($files as $file)
    {
      array_unshift($data, $file->getPathname());
      if ($file->isDir() && !$file->isLink())
      {
        self::getFiles($file->getPathname(), $data);
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
  
  // modified from http://us.php.net/manual/en/function.array-merge-recursive.php#92195
  public static function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
  {
    $merged = $array1;
    
    foreach ($array2 as $key => &$value)
    {
      if (is_int($key))
      {
        $merged[] = $value;
      }
      else if (is_array($value) && !empty($value) && isset($merged[$key]) && is_array($merged[$key]))
      {
        $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
      }
      else
      {
        $merged[$key] = $value;
      }
    }
    
    return $merged;
  }
}
