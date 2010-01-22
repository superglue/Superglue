<?php

class Twig_TokenParser_sgComponent extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    
    $expr = $this->parser->getExpressionParser()->parseExpression();

    $variables = null;
    if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'with'))
    {
      $this->parser->getStream()->next();
      $variables = $this->parser->getExpressionParser()->parseExpression();
    }

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
    
    return new Twig_Node_sgComponent($expr, $variables, $token->getLine(), $this->getTag());
  }

  public function getTag()
  {
    return 'component';
  }
}
