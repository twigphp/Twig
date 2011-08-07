<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_IntegrationTest extends PHPUnit_Framework_TestCase
{
    public function getTests()
    {
        $fixturesDir = realpath(dirname(__FILE__).'/Fixtures/');
        $tests = array();

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixturesDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!preg_match('/\.test$/', $file)) {
                continue;
            }

            $test = file_get_contents($file->getRealpath());

            if (preg_match('/--TEST--\s*(.*?)\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*))+)\s*--EXCEPTION--\s*(.*)/s', $test, $match)) {
                $message = $match[1];
                $exception = $match[3];
                $templates = $this->parseTemplates($match[2]);
                $outputs = array();
            } elseif (preg_match('/--TEST--\s*(.*?)\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)--DATA--.*?--EXPECT--.*/s', $test, $match)) {
                $message = $match[1];
                $exception = false;
                $templates = $this->parseTemplates($match[2]);
                preg_match_all('/--DATA--(.*?)--EXPECT--(.*?)(?=\-\-DATA\-\-|$)/s', $test, $outputs, PREG_SET_ORDER);
            } else {
                throw new InvalidArgumentException(sprintf('Test "%s" is not valid.', str_replace($fixturesDir.'/', '', $file)));
            }

            $tests[] = array(str_replace($fixturesDir.'/', '', $file), $message, $templates, $exception, $outputs);
        }

        return $tests;
    }

    /**
     * @dataProvider getTests
     */
    public function testIntegration($file, $message, $templates, $exception, $outputs)
    {
        $loader = new Twig_Loader_Array($templates);
        $twig = new Twig_Environment($loader, array('cache' => false));
        $twig->addExtension(new Twig_Extension_Escaper());
        $twig->addExtension(new TestExtension());

        try {
            $template = $twig->loadTemplate('index.twig');
        } catch (Exception $e) {
            if (false !== $exception) {
                $this->assertEquals(trim($exception), trim(sprintf('%s: %s', get_class($e), $e->getMessage())));

                return;
            }

            if ($e instanceof Twig_Error_Syntax) {
                $e->setTemplateFile($file);

                throw $e;
            }

            throw new Twig_Error($e->getMessage().' (in '.$file.')');
        }

        foreach ($outputs as $match) {
            try {
                $output = trim($template->render(eval($match[1].';')), "\n ");
            } catch (Exception $e) {
                $output = trim(sprintf('%s: %s', get_class($e), $e->getMessage()));
            }
            $expected = trim($match[2], "\n ");

            if ($expected != $output)  {
                echo 'Compiled template that failed:';

                foreach (array_keys($templates) as $name)  {
                    echo "Template: $name\n";
                    $source = $loader->getSource($name);
                    echo $twig->compile($twig->parse($twig->tokenize($source, $name)));
                }
            }
            $this->assertEquals($expected, $output, $message.' (in '.$file.')');
        }
    }

    protected function parseTemplates($test)
    {
        $templates = array();
        preg_match_all('/--TEMPLATE(?:\((.*?)\))?--(.*?)(?=\-\-TEMPLATE|$)/s', $test, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $templates[($match[1] ? $match[1] : 'index.twig')] = $match[2];
        }

        return $templates;
    }
}

function test_foo($value = 'foo')
{
    return $value;
}

class Foo
{
    const BAR_NAME = 'bar';

    public function bar($param1 = null, $param2 = null)
    {
        return 'bar'.($param1 ? '_'.$param1 : '').($param2 ? '-'.$param2 : '');
    }

    public function getFoo()
    {
        return 'foo';
    }

    public function getSelf()
    {
        return $this;
    }

    public function is()
    {
        return 'is';
    }

    public function in()
    {
        return 'in';
    }

    public function not()
    {
        return 'not';
    }

    public function strToLower($value)
    {
        return strtolower($value);
    }
}

class TestTokenParser_☃ extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_Print(new Twig_Node_Expression_Constant('☃', -1), -1);
    }

    public function getTag()
    {
        return '☃';
    }
}

class TestExtension extends Twig_Extension
{
    public function getTokenParsers()
    {
        return array(
            new TestTokenParser_☃(),
        );
    }

    public function getFilters()
    {
        return array(
            '☃' => new Twig_Filter_Method($this, '☃Filter'),
            'escape_and_nl2br' => new Twig_Filter_Method($this, 'escape_and_nl2br', array('needs_environment' => true, 'is_safe' => array('html'))),
            'nl2br' => new Twig_Filter_Method($this, 'nl2br', array('pre_escape' => 'html', 'is_safe' => array('html'))),
            'escape_something' => new Twig_Filter_Method($this, 'escape_something', array('is_safe' => array('something'))),
        );
    }

    public function getFunctions()
    {
        return array(
            '☃' => new Twig_Function_Method($this, '☃Function'),
            'safe_br' => new Twig_Function_Method($this, 'br', array('is_safe' => array('html'))),
            'unsafe_br' => new Twig_Function_Method($this, 'br'),
        );
    }

    public function ☃Filter($value)
    {
        return "☃{$value}☃";
    }

    public function ☃Function($value)
    {
        return "☃{$value}☃";
    }

    /**
     * nl2br which also escapes, for testing escaper filters
     */
    public function escape_and_nl2br($env, $value, $sep = '<br />')
    {
        return $this->nl2br(twig_escape_filter($env, $value, 'html'), $sep);
    }

    /**
     * nl2br only, for testing filters with pre_escape
     */
    public function nl2br($value, $sep = '<br />')
    {
        // not secure if $value contains html tags (not only entities)
        // don't use
        return str_replace("\n", "$sep\n", $value);
    }

    public function escape_something($value)
    {
        return strtoupper($value);
    }

    public function br()
    {
        return '<br />';
    }

    public function getName()
    {
        return 'test';
    }
}
