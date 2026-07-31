<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\ExpressionParser\Infix;

use Twig\Error\SyntaxError;
use Twig\ExpressionParser\AbstractExpressionParser;
use Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Twig\ExpressionParser\InfixAssociativity;
use Twig\ExpressionParser\InfixExpressionParserInterface;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\MacroVariable;
use Twig\Parser;
use Twig\Token;

/**
 * @internal
 */
final class FunctionExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    use ArgumentsTrait;

    public function parse(Parser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $line = $token->getLine();
        if (!$expr instanceof ContextVariable) {
            throw new SyntaxError('Function name must be an identifier.', $line, $parser->getStream()->getSourceContext());
        }

        $name = $expr->getAttribute('name');

        // A bare call to a macro imported via "from" is syntactically a function call;
        // it is resolved through the "function" imported symbol registered by
        // FromTokenParser, which maps the local alias to the macro name and the
        // template it comes from.
        if (null !== $alias = $parser->getImportedSymbol('function', $name)) {
            /** @var MacroVariable $var */
            $var = $alias['node']->getNode('var');
            $arguments = $this->parseCallableArguments($parser, $line, false, true);
            $node = new MacroReferenceExpression($var, $alias['name'], $arguments, $line);
            $node->setHasCallParentheses(true);

            return $node;
        }

        $args = $this->parseNamedArguments($parser, false);

        $function = $parser->getFunction($name, $line);

        if ($function->getParserCallable()) {
            $fakeNode = new EmptyNode($line);
            $fakeNode->setSourceContext($parser->getStream()->getSourceContext());

            $node = ($function->getParserCallable())($parser, $fakeNode, $args, $line);
            // remember the original function name so the sandbox can enforce
            // the `allowedFunctions` allow-list even though the parser callable
            // returned a specialized node (e.g. `parent`, `block`, `attribute`).
            $node->setAttribute('sandboxed_function_name', $name);
            $node->setAttribute('sandboxed_function', $function);

            return $node;
        }

        return new ($function->getNodeClass())($function, $args, $line);
    }

    public function getName(): string
    {
        return '(';
    }

    public function getDescription(): string
    {
        return 'Twig function call';
    }

    public function getPrecedence(): int
    {
        return 512;
    }

    public function getAssociativity(): InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
