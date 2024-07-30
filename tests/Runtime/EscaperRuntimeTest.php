<?php

namespace Twig\Tests\Runtime;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;
use Twig\Runtime\EscaperRuntime;

class EscaperRuntimeTest extends TestCase
{
    /**
     * All character encodings supported by htmlspecialchars().
     */
    protected $htmlSpecialChars = [
        '\'' => '&#039;',
        '"' => '&quot;',
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
    ];

    protected $htmlAttrSpecialChars = [
        '\'' => '&#x27;',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '&#x0100;',
        '😀' => '&#x1F600;',
        /* Immune chars excluded */
        ',' => ',',
        '.' => '.',
        '-' => '-',
        '_' => '_',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '&#x0D;',
        "\n" => '&#x0A;',
        "\t" => '&#x09;',
        "\0" => '&#xFFFD;', // should use Unicode replacement char
        /* Encode chars as named entities where possible */
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
        '"' => '&quot;',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '&#x20;',
    ];

    protected $jsSpecialChars = [
        /* HTML special chars - escape without exception to hex */
        '<' => '\\u003C',
        '>' => '\\u003E',
        '\'' => '\\u0027',
        '"' => '\\u0022',
        '&' => '\\u0026',
        '/' => '\\/',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '\\u0100',
        '😀' => '\\uD83D\\uDE00',
        /* Immune chars excluded */
        ',' => ',',
        '.' => '.',
        '_' => '_',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '\r',
        "\n" => '\n',
        "\x08" => '\b',
        "\t" => '\t',
        "\x0C" => '\f',
        "\0" => '\\u0000',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '\\u0020',
    ];

    protected $urlSpecialChars = [
        /* HTML special chars - escape without exception to percent encoding */
        '<' => '%3C',
        '>' => '%3E',
        '\'' => '%27',
        '"' => '%22',
        '&' => '%26',
        /* Characters beyond ASCII value 255 to hex sequence */
        'Ā' => '%C4%80',
        /* Punctuation and unreserved check */
        ',' => '%2C',
        '.' => '.',
        '_' => '_',
        '-' => '-',
        ':' => '%3A',
        ';' => '%3B',
        '!' => '%21',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '%0D',
        "\n" => '%0A',
        "\t" => '%09',
        "\0" => '%00',
        /* PHP quirks from the past */
        ' ' => '%20',
        '~' => '~',
        '+' => '%2B',
    ];

    protected $cssSpecialChars = [
        /* HTML special chars - escape without exception to hex */
        '<' => '\\3C ',
        '>' => '\\3E ',
        '\'' => '\\27 ',
        '"' => '\\22 ',
        '&' => '\\26 ',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '\\100 ',
        /* Immune chars excluded */
        ',' => '\\2C ',
        '.' => '\\2E ',
        '_' => '\\5F ',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '\\D ',
        "\n" => '\\A ',
        "\t" => '\\9 ',
        "\0" => '\\0 ',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '\\20 ',
    ];

    public function testHtmlEscapingConvertsSpecialChars()
    {
        foreach ($this->htmlSpecialChars as $key => $value) {
            $this->assertEquals($value, (new EscaperRuntime())->escape($key, 'html'), 'Failed to escape: '.$key);
        }
    }

    public function testHtmlAttributeEscapingConvertsSpecialChars()
    {
        foreach ($this->htmlAttrSpecialChars as $key => $value) {
            $this->assertEquals($value, (new EscaperRuntime())->escape($key, 'html_attr'), 'Failed to escape: '.$key);
        }
    }

    public function testJavascriptEscapingConvertsSpecialChars()
    {
        foreach ($this->jsSpecialChars as $key => $value) {
            $this->assertEquals($value, (new EscaperRuntime())->escape($key, 'js'), 'Failed to escape: '.$key);
        }
    }

    public function testJavascriptEscapingConvertsSpecialCharsWithInternalEncoding()
    {
        $previousInternalEncoding = mb_internal_encoding();
        try {
            mb_internal_encoding('ISO-8859-1');
            foreach ($this->jsSpecialChars as $key => $value) {
                $this->assertEquals($value, (new EscaperRuntime())->escape($key, 'js'), 'Failed to escape: '.$key);
            }
        } finally {
            if (false !== $previousInternalEncoding) {
                mb_internal_encoding($previousInternalEncoding);
            }
        }
    }

    public function testJavascriptEscapingReturnsStringIfZeroLength()
    {
        $this->assertEquals('', (new EscaperRuntime())->escape('', 'js'));
    }

    public function testJavascriptEscapingReturnsStringIfContainsOnlyDigits()
    {
        $this->assertEquals('123', (new EscaperRuntime())->escape('123', 'js'));
    }

    public function testCssEscapingConvertsSpecialChars()
    {
        foreach ($this->cssSpecialChars as $key => $value) {
            $this->assertEquals($value, (new EscaperRuntime())->escape($key, 'css'), 'Failed to escape: '.$key);
        }
    }

    public function testCssEscapingReturnsStringIfZeroLength()
    {
        $this->assertEquals('', (new EscaperRuntime())->escape('', 'css'));
    }

    public function testCssEscapingReturnsStringIfContainsOnlyDigits()
    {
        $this->assertEquals('123', (new EscaperRuntime())->escape('123', 'css'));
    }

    public function testUrlEscapingConvertsSpecialChars()
    {
        foreach ($this->urlSpecialChars as $key => $value) {
            $this->assertEquals($value, (new EscaperRuntime())->escape($key, 'url'), 'Failed to escape: '.$key);
        }
    }

    /**
     * Range tests to confirm escaped range of characters is within OWASP recommendation.
     */

    /**
     * Only testing the first few 2 ranges on this prot. function as that's all these
     * other range tests require.
     */
    public function testUnicodeCodepointConversionToUtf8()
    {
        $expected = ' ~ޙ';
        $codepoints = [0x20, 0x7E, 0x799];
        $result = '';
        foreach ($codepoints as $value) {
            $result .= $this->codepointToUtf8($value);
        }
        $this->assertEquals($expected, $result);
    }

    /**
     * Convert a Unicode Codepoint to a literal UTF-8 character.
     *
     * @param int $codepoint Unicode codepoint in hex notation
     *
     * @return string UTF-8 literal string
     */
    protected function codepointToUtf8($codepoint)
    {
        if ($codepoint < 0x80) {
            return \chr($codepoint);
        }
        if ($codepoint < 0x800) {
            return \chr($codepoint >> 6 & 0x3F | 0xC0)
                .\chr($codepoint & 0x3F | 0x80);
        }
        if ($codepoint < 0x10000) {
            return \chr($codepoint >> 12 & 0x0F | 0xE0)
                .\chr($codepoint >> 6 & 0x3F | 0x80)
                .\chr($codepoint & 0x3F | 0x80);
        }
        if ($codepoint < 0x110000) {
            return \chr($codepoint >> 18 & 0x07 | 0xF0)
                .\chr($codepoint >> 12 & 0x3F | 0x80)
                .\chr($codepoint >> 6 & 0x3F | 0x80)
                .\chr($codepoint & 0x3F | 0x80);
        }
        throw new \Exception('Codepoint requested outside of Unicode range.');
    }

    public function testJavascriptEscapingEscapesOwaspRecommendedRanges()
    {
        $immune = [',', '.', '_']; // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $this->assertEquals($literal, (new EscaperRuntime())->escape($literal, 'js'));
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (\in_array($literal, $immune)) {
                    $this->assertEquals($literal, (new EscaperRuntime())->escape($literal, 'js'));
                } else {
                    $this->assertNotEquals(
                        $literal,
                        (new EscaperRuntime())->escape($literal, 'js'),
                        "$literal should be escaped!");
                }
            }
        }
    }

    public function testHtmlAttributeEscapingEscapesOwaspRecommendedRanges()
    {
        $immune = [',', '.', '-', '_']; // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $this->assertEquals($literal, (new EscaperRuntime())->escape($literal, 'html_attr'));
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (\in_array($literal, $immune)) {
                    $this->assertEquals($literal, (new EscaperRuntime())->escape($literal, 'html_attr'));
                } else {
                    $this->assertNotEquals(
                        $literal,
                        (new EscaperRuntime())->escape($literal, 'html_attr'),
                        "$literal should be escaped!");
                }
            }
        }
    }

    public function testCssEscapingEscapesOwaspRecommendedRanges()
    {
        // CSS has no exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $this->assertEquals($literal, (new EscaperRuntime())->escape($literal, 'css'));
            } else {
                $literal = $this->codepointToUtf8($chr);
                $this->assertNotEquals(
                    $literal,
                    (new EscaperRuntime())->escape($literal, 'css'),
                    "$literal should be escaped!");
            }
        }
    }

    public function testUnknownCustomEscaper()
    {
        $this->expectException(RuntimeError::class);

        (new EscaperRuntime())->escape('foo', 'bar');
    }

    /**
     * @dataProvider provideCustomEscaperCases
     */
    public function testCustomEscaper($expected, $string, $strategy, $charset)
    {
        $escaper = new EscaperRuntime();
        $escaper->setEscaper('foo', 'Twig\Tests\Runtime\escaper');
        $this->assertSame($expected, $escaper->escape($string, $strategy, $charset));
    }

    public function provideCustomEscaperCases()
    {
        return [
            ['foo**ISO-8859-1', 'foo', 'foo', 'ISO-8859-1'],
            ['**ISO-8859-1', null, 'foo', 'ISO-8859-1'],
            ['42**UTF-8', 42, 'foo', null],
        ];
    }

    /**
     * @dataProvider provideObjectsForEscaping
     */
    public function testObjectEscaping(string $escapedHtml, string $escapedJs, array $safeClasses)
    {
        $obj = new Extension_TestClass();
        $escaper = new EscaperRuntime();
        $escaper->setSafeClasses($safeClasses);
        $this->assertSame($escapedHtml, $escaper->escape($obj, 'html', null, true));
        $this->assertSame($escapedJs, $escaper->escape($obj, 'js', null, true));
    }

    public function provideObjectsForEscaping()
    {
        return [
            ['&lt;br /&gt;', '<br />', ['\Twig\Tests\Runtime\Extension_TestClass' => ['js']]],
            ['<br />', '\u003Cbr\u0020\/\u003E', ['\Twig\Tests\Runtime\Extension_TestClass' => ['html']]],
            ['&lt;br /&gt;', '<br />', ['\Twig\Tests\Runtime\Extension_SafeHtmlInterface' => ['js']]],
            ['<br />', '<br />', ['\Twig\Tests\Runtime\Extension_SafeHtmlInterface' => ['all']]],
        ];
    }
}

function escaper($string, $charset)
{
    return $string.'**'.$charset;
}

interface Extension_SafeHtmlInterface
{
}
class Extension_TestClass implements Extension_SafeHtmlInterface
{
    public function __toString()
    {
        return '<br />';
    }
}
