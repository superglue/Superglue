<?php
class Twig_Extension_sgComponent extends Twig_Extension
{
  public function getTokenParsers()
  {
    return array(
      new Twig_TokenParser_sgComponent(),
    );
  }
  
  public function getName()
  {
    return 'component';
  }
}