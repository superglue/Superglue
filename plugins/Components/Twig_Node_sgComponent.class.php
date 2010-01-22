<?php
class Twig_Node_sgComponent extends Twig_Node
{
  protected $expr;
  protected $variables;

  public function __construct(Twig_Node_Expression $expr, $variables, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->expr = $expr;
    $this->variables = $variables;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->expr.')';
  }

  public function compile($compiler)
  {
    $compiler->addDebugInfo($this);
    $classMethod = explode('|', $this->expr->getValue());
    
    $compiler->write('$component = new ' . $classMethod[0] . "Component('" . $classMethod[0] . "', '" . $classMethod[1] . "'");
    if ($this->variables !== null)
    {
      $compiler
        ->raw(', ')
        ->subcompile($this->variables)
      ;
    }
    $compiler
      ->raw(');' . "\n")
      ->write('$output = $component->' . $classMethod[1] . '();' . "\n")
      ->write('echo $output;' . "\n")
    ;
  }
}
