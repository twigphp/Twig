<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

use Twig\Error\SyntaxError;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ArrowFunctionExpression;
use Twig\Node\Expression\Binary\AbstractBinary;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Expression\Unary\AbstractUnary;
use Twig\Node\Expression\Unary\NegUnary;
use Twig\Node\Expression\Unary\NotUnary;
use Twig\Node\Expression\Unary\PosUnary;
use Twig\Node\Expression\Unary\SpreadUnary;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\LocalVariable;
use Twig\Node\Expression\Variable\TemplateVariable;
use Twig\Node\Node;
use Twig\Node\Nodes;

/**
 * Parses expressions.
 *
 * This parser implements a "Precedence climbing" algorithm.
 *
 * @see https://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 * @see https://en.wikipedia.org/wiki/Operator-precedence_parser
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExpressionParser
{
    public const OPERATOR_LEFT = 1;
    public const OPERATOR_RIGHT = 2;

    /** @var array<string, array{precedence: int, class: class-string<AbstractUnary>}> */
    private array $unaryOperators;
    /** @var array<string, array{precedence: int, class: class-string<AbstractBinary>, associativity: self::OPERATOR_*}> */
    private array $binaryOperators;
    private array $precedenceChanges = [];
    private bool $deprecationCheck = true;

    public function __construct(
        private Parser $parser,
        private Environment $env,
    ) {
        $this->unaryOperators = $env->getUnaryOperators();
        $this->binaryOperators = $env->getBinaryOperators();

        $ops = [];
        foreach ($this->unaryOperators as $n => $c) {
            $ops[] = $c + ['name' => $n, 'type' => 'unary'];
        }
        foreach ($this->binaryOperators as $n => $c) {
            $ops[] = $c + ['name' => $n, 'type' => 'binary'];
        }
        foreach ($ops as $config) {
            if (!isset($config['precedence_change'])) {
                continue;
            }
            $name = $config['type'].'_'.$config['name'];
            $min = min($config['precedence_change']->getNewPrecedence(), $config['precedence']);
            $max = max($config['precedence_change']->getNewPrecedence(), $config['precedence']);
            foreach ($ops as $c) {
                if ($c['precedence'] > $min && $c['precedence'] < $max) {
                    $this->precedenceChanges[$c['type'].'_'.$c['name']][] = $name;
                }
            }
        }
    }

    public function parseExpression($precedence = 0)
    {
        if ($arrow = $this->parseArrow()) {
            return $arrow;
        }

        $expr = $this->getPrimary();
        $token = $this->parser->getCurrentToken();
        while ($this->isBinary($token) && $this->binaryOperators[$token->getValue()]['precedence'] >= $precedence) {
            $op = $this->binaryOperators[$token->getValue()];
            $this->parser->getStream()->next();

            if ('is not' === $token->getValue()) {
                $expr = $this->parseNotTestExpression($expr);
            } elseif ('is' === $token->getValue()) {
                $expr = $this->parseTestExpression($expr);
            } elseif (isset($op['callable'])) {
                $expr = $op['callable']($this->parser, $expr);
            } else {
                $previous = $this->setDeprecationCheck(true);
                try {
                    $expr1 = $this->parseExpression(self::OPERATOR_LEFT === $op['associativity'] ? $op['precedence'] + 1 : $op['precedence']);
                } finally {
                    $this->setDeprecationCheck($previous);
                }
                $class = $op['class'];
                $expr = new $class($expr, $expr1, $token->getLine());
            }

            $expr->setAttribute('operator', 'binary_'.$token->getValue());

            $this->triggerPrecedenceDeprecations($expr, $token);

            $token = $this->parser->getCurrentToken();
        }

        if (0 === $precedence) {
            return $this->parseConditionalExpression($expr);
        }

        return $expr;
    }

    private function triggerPrecedenceDeprecations(AbstractExpression $expr): void
    {
        // Check that the all nodes that are between the 2 precedences have explicit parentheses
        if (!$expr->hasAttribute('operator') || !isset($this->precedenceChanges[$expr->getAttribute('operator')])) {
            return;
        }

        if (str_starts_with($unaryOp = $expr->getAttribute('operator'), 'unary')) {
            if ($expr->hasExplicitParentheses()) {
                return;
            }
            $target = explode('_', $unaryOp)[1];
            $change = $this->unaryOperators[$target]['precedence_change'];
            /** @var AbstractExpression $node */
            $node = $expr->getNode('node');
            foreach ($this->precedenceChanges as $operatorName => $changes) {
                if (!in_array($unaryOp, $changes)) {
                    continue;
                }
                if ($node->hasAttribute('operator') && $operatorName === $node->getAttribute('operator')) {
                    trigger_deprecation($change->getPackage(), $change->getVersion(), \sprintf('Add explicit parentheses around the "%s" unary operator to avoid behavior change in the next major version as its precedence will change in "%s" at line %d.', $target, $this->parser->getStream()->getSourceContext()->getName(), $node->getTemplateLine()));
                }
            }
        } else {
            foreach ($this->precedenceChanges[$expr->getAttribute('operator')] as $operatorName) {
                foreach ($expr as $node) {
                    /** @var AbstractExpression $node */
                    if ($node->hasAttribute('operator') && $operatorName === $node->getAttribute('operator') && !$node->hasExplicitParentheses()) {
                        $op = explode('_', $operatorName)[1];
                        $change = $this->binaryOperators[$op]['precedence_change'];
                        trigger_deprecation($change->getPackage(), $change->getVersion(), \sprintf('Add explicit parentheses around the "%s" binary operator to avoid behavior change in the next major version as its precedence will change in "%s" at line %d.', $op, $this->parser->getStream()->getSourceContext()->getName(), $node->getTemplateLine()));
                    }
                }
            }
        }
    }

    private function parseArrow(): ?ArrowFunctionExpression
    {
        $stream = $this->parser->getStream();

        // short array syntax (one argument, no parentheses)?
        if ($stream->look(1)->test(Token::ARROW_TYPE)) {
            $line = $stream->getCurrent()->getLine();
            $token = $stream->expect(Token::NAME_TYPE);
            $names = [new AssignContextVariable($token->getValue(), $token->getLine())];
            $stream->expect(Token::ARROW_TYPE);

            return new ArrowFunctionExpression($this->parseExpression(), new Nodes($names), $line);
        }

        // first, determine if we are parsing an arrow function by finding => (long form)
        $i = 0;
        if (!$stream->look($i)->test(Token::PUNCTUATION_TYPE, '(')) {
            return null;
        }
        ++$i;
        while (true) {
            // variable name
            ++$i;
            if (!$stream->look($i)->test(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
            ++$i;
        }
        if (!$stream->look($i)->test(Token::PUNCTUATION_TYPE, ')')) {
            return null;
        }
        ++$i;
        if (!$stream->look($i)->test(Token::ARROW_TYPE)) {
            return null;
        }

        // yes, let's parse it properly
        $token = $stream->expect(Token::PUNCTUATION_TYPE, '(');
        $line = $token->getLine();

        $names = [];
        while (true) {
            $token = $stream->expect(Token::NAME_TYPE);
            $names[] = new AssignContextVariable($token->getValue(), $token->getLine());

            if (!$stream->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')');
        $stream->expect(Token::ARROW_TYPE);

        return new ArrowFunctionExpression($this->parseExpression(), new Nodes($names), $line);
    }

    private function getPrimary(): AbstractExpression
    {
        $token = $this->parser->getCurrentToken();

        if ($this->isUnary($token)) {
            $operator = $this->unaryOperators[$token->getValue()];
            $this->parser->getStream()->next();
            $expr = $this->parseExpression($operator['precedence']);
            $class = $operator['class'];

            $expr = new $class($expr, $token->getLine());
            $expr->setAttribute('operator', 'unary_'.$token->getValue());

            if ($this->deprecationCheck) {
                $this->triggerPrecedenceDeprecations($expr, $token);
            }

            return $this->parsePostfixExpression($expr);
        } elseif ($token->test(Token::PUNCTUATION_TYPE, '(')) {
            $this->parser->getStream()->next();
            $previous = $this->setDeprecationCheck(false);
            try {
                $expr = $this->parseExpression()->setExplicitParentheses();
            } finally {
                $this->setDeprecationCheck($previous);
            }
            $this->parser->getStream()->expect(Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');

            return $this->parsePostfixExpression($expr);
        }

        return $this->parsePrimaryExpression();
    }

    private function parseConditionalExpression($expr): AbstractExpression
    {
        while ($this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, '?')) {
            if (!$this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ':')) {
                $expr2 = $this->parseExpression();
                if ($this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ':')) {
                    // Ternary operator (expr ? expr2 : expr3)
                    $expr3 = $this->parseExpression();
                } else {
                    // Ternary without else (expr ? expr2)
                    $expr3 = new ConstantExpression('', $this->parser->getCurrentToken()->getLine());
                }
            } else {
                // Ternary without then (expr ?: expr3)
                $expr2 = $expr;
                $expr3 = $this->parseExpression();
            }

            $expr = new ConditionalExpression($expr, $expr2, $expr3, $this->parser->getCurrentToken()->getLine());
        }

        return $expr;
    }

    private function isUnary(Token $token): bool
    {
        return $token->test(Token::OPERATOR_TYPE) && isset($this->unaryOperators[$token->getValue()]);
    }

    private function isBinary(Token $token): bool
    {
        return $token->test(Token::OPERATOR_TYPE) && isset($this->binaryOperators[$token->getValue()]);
    }

    public function parsePrimaryExpression()
    {
        $token = $this->parser->getCurrentToken();
        switch ($token->getType()) {
            case Token::NAME_TYPE:
                $this->parser->getStream()->next();
                switch ($token->getValue()) {
                    case 'true':
                    case 'TRUE':
                        $node = new ConstantExpression(true, $token->getLine());
                        break;

                    case 'false':
                    case 'FALSE':
                        $node = new ConstantExpression(false, $token->getLine());
                        break;

                    case 'none':
                    case 'NONE':
                    case 'null':
                    case 'NULL':
                        $node = new ConstantExpression(null, $token->getLine());
                        break;

                    default:
                        if ('(' === $this->parser->getCurrentToken()->getValue()) {
                            $node = $this->getFunctionNode($token->getValue(), $token->getLine());
                        } else {
                            $node = new ContextVariable($token->getValue(), $token->getLine());
                        }
                }
                break;

            case Token::NUMBER_TYPE:
                $this->parser->getStream()->next();
                $node = new ConstantExpression($token->getValue(), $token->getLine());
                break;

            case Token::STRING_TYPE:
            case Token::INTERPOLATION_START_TYPE:
                $node = $this->parseStringExpression();
                break;

            case Token::PUNCTUATION_TYPE:
                $node = match ($token->getValue()) {
                    '[' => $this->parseSequenceExpression(),
                    '{' => $this->parseMappingExpression(),
                    default => throw new SyntaxError(\sprintf('Unexpected token "%s" of value "%s".', Token::typeToEnglish($token->getType()), $token->getValue()), $token->getLine(), $this->parser->getStream()->getSourceContext()),
                };
                break;

            case Token::OPERATOR_TYPE:
                if (preg_match(Lexer::REGEX_NAME, $token->getValue(), $matches) && $matches[0] == $token->getValue()) {
                    // in this context, string operators are variable names
                    $this->parser->getStream()->next();
                    $node = new ContextVariable($token->getValue(), $token->getLine());
                    break;
                }

                if ('=' === $token->getValue() && ('==' === $this->parser->getStream()->look(-1)->getValue() || '!=' === $this->parser->getStream()->look(-1)->getValue())) {
                    throw new SyntaxError(\sprintf('Unexpected operator of value "%s". Did you try to use "===" or "!==" for strict comparison? Use "is same as(value)" instead.', $token->getValue()), $token->getLine(), $this->parser->getStream()->getSourceContext());
                }

                // no break
            default:
                throw new SyntaxError(\sprintf('Unexpected token "%s" of value "%s".', Token::typeToEnglish($token->getType()), $token->getValue()), $token->getLine(), $this->parser->getStream()->getSourceContext());
        }

        return $this->parsePostfixExpression($node);
    }

    public function parseStringExpression()
    {
        $stream = $this->parser->getStream();

        $nodes = [];
        // a string cannot be followed by another string in a single expression
        $nextCanBeString = true;
        while (true) {
            if ($nextCanBeString && $token = $stream->nextIf(Token::STRING_TYPE)) {
                $nodes[] = new ConstantExpression($token->getValue(), $token->getLine());
                $nextCanBeString = false;
            } elseif ($stream->nextIf(Token::INTERPOLATION_START_TYPE)) {
                $nodes[] = $this->parseExpression();
                $stream->expect(Token::INTERPOLATION_END_TYPE);
                $nextCanBeString = true;
            } else {
                break;
            }
        }

        $expr = array_shift($nodes);
        foreach ($nodes as $node) {
            $expr = new ConcatBinary($expr, $node, $node->getTemplateLine());
        }

        return $expr;
    }

    public function parseSequenceExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Token::PUNCTUATION_TYPE, '[', 'A sequence element was expected');

        $node = new ArrayExpression([], $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ']')) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A sequence element must be followed by a comma');

                // trailing ,?
                if ($stream->test(Token::PUNCTUATION_TYPE, ']')) {
                    break;
                }
            }
            $first = false;

            if ($stream->nextIf(Token::SPREAD_TYPE)) {
                $node->addElement(new SpreadUnary($this->parseExpression(), $stream->getCurrent()->getLine()));
            } else {
                $node->addElement($this->parseExpression());
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ']', 'An opened sequence is not properly closed');

        return $node;
    }

    public function parseMappingExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Token::PUNCTUATION_TYPE, '{', 'A mapping element was expected');

        $node = new ArrayExpression([], $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Token::PUNCTUATION_TYPE, '}')) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A mapping value must be followed by a comma');

                // trailing ,?
                if ($stream->test(Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;

            if ($stream->nextIf(Token::SPREAD_TYPE)) {
                $node->addElement(new SpreadUnary($this->parseExpression(), $stream->getCurrent()->getLine()));
                continue;
            }

            // a mapping key can be:
            //
            //  * a number -- 12
            //  * a string -- 'a'
            //  * a name, which is equivalent to a string -- a
            //  * an expression, which must be enclosed in parentheses -- (1 + 2)
            if ($token = $stream->nextIf(Token::NAME_TYPE)) {
                $key = new ConstantExpression($token->getValue(), $token->getLine());

                // {a} is a shortcut for {a:a}
                if ($stream->test(Token::PUNCTUATION_TYPE, [',', '}'])) {
                    $value = new ContextVariable($key->getAttribute('value'), $key->getTemplateLine());
                    $node->addElement($value, $key);
                    continue;
                }
            } elseif (($token = $stream->nextIf(Token::STRING_TYPE)) || $token = $stream->nextIf(Token::NUMBER_TYPE)) {
                $key = new ConstantExpression($token->getValue(), $token->getLine());
            } elseif ($stream->test(Token::PUNCTUATION_TYPE, '(')) {
                $key = $this->parseExpression();
            } else {
                $current = $stream->getCurrent();

                throw new SyntaxError(\sprintf('A mapping key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "%s" of value "%s".', Token::typeToEnglish($current->getType()), $current->getValue()), $current->getLine(), $stream->getSourceContext());
            }

            $stream->expect(Token::PUNCTUATION_TYPE, ':', 'A mapping key must be followed by a colon (:)');
            $value = $this->parseExpression();

            $node->addElement($value, $key);
        }
        $stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened mapping is not properly closed');

        return $node;
    }

    public function parsePostfixExpression($node)
    {
        while (true) {
            $token = $this->parser->getCurrentToken();
            if (Token::PUNCTUATION_TYPE == $token->getType()) {
                if ('.' == $token->getValue() || '[' == $token->getValue()) {
                    $node = $this->parseSubscriptExpression($node);
                } elseif ('|' == $token->getValue()) {
                    $node = $this->parseFilterExpression($node);
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $node;
    }

    public function getFunctionNode($name, $line)
    {
        if (null !== $alias = $this->parser->getImportedSymbol('function', $name)) {
            return new MacroReferenceExpression(new TemplateVariable($alias['node']->getAttribute('name'), $line), $alias['name'], $this->createArguments($line), $line);
        }

        $args = $this->parseOnlyArguments();
        $function = $this->getFunction($name, $line);

        if ($function->getParserCallable()) {
            $fakeNode = new EmptyNode($line);
            $fakeNode->setSourceContext($this->parser->getStream()->getSourceContext());

            return ($function->getParserCallable())($this->parser, $fakeNode, $args, $line);
        }

        return new ($function->getNodeClass())($function, $args, $line);
    }

    public function parseSubscriptExpression($node)
    {
        if ('.' === $this->parser->getStream()->next()->getValue()) {
            return $this->parseSubscriptExpressionDot($node);
        }

        return $this->parseSubscriptExpressionArray($node);
    }

    public function parseFilterExpression($node)
    {
        $this->parser->getStream()->next();

        return $this->parseFilterExpressionRaw($node);
    }

    public function parseFilterExpressionRaw($node)
    {
        while (true) {
            $token = $this->parser->getStream()->expect(Token::NAME_TYPE);

            if (!$this->parser->getStream()->test(Token::PUNCTUATION_TYPE, '(')) {
                $arguments = new EmptyNode();
            } else {
                $arguments = $this->parseOnlyArguments();
            }

            $filter = $this->getFilter($token->getValue(), $token->getLine());

            $node = new ($filter->getNodeClass())($node, $filter, $arguments, $token->getLine());

            if (!$this->parser->getStream()->test(Token::PUNCTUATION_TYPE, '|')) {
                break;
            }

            $this->parser->getStream()->next();
        }

        return $node;
    }

    /**
     * Parses arguments.
     *
     * @return Node
     *
     * @throws SyntaxError
     */
    public function parseArguments()
    {
        $args = [];
        $stream = $this->parser->getStream();

        $stream->expect(Token::PUNCTUATION_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        $hasSpread = false;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if ($args) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');

                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }

            if ($stream->nextIf(Token::SPREAD_TYPE)) {
                $hasSpread = true;
                $value = new SpreadUnary($this->parseExpression(), $stream->getCurrent()->getLine());
            } elseif ($hasSpread) {
                throw new SyntaxError('Normal arguments must be placed before argument unpacking.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            } else {
                $value = $this->parseExpression();
            }

            $name = null;
            if (($token = $stream->nextIf(Token::OPERATOR_TYPE, '=')) || ($token = $stream->nextIf(Token::PUNCTUATION_TYPE, ':'))) {
                if (!$value instanceof ContextVariable) {
                    throw new SyntaxError(\sprintf('A parameter name must be a string, "%s" given.', $value::class), $token->getLine(), $stream->getSourceContext());
                }
                $name = $value->getAttribute('name');
                $value = $this->parseExpression();
            }

            if (null === $name) {
                $args[] = $value;
            } else {
                $args[$name] = $value;
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return new Nodes($args);
    }

    public function parseAssignmentExpression()
    {
        $stream = $this->parser->getStream();
        $targets = [];
        while (true) {
            $token = $this->parser->getCurrentToken();
            if ($stream->test(Token::OPERATOR_TYPE) && preg_match(Lexer::REGEX_NAME, $token->getValue())) {
                // in this context, string operators are variable names
                $this->parser->getStream()->next();
            } else {
                $stream->expect(Token::NAME_TYPE, null, 'Only variables can be assigned to');
            }
            $targets[] = new AssignContextVariable($token->getValue(), $token->getLine());

            if (!$stream->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }

        return new Nodes($targets);
    }

    public function parseMultitargetExpression()
    {
        $targets = [];
        while (true) {
            $targets[] = $this->parseExpression();
            if (!$this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }

        return new Nodes($targets);
    }

    private function parseNotTestExpression(Node $node): NotUnary
    {
        return new NotUnary($this->parseTestExpression($node), $this->parser->getCurrentToken()->getLine());
    }

    private function parseTestExpression(Node $node): TestExpression
    {
        $stream = $this->parser->getStream();
        $test = $this->getTest($node->getTemplateLine());

        $arguments = null;
        if ($stream->test(Token::PUNCTUATION_TYPE, '(')) {
            $arguments = $this->parseOnlyArguments();
        } elseif ($test->hasOneMandatoryArgument()) {
            $arguments = new Nodes([0 => $this->getPrimary()]);
        }

        if ('defined' === $test->getName() && $node instanceof ContextVariable && null !== $alias = $this->parser->getImportedSymbol('function', $node->getAttribute('name'))) {
            $node = new MacroReferenceExpression(new TemplateVariable($alias['node']->getAttribute('name'), $node->getTemplateLine()), $alias['name'], new ArrayExpression([], $node->getTemplateLine()), $node->getTemplateLine());
        }

        return new ($test->getNodeClass())($node, $test, $arguments, $this->parser->getCurrentToken()->getLine());
    }

    private function getTest(int $line): TwigTest
    {
        $stream = $this->parser->getStream();
        $name = $stream->expect(Token::NAME_TYPE)->getValue();

        if ($stream->test(Token::NAME_TYPE)) {
            // try 2-words tests
            $name = $name.' '.$this->parser->getCurrentToken()->getValue();

            if ($test = $this->env->getTest($name)) {
                $stream->next();
            }
        } else {
            $test = $this->env->getTest($name);
        }

        if (!$test) {
            if ($this->parser->shouldIgnoreUnknownTwigCallables()) {
                return new TwigTest($name, fn () => '');
            }
            $e = new SyntaxError(\sprintf('Unknown "%s" test.', $name), $line, $stream->getSourceContext());
            $e->addSuggestions($name, array_keys($this->env->getTests()));

            throw $e;
        }

        if ($test->isDeprecated()) {
            $stream = $this->parser->getStream();
            $src = $stream->getSourceContext();
            $test->triggerDeprecation($src->getPath() ?: $src->getName(), $stream->getCurrent()->getLine());
        }

        return $test;
    }

    private function getFunction(string $name, int $line): TwigFunction
    {
        if (!$function = $this->env->getFunction($name)) {
            if ($this->parser->shouldIgnoreUnknownTwigCallables()) {
                return new TwigFunction($name, fn () => '');
            }
            $e = new SyntaxError(\sprintf('Unknown "%s" function.', $name), $line, $this->parser->getStream()->getSourceContext());
            $e->addSuggestions($name, array_keys($this->env->getFunctions()));

            throw $e;
        }

        if ($function->isDeprecated()) {
            $src = $this->parser->getStream()->getSourceContext();
            $function->triggerDeprecation($src->getPath() ?: $src->getName(), $line);
        }

        return $function;
    }

    private function getFilter(string $name, int $line): TwigFilter
    {
        if (!$filter = $this->env->getFilter($name)) {
            if ($this->parser->shouldIgnoreUnknownTwigCallables()) {
                return new TwigFilter($name, fn () => '');
            }
            $e = new SyntaxError(\sprintf('Unknown "%s" filter.', $name), $line, $this->parser->getStream()->getSourceContext());
            $e->addSuggestions($name, array_keys($this->env->getFilters()));

            throw $e;
        }

        if ($filter->isDeprecated()) {
            $src = $this->parser->getStream()->getSourceContext();
            $filter->triggerDeprecation($src->getPath() ?: $src->getName(), $line);
        }

        return $filter;
    }

    private function setDeprecationCheck(bool $deprecationCheck): bool
    {
        $current = $this->deprecationCheck;
        $this->deprecationCheck = $deprecationCheck;

        return $current;
    }

    private function createArguments(int $line): ArrayExpression
    {
        $arguments = new ArrayExpression([], $line);
        foreach ($this->parseOnlyArguments() as $k => $n) {
            $arguments->addElement($n, new LocalVariable($k, $line));
        }

        return $arguments;
    }

    public function parseOnlyArguments()
    {
        $args = [];
        $stream = $this->parser->getStream();
        $stream->expect(Token::PUNCTUATION_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        $hasSpread = false;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if ($args) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');

                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }

            if ($stream->nextIf(Token::SPREAD_TYPE)) {
                $hasSpread = true;
                $value = new SpreadUnary($this->parseExpression(), $stream->getCurrent()->getLine());
            } elseif ($hasSpread) {
                throw new SyntaxError('Normal arguments must be placed before argument unpacking.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            } else {
                $value = $this->parseExpression();
            }

            $name = null;
            if (($token = $stream->nextIf(Token::OPERATOR_TYPE, '=')) || ($token = $stream->nextIf(Token::PUNCTUATION_TYPE, ':'))) {
                if (!$value instanceof ContextVariable) {
                    throw new SyntaxError(\sprintf('A parameter name must be a string, "%s" given.', \get_class($value)), $token->getLine(), $stream->getSourceContext());
                }
                $name = $value->getAttribute('name');
                $value = $this->parseExpression();
            }

            if (null === $name) {
                $args[] = $value;
            } else {
                $args[$name] = $value;
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return new Nodes($args);
    }

    private function parseSubscriptExpressionDot(Node $node): AbstractExpression
    {
        $stream = $this->parser->getStream();
        $token = $stream->getCurrent();
        $lineno = $token->getLine();
        $arguments = new ArrayExpression([], $lineno);
        $type = Template::ANY_CALL;

        if ($stream->nextIf(Token::PUNCTUATION_TYPE, '(')) {
            $attribute = $this->parseExpression();
            $stream->expect(Token::PUNCTUATION_TYPE, ')');
        } else {
            $token = $stream->next();
            if (
                Token::NAME_TYPE == $token->getType()
                ||
                Token::NUMBER_TYPE == $token->getType()
                ||
                (Token::OPERATOR_TYPE == $token->getType() && preg_match(Lexer::REGEX_NAME, $token->getValue()))
            ) {
                $attribute = new ConstantExpression($token->getValue(), $token->getLine());
            } else {
                throw new SyntaxError(\sprintf('Expected name or number, got value "%s" of type %s.', $token->getValue(), Token::typeToEnglish($token->getType())), $token->getLine(), $stream->getSourceContext());
            }
        }

        if ($stream->test(Token::PUNCTUATION_TYPE, '(')) {
            $type = Template::METHOD_CALL;
            $arguments = $this->createArguments($token->getLine());
        }

        if (
            $node instanceof ContextVariable
            &&
            (
                null !== $this->parser->getImportedSymbol('template', (new TemplateVariable($node->getAttribute('name'), $node->getTemplateLine()))->getAttribute('name'))
                ||
                '_self' === $node->getAttribute('name') && $attribute instanceof ConstantExpression
            )
        ) {
            return new MacroReferenceExpression(new TemplateVariable($node->getAttribute('name'), $node->getTemplateLine()), 'macro_'.$attribute->getAttribute('value'), $arguments, $node->getTemplateLine());
        }

        return new GetAttrExpression($node, $attribute, $arguments, $type, $lineno);
    }

    private function parseSubscriptExpressionArray(Node $node): AbstractExpression
    {
        $stream = $this->parser->getStream();
        $token = $stream->getCurrent();
        $lineno = $token->getLine();
        $arguments = new ArrayExpression([], $lineno);

        // slice?
        $slice = false;
        if ($stream->test(Token::PUNCTUATION_TYPE, ':')) {
            $slice = true;
            $attribute = new ConstantExpression(0, $token->getLine());
        } else {
            $attribute = $this->parseExpression();
        }

        if ($stream->nextIf(Token::PUNCTUATION_TYPE, ':')) {
            $slice = true;
        }

        if ($slice) {
            if ($stream->test(Token::PUNCTUATION_TYPE, ']')) {
                $length = new ConstantExpression(null, $token->getLine());
            } else {
                $length = $this->parseExpression();
            }

            $filter = $this->getFilter('slice', $token->getLine());
            $arguments = new Nodes([$attribute, $length]);
            $filter = new ($filter->getNodeClass())($node, $filter, $arguments, $token->getLine());

            $stream->expect(Token::PUNCTUATION_TYPE, ']');

            return $filter;
        }

        $stream->expect(Token::PUNCTUATION_TYPE, ']');

        return new GetAttrExpression($node, $attribute, $arguments, Template::ARRAY_CALL, $lineno);
    }
}
