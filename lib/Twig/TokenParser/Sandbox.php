<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Marks a section of a template as untrusted code that must be evaluated in the sandbox mode.
 *
 *    {% sandbox %}
 *        {% include 'user.html' %}
 *    {% endsandbox %}
 *
 * @see https://twig.symfony.com/doc/api.html#sandbox-extension for details
 */
final class Twig_TokenParser_Sandbox extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $stream->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        // in a sandbox tag, only include tags are allowed
        if (!$body instanceof \Twig\Node\IncludeNode) {
            foreach ($body as $node) {
                if ($node instanceof \Twig\Node\TextNode && ctype_space($node->getAttribute('data'))) {
                    continue;
                }

                if (!$node instanceof \Twig\Node\IncludeNode) {
                    throw new \Twig\Error\SyntaxError('Only "include" tags are allowed within a "sandbox" section.', $node->getTemplateLine(), $stream->getSourceContext());
                }
            }
        }

        return new \Twig\Node\SandboxNode($body, $token->getLine(), $this->getTag());
    }

    public function decideBlockEnd(\Twig\Token $token)
    {
        return $token->test('endsandbox');
    }

    public function getTag()
    {
        return 'sandbox';
    }
}

class_alias('Twig_TokenParser_Sandbox', 'Twig\TokenParser\SandboxTokenParser', false);
