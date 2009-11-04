<?php
class sgFilteredDirectoryIterator extends RecursiveFilterIterator
{
  private $_exclusions;
  /*
    TODO this should be passed in via the constructor
  */
  private $_extension = 'php.ssalc.'; //reversed
  function __construct($path, array $_exclusions = array())
  {
    $this->_exclusions = $_exclusions;
    parent::__construct(new RecursiveDirectoryIterator($path));
  }
  
  function accept()
  {
    $filename = $this->getInnerIterator()->getFilename();
    if (($this->getInnerIterator()->isDir() && !in_array($filename, $this->_exclusions)) || ($this->getInnerIterator()->isFile() && stripos(strrev($filename), 'php.ssalc.') === 0 && !in_array($filename, $this->_exclusions))) {
      return true;
    }
    
    return false;
  }
  
  function getChildren()
  {
    $class = __CLASS__;
    return new $class($this->key(), $this->_exclusions);
  }
}
