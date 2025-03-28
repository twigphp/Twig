<?php

namespace Twig\ExpressionParser\Infix;

use Twig\Error\SyntaxError;
use Twig\ExpressionParser\AbstractExpressionParser;
use Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Twig\ExpressionParser\InfixAssociativity;
use Twig\ExpressionParser\InfixExpressionParserInterface;
use Twig\Lexer;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Expression\Variable\TemplateVariable;
use Twig\Parser;
use Twig\Template;
use Twig\Token;

/**
 * @internal
 */
final class OptionalChainExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    use ArgumentsTrait;


    public function parse(Parser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $stream = $parser->getStream();
        $token = $stream->getCurrent();
        $lineno = $token->getLine();
        $arguments = new ArrayExpression([], $lineno);
        $type = Template::ANY_CALL;
        $isOptionalChain = true;

        // Проверка, является ли левое выражение переменной
        $isVariable = $expr instanceof NameExpression;

        // Для обработки квадратных скобок
        if ($stream->test(Token::OPERATOR_TYPE, '[')) {
            $token = $stream->next();
            $attribute = $parser->parseExpression();
            $stream->expect(Token::PUNCTUATION_TYPE, ']');
            $type = Template::ARRAY_CALL;
        } elseif ($stream->nextIf(Token::OPERATOR_TYPE, '(')) {
            $attribute = $parser->parseExpression();
            $stream->expect(Token::PUNCTUATION_TYPE, ')');
        } else {
            $token = $stream->next();
            if (
                $token->test(Token::NAME_TYPE)
                || $token->test(Token::NUMBER_TYPE)
                || ($token->test(Token::OPERATOR_TYPE) && preg_match(Lexer::REGEX_NAME, $token->getValue()))
            ) {
                $attribute = new ConstantExpression($token->getValue(), $token->getLine());
            } else {
                throw new SyntaxError(\sprintf('Expected name or number, got value "%s" of type %s.', $token->getValue(), $token->toEnglish()), $token->getLine(), $stream->getSourceContext());
            }
        }

        if ($stream->test(Token::OPERATOR_TYPE, '(')) {
            $type = Template::METHOD_CALL;
            $arguments = $this->parseCallableArguments($parser, $token->getLine());
        }

        if (
            $expr instanceof NameExpression
            && (
                null !== $parser->getImportedSymbol('template', $expr->getAttribute('name'))
                || '_self' === $expr->getAttribute('name') && $attribute instanceof ConstantExpression
            )
        ) {
            // Для макросов не используем optional chaining
            return new MacroReferenceExpression(new TemplateVariable($expr->getAttribute('name'), $expr->getTemplateLine()), 'macro_'.$attribute->getAttribute('value'), $arguments, $expr->getTemplateLine());
        }

        // Создаем специальный флаг для проверки существования переменной
        if ($isVariable && $expr instanceof NameExpression) {
            $expr->setAttribute('optional_chain', true);
        }

        return new GetAttrExpression($expr, $attribute, $arguments, $type, $lineno, $isOptionalChain);
    }

    public function getName(): string
    {
        return '?.';
    }

    public function getDescription(): string
    {
        return 'Optional chaining to safely access an attribute on a potentially null variable';
    }

    public function getPrecedence(): int
    {
        return 512; // Тот же приоритет, что и у оператора "."
    }

    public function getAssociativity(): InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}