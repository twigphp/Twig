<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Twig\ExpressionParser\ExpressionParserType;
use Twig\ExpressionParser\InfixAssociativity;
use Twig\ExpressionParser\InfixExpressionParserInterface;
use Twig\Loader\ArrayLoader;

require_once \dirname(__DIR__).'/vendor/autoload.php';

$output = fopen(\dirname(__DIR__).'/doc/operators_precedence.rst', 'w');

$twig = new Environment(new ArrayLoader([]));
$expressionParsers = [];
foreach ($twig->getExpressionParsers() as $expressionParser) {
    $expressionParsers[] = $expressionParser;
}

fwrite($output, "\n=========== ================ ======= ============= ===========\n");
fwrite($output, "Precedence  Operator         Type    Associativity Description\n");
fwrite($output, '=========== ================ ======= ============= ===========');

usort($expressionParsers, fn ($a, $b) => $b->getPrecedence() <=> $a->getPrecedence());

$previous = null;
foreach ($expressionParsers as $expressionParser) {
    $precedence = $expressionParser->getPrecedence();
    $previousPrecedence = $previous ? $previous->getPrecedence() : \PHP_INT_MAX;
    $associativity = $expressionParser instanceof InfixExpressionParserInterface ? (InfixAssociativity::Left === $expressionParser->getAssociativity() ? 'Left' : 'Right') : 'n/a';
    $previousAssociativity = $previous ? ($previous instanceof InfixExpressionParserInterface ? (InfixAssociativity::Left === $previous->getAssociativity() ? 'Left' : 'Right') : 'n/a') : 'n/a';
    if ($previousPrecedence !== $precedence) {
        $previous = null;
    }
    fwrite($output, rtrim(\sprintf("\n%-11s %-16s %-7s %-13s %s\n",
        (!$previous || $previousPrecedence !== $precedence ? $precedence : '').($expressionParser->getPrecedenceChange() ? ' => '.$expressionParser->getPrecedenceChange()->getNewPrecedence() : ''),
        '``'.$expressionParser->getName().'``',
        !$previous || ExpressionParserType::getType($previous) !== ExpressionParserType::getType($expressionParser) ? ExpressionParserType::getType($expressionParser)->value : '',
        !$previous || $previousAssociativity !== $associativity ? $associativity : '',
        $expressionParser instanceof ExpressionParserDescriptionInterface ? $expressionParser->getDescription() : '',
    )));
    $previous = $expressionParser;
}
fwrite($output, "\n=========== ================ ======= ============= ===========\n");
fwrite($output, "\nWhen a precedence will change in 4.0, the new precedence is indicated by the arrow ``=>``.\n");

fwrite($output, "\nHere is the same table for Twig 4.0 with adjusted precedences:\n");

fwrite($output, "\n=========== ================ ======= ============= ===========\n");
fwrite($output, "Precedence  Operator         Type    Associativity Description\n");
fwrite($output, '=========== ================ ======= ============= ===========');

usort($expressionParsers, function ($a, $b) {
    $aPrecedence = $a->getPrecedenceChange() ? $a->getPrecedenceChange()->getNewPrecedence() : $a->getPrecedence();
    $bPrecedence = $b->getPrecedenceChange() ? $b->getPrecedenceChange()->getNewPrecedence() : $b->getPrecedence();

    return $bPrecedence - $aPrecedence;
});

$previous = null;
foreach ($expressionParsers as $expressionParser) {
    $precedence = $expressionParser->getPrecedenceChange() ? $expressionParser->getPrecedenceChange()->getNewPrecedence() : $expressionParser->getPrecedence();
    $previousPrecedence = $previous ? ($previous->getPrecedenceChange() ? $previous->getPrecedenceChange()->getNewPrecedence() : $previous->getPrecedence()) : \PHP_INT_MAX;
    $associativity = $expressionParser instanceof InfixExpressionParserInterface ? (InfixAssociativity::Left === $expressionParser->getAssociativity() ? 'Left' : 'Right') : 'n/a';
    $previousAssociativity = $previous ? ($previous instanceof InfixExpressionParserInterface ? (InfixAssociativity::Left === $previous->getAssociativity() ? 'Left' : 'Right') : 'n/a') : 'n/a';
    if ($previousPrecedence !== $precedence) {
        $previous = null;
    }
    fwrite($output, rtrim(\sprintf("\n%-11s %-16s %-7s %-13s %s\n",
        !$previous || $previousPrecedence !== $precedence ? $precedence : '',
        '``'.$expressionParser->getName().'``',
        !$previous || ExpressionParserType::getType($previous) !== ExpressionParserType::getType($expressionParser) ? ExpressionParserType::getType($expressionParser)->value : '',
        !$previous || $previousAssociativity !== $associativity ? $associativity : '',
        $expressionParser instanceof ExpressionParserDescriptionInterface ? $expressionParser->getDescription() : '',
    )));
    $previous = $expressionParser;
}
fwrite($output, "\n=========== ================ ======= ============= ===========\n");

fclose($output);
