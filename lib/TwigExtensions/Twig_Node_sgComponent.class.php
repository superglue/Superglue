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
    
    
    $compiler
      ->write('$component = new ' . $classMethod[0] . 'Component();')
      ->raw("\n")
      ->write('$output = $component->' . $classMethod[1] . '(')
      ->subcompile($this->variables)
      ->raw(");\n")
      ->write('echo $output;' . "\n")
    ;
    
    // $compiler
    //   ->write('$this->env->loadTemplate(')
    //   ->subcompile($this->expr)
    //   ->raw(')->display(')
    // ;
    // 
    // if (null === $this->variables)
    // {
    //   $compiler->raw('$context');
    // }
    // else
    // {
    //   $compiler->subcompile($this->variables);
    // }
    // 
    // $compiler->raw(");\n");
    // 
    // if ($this->sandboxed)
    // {
    //   $compiler
    //     ->write("if (!\$alreadySandboxed)\n", "{\n")
    //     ->indent()
    //     ->write("\$sandbox->disableSandbox();\n")
    //     ->outdent()
    //     ->write("}\n")
    //   ;
    // }
  }
}
