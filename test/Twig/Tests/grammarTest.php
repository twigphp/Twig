<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/SimpleTokenParser.php';

class grammarTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTests
     */
    public function testGrammar($tag, $grammar, $template, $output, $exception)
    {
        $twig = new Twig_Environment(new Twig_Loader_String(), array('cache' => false));
        $twig->addTokenParser(new SimpleTokenParser($tag, $grammar));

        $ok = true;
        try {
            $template = $twig->loadTemplate($template);
        } catch (Exception $e) {
            $ok = false;

            if (false === $exception) {
                $this->fail('Exception not expected');
            } else {
                $this->assertEquals($exception, get_class($e));
            }
        }

        if ($ok) {
            if (false !== $exception) {
                $this->fail(sprintf('Exception "%s" expected', $exception));
            }

            $actual = $template->render(array());
            $this->assertEquals($output, $actual);
        }
    }

    public function getTests()
    {
        return array(
            array('foo1', '', '{% foo1 %}', '|', false),
            array('foo2', '', '{% foo2 "bar" %}', '|', 'Twig_Error_Syntax'),
            array('foo3', '<foo>', '{% foo3 "bar" %}', '|bar|', false),
            array('foo4', '<foo>', '{% foo4 1 + 2 %}', '|3|', false),
            array('foo5', '<foo:expression>', '{% foo5 1 + 2 %}', '|3|', false),
            array('foo6', '<foo:array>', '{% foo6 1 + 2 %}', '|3|', 'Twig_Error_Syntax'),
            array('foo7', '<foo>', '{% foo7 %}', '|3|', 'Twig_Error_Syntax'),
            array('foo8', '<foo:array>', '{% foo8 [1, 2] %}', '|Array|', false),
            array('foo9', '<foo> with <bar>', '{% foo9 "bar" with "foobar" %}', '|bar|with|foobar|', false),
            array('foo10', '<foo> [with <bar>]', '{% foo10 "bar" with "foobar" %}', '|bar|with|foobar|', false),
            array('foo11', '<foo> [with <bar>]', '{% foo11 "bar" %}', '|bar|', false),
        );
    }
}
