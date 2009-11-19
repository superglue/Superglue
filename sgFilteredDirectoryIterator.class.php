<?php
class sgFilteredDirectoryIterator extends RecursiveFilterIterator
{
  private $_exclusions;
  /*
    TODO make array of extensions
  */
  private $_extension;
  
  function __construct($path, $extension, array $_exclusions = array())
  {
    $this->_extension = strrev($extension);
    $this->_exclusions = $_exclusions;
    parent::__construct(new RecursiveDirectoryIterator($path));
  }
  
  function accept()
  {
    $filename = $this->getInnerIterator()->getFilename();
    if (($this->getInnerIterator()->isDir() && !in_array($filename, $this->_exclusions)) || ($this->getInnerIterator()->isFile() && stripos(strrev($filename), $this->_extension) === 0 && !in_array($filename, $this->_exclusions))) {
      return true;
    }
    
    return false;
  }
  
  function getChildren()
  {
    $class = __CLASS__;
    return new $class($this->key(), strrev($this->_extension), $this->_exclusions);
  }
}
