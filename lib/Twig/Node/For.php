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

    if ($this->isMultitarget)
    {
      $loopVars = array($this->item[0]->getName(), $this->item[1]->getName());
    }
    else
    {
      $loopVars = array('_key', $this->item->getName());
    }

    $var = rand(1, 999999);
    $compiler
      ->write("\$seq$var = twig_iterator_to_array(")
      ->subcompile($this->seq)
      ->raw(");\n")
      ->write("\$context['loop']['length'] = count(\$seq$var);\n")

      ->write("\$context['loop'] = array(\n")
      ->write("  'parent'    => \$context['_parent'],\n")
      ->write("  'length'    => \$context['loop']['length'],\n")
      ->write("  'index0'    => 0,\n")
      ->write("  'index'     => 1,\n")
      ->write("  'revindex0' => \$context['loop']['length'] - 1,\n")
      ->write("  'revindex'  => \$context['loop']['length'],\n")
      ->write("  'first'     => true,\n")
      ->write("  'last'      => false,\n")
      ->write(");\n")

      ->write("foreach (\$seq$var as \$context[")
      ->repr($loopVars[0])
      ->raw("] => \$context[")
      ->repr($loopVars[1])
      ->raw("])\n")
      ->write("{\n")
      ->indent()
    ;

    if (!is_null($this->else))
    {
      $compiler->write("\$context['_iterated'] = true;\n");
    }

    $compiler
      ->subcompile($this->body)

      ->write("++\$context['loop']['index0'];\n")
      ->write("++\$context['loop']['index'];\n")
      ->write("--\$context['loop']['revindex0'];\n")
      ->write("--\$context['loop']['revindex'];\n")
      ->write("\$context['loop']['first'] = false;\n")
      ->write("\$context['loop']['last'] = 0 === \$context['loop']['revindex0'];\n")

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
