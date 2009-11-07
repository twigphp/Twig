<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a for node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_For extends Twig_Node implements Twig_NodeListInterface
{
  protected $isMultitarget;
  protected $item;
  protected $seq;
  protected $body;
  protected $else;

  public function __construct($isMultitarget, $item, $seq, Twig_NodeList $body, Twig_Node $else = null, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->isMultitarget = $isMultitarget;
    $this->item = $item;
    $this->seq = $seq;
    $this->body = $body;
    $this->else = $else;
    $this->lineno = $lineno;
  }

  public function getNodes()
  {
    return $this->body->getNodes();
  }

  public function setNodes(array $nodes)
  {
    $this->body = new Twig_NodeList($nodes, $this->lineno);
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->pushContext()
    ;

    if (!is_null($this->else))
    {
      $compiler->write("\$context['_iterated'] = false;\n");
    }

    $var = rand(1, 999999);
    $compiler
      ->write("\$seq$var = twig_iterator_to_array(")
      ->subcompile($this->seq)
      ->raw(");\n")
      ->write("\$context['loop']['length'] = count(\$seq$var);\n")
      ->write("for (\$i$var = 0; \$i$var < \$context['loop']['length']; \$i$var++)\n")
      ->write("{\n")
      ->indent()
    ;

    if (!is_null($this->else))
    {
      $compiler->write("\$context['_iterated'] = true;\n");
    }

    $compiler->write("twig_set_loop_context(\$context, \$seq$var, \$i$var, ");

    if ($this->isMultitarget)
    {
      $compiler->raw('array(');
      foreach ($this->item as $idx => $node)
      {
        if ($idx)
        {
          $compiler->raw(', ');
        }
        $compiler->repr($node->getName());
      }
      $compiler->raw(')');
    }
    else
    {
      $compiler->repr($this->item->getName());
    }

    $compiler
      ->raw(");\n")
      ->subcompile($this->body)
      ->outdent()
      ->write("}\n")
    ;

    if (!is_null($this->else))
    {
      $compiler
        ->write("if (!\$context['_iterated'])\n")
        ->write("{\n")
        ->indent()
        ->subcompile($this->else)
        ->outdent()
        ->write("}\n")
      ;
    }
    $compiler->popContext();
  }
}
