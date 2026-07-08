<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Loader\ArrayLoader;
use Twig\Source;
use Twig\Token;

class LexerTest extends TestCase
{
    public function testNameLabelForTag()
    {
        $template = '{% § %}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        $stream->expect(Token::BLOCK_START_TYPE);
        $this->assertSame('§', $stream->expect(Token::NAME_TYPE)->getValue());
    }

    public function testNameLabelForFunction()
    {
        $template = '{{ §() }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        $stream->expect(Token::VAR_START_TYPE);
        $this->assertSame('§', $stream->expect(Token::NAME_TYPE)->getValue());
    }

    public function testBracketsNesting()
    {
        $template = '{{ {"a":{"b":"c"}} }}';

        $this->assertEquals(2, $this->countToken($template, Token::PUNCTUATION_TYPE, '{'));
        $this->assertEquals(2, $this->countToken($template, Token::PUNCTUATION_TYPE, '}'));
    }

    protected function countToken($template, $type, $value = null)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        $count = 0;
        while (!$stream->isEOF()) {
            $token = $stream->next();
            if ($token->test($type)) {
                if (null === $value || $value === $token->getValue()) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function testLineDirective()
    {
        $template = "foo\n"
            ."bar\n"
            ."{% line 10 %}\n"
            ."{{\n"
            ."baz\n"
            ."}}\n";

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        // foo\nbar\n
        $this->assertSame(1, $stream->expect(Token::TEXT_TYPE)->getLine());
        // \n (after {% line %})
        $this->assertSame(10, $stream->expect(Token::TEXT_TYPE)->getLine());
        // {{
        $this->assertSame(11, $stream->expect(Token::VAR_START_TYPE)->getLine());
        // baz
        $this->assertSame(12, $stream->expect(Token::NAME_TYPE)->getLine());
    }

    public function testLineDirectiveInline()
    {
        $template = "foo\n"
            ."bar{% line 10 %}{{\n"
            ."baz\n"
            ."}}\n";

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        // foo\nbar
        $this->assertSame(1, $stream->expect(Token::TEXT_TYPE)->getLine());
        // {{
        $this->assertSame(10, $stream->expect(Token::VAR_START_TYPE)->getLine());
        // baz
        $this->assertSame(11, $stream->expect(Token::NAME_TYPE)->getLine());
    }

    public function testLongComments()
    {
        $template = '{# '.str_repeat('*', 100000).' #}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $lexer->tokenize(new Source($template, 'index'));

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testLongVerbatim()
    {
        $template = '{% verbatim %}'.str_repeat('*', 100000).'{% endverbatim %}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $lexer->tokenize(new Source($template, 'index'));

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testLongVar()
    {
        $template = '{{ '.str_repeat('x', 100000).' }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $lexer->tokenize(new Source($template, 'index'));

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testLongBlock()
    {
        $template = '{% '.str_repeat('x', 100000).' %}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $lexer->tokenize(new Source($template, 'index'));

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testBigNumbers()
    {
        $template = '{{ 922337203685477580700 }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->next();
        $node = $stream->next();
        $this->assertEquals('922337203685477580700', $node->getValue());
    }

    #[DataProvider('getStringWithEscapedDelimiter')]
    public function testStringWithEscapedDelimiter(string $template, string $expected)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $token = $stream->expect(Token::STRING_TYPE);
        $this->assertSame($expected, $token->getValue());
    }

    public static function getStringWithEscapedDelimiter()
    {
        yield [
            <<<'EOF'
            {{ '\x6' }}
            EOF,
            "\x6",
        ];
        yield [
            <<<'EOF'
            {{ '\065\x64' }}
            EOF,
            "\065\x64",
        ];
        yield 'octal escape overflowing a single byte is constrained with % 256' => [
            <<<'EOF'
            {{ '\777' }}
            EOF,
            "\xff",
        ];
        yield 'octal escape exactly equal to 256 wraps around to a NUL byte' => [
            <<<'EOF'
            {{ '\400' }}
            EOF,
            "\x00",
        ];
        yield [
            <<<'EOF'
            {{ 'App\\Test' }}
            EOF,
            'App\\Test',
        ];
        yield [
            <<<'EOF'
            {{ "App\#{var}" }}
            EOF,
            'App#{var}',
        ];
        yield [
            <<<'EOF'
            {{ 'foo \' bar' }}
            EOF,
            <<<'EOF'
            foo ' bar
            EOF,
        ];
        yield [
            <<<'EOF'
            {{ "foo \" bar" }}
            EOF,
            'foo " bar',
        ];
        yield [
            <<<'EOF'
            {{ '\f\n\r\t\v' }}
            EOF,
            "\f\n\r\t\v",
        ];
        yield [
            <<<'EOF'
            {{ '\\f\\n\\r\\t\\v' }}
            EOF,
            '\\f\\n\\r\\t\\v',
        ];
        yield [
            <<<'EOF'
            {{ 'Ymd\\THis' }}
            EOF,
            <<<'EOF'
            Ymd\THis
            EOF,
        ];
    }

    public function testStringWithInterpolation()
    {
        $template = 'foo {{ "bar #{ baz + 1 }" }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::TEXT_TYPE, 'foo ');
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'bar ');
        $stream->expect(Token::INTERPOLATION_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'baz');
        $stream->expect(Token::OPERATOR_TYPE, '+');
        $stream->expect(Token::NUMBER_TYPE, '1');
        $stream->expect(Token::INTERPOLATION_END_TYPE);
        $stream->expect(Token::VAR_END_TYPE);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testStringWithEscapedInterpolation()
    {
        $template = '{{ "bar \#{baz+1}" }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'bar #{baz+1}');
        $stream->expect(Token::VAR_END_TYPE);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testStringWithHash()
    {
        $template = '{{ "bar # baz" }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'bar # baz');
        $stream->expect(Token::VAR_END_TYPE);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testStringWithUnterminatedInterpolation()
    {
        $template = '{{ "bar #{x" }}';
        $lexer = new Lexer(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unclosed """');

        $lexer->tokenize(new Source($template, 'index'));
    }

    public function testStringWithNestedInterpolations()
    {
        $template = '{{ "bar #{ "foo#{bar}" }" }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'bar ');
        $stream->expect(Token::INTERPOLATION_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'foo');
        $stream->expect(Token::INTERPOLATION_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'bar');
        $stream->expect(Token::INTERPOLATION_END_TYPE);
        $stream->expect(Token::INTERPOLATION_END_TYPE);
        $stream->expect(Token::VAR_END_TYPE);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testStringWithNestedInterpolationsInBlock()
    {
        $template = '{% foo "bar #{ "foo#{bar}" }" %}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::BLOCK_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'foo');
        $stream->expect(Token::STRING_TYPE, 'bar ');
        $stream->expect(Token::INTERPOLATION_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'foo');
        $stream->expect(Token::INTERPOLATION_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'bar');
        $stream->expect(Token::INTERPOLATION_END_TYPE);
        $stream->expect(Token::INTERPOLATION_END_TYPE);
        $stream->expect(Token::BLOCK_END_TYPE);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testOperatorEndingWithALetterAtTheEndOfALine()
    {
        $template = "{{ 1 and\n0}}";

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::NUMBER_TYPE, 1);
        $stream->expect(Token::OPERATOR_TYPE, 'and');

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testFilterAndAttributeNamedAfterOperator()
    {
        // Ensure that filters/attributes aren't mistaken for operators when their names conflict
        // (see https://github.com/twigphp/Twig/issues/4767)
        $template = '{{ \'foo\'|and }}'
            .'{{ \'bar\' | and }}'
            .'{{ foo.and }}'
            .'{{ bar . and }}'
            .'{{ foo and bar }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        foreach (['foo', 'bar'] as $value) {
            $stream->expect(Token::VAR_START_TYPE);
            $stream->expect(Token::STRING_TYPE, $value);
            $stream->expect(Token::OPERATOR_TYPE, '|');
            $stream->expect(Token::NAME_TYPE, 'and');
            $stream->expect(Token::VAR_END_TYPE);
        }
        foreach (['foo', 'bar'] as $value) {
            $stream->expect(Token::VAR_START_TYPE);
            $stream->expect(Token::NAME_TYPE, $value);
            $stream->expect(Token::OPERATOR_TYPE, '.');
            $stream->expect(Token::NAME_TYPE, 'and');
            $stream->expect(Token::VAR_END_TYPE);
        }
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'foo');
        $stream->expect(Token::OPERATOR_TYPE, 'and');
        $stream->expect(Token::NAME_TYPE, 'bar');
        $stream->expect(Token::VAR_END_TYPE);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function testLiteralIsNotAnOperator()
    {
        // "literal" is the name of the LiteralExpressionParser but should not be treated as an operator token
        $template = '{{ literal }}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'literal');
        $stream->expect(Token::VAR_END_TYPE);

        $this->addToAssertionCount(1);
    }

    public function testUnterminatedVariable()
    {
        $template = '

{{

bar


';

        $lexer = new Lexer(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unclosed "variable" in "index" at line 3');
        $lexer->tokenize(new Source($template, 'index'));
    }

    public function testUnterminatedBlock()
    {
        $template = '

{%

bar


';

        $lexer = new Lexer(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unclosed "block" in "index" at line 3');

        $lexer->tokenize(new Source($template, 'index'));
    }

    public function testOverridingSyntax()
    {
        $template = '[# comment #]{# variable #}/# if true #/true/# endif #/';
        $lexer = new Lexer(new Environment(new ArrayLoader()), [
            'tag_comment' => ['[#', '#]'],
            'tag_block' => ['/#', '#/'],
            'tag_variable' => ['{#', '#}'],
        ]);
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'variable');
        $stream->expect(Token::VAR_END_TYPE);
        $stream->expect(Token::BLOCK_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'if');
        $stream->expect(Token::NAME_TYPE, 'true');
        $stream->expect(Token::BLOCK_END_TYPE);
        $stream->expect(Token::TEXT_TYPE, 'true');
        $stream->expect(Token::BLOCK_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'endif');
        $stream->expect(Token::BLOCK_END_TYPE);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    #[DataProvider('getTemplateForErrorsAtTheEndOfTheStream')]
    public function testErrorsAtTheEndOfTheStream(string $template)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        set_error_handler(function () {
            $this->fail('Lexer should not emit warnings.');
        });
        try {
            $lexer->tokenize(new Source($template, 'index'));
            $this->addToAssertionCount(1);
        } finally {
            restore_error_handler();
        }
    }

    public static function getTemplateForErrorsAtTheEndOfTheStream()
    {
        yield ['{{ ='];
        yield ['{{ ..'];
    }

    #[DataProvider('getTemplateForStrings')]
    public function testStrings(string $expected)
    {
        $template = '{{ "'.$expected.'" }}';
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, $expected);

        $template = "{{ '".$expected."' }}";
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, $expected);

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public static function getTemplateForStrings()
    {
        yield ['日本では、春になると桜の花が咲きます。多くの人々は、公園や川の近くに集まり、お花見を楽しみます。桜の花びらが風に舞い、まるで雪のように見える瞬間は、とても美しいです。'];
        yield ['في العالم العربي، يُعتبر الخط العربي أحد أجمل أشكال الفن. يُستخدم الخط في تزيين المساجد والكتب والمخطوطات القديمة. يتميز الخط العربي بجماله وتناسقه، ويُعتبر رمزًا للثقافة الإسلامية.'];
    }

    public function testInlineCommentWithHashInString()
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source('{{ "me # this is NOT an inline comment" }}', 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'me # this is NOT an inline comment');
        $stream->expect(Token::VAR_END_TYPE);
        $this->assertTrue($stream->isEOF());
    }

    #[DataProvider('getTemplateForInlineCommentsForVariable')]
    public function testInlineCommentForVariable(string $template)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::VAR_START_TYPE);
        $stream->expect(Token::STRING_TYPE, 'me');
        $stream->expect(Token::VAR_END_TYPE);
        $this->assertTrue($stream->isEOF());
    }

    public static function getTemplateForInlineCommentsForVariable()
    {
        yield ['{{
            "me"
            # this is an inline comment
        }}'];
        yield ['{{
            # this is an inline comment
            "me"
        }}'];
        yield ['{{
            "me" # this is an inline comment
        }}'];
        yield ['{{
            # this is an inline comment
            "me" # this is an inline comment
            # this is an inline comment
        }}'];
    }

    #[DataProvider('getTemplateForInlineCommentsForBlock')]
    public function testInlineCommentForBlock(string $template)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $stream->expect(Token::BLOCK_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'if');
        $stream->expect(Token::NAME_TYPE, 'true');
        $stream->expect(Token::BLOCK_END_TYPE);
        $stream->expect(Token::TEXT_TYPE, 'me');
        $stream->expect(Token::BLOCK_START_TYPE);
        $stream->expect(Token::NAME_TYPE, 'endif');
        $stream->expect(Token::BLOCK_END_TYPE);
        $this->assertTrue($stream->isEOF());
    }

    public static function getTemplateForInlineCommentsForBlock()
    {
        yield ['{%
            if true
            # this is an inline comment
        %}me{% endif %}'];
        yield ['{%
            # this is an inline comment
            if true
        %}me{% endif %}'];
        yield ['{%
            if true # this is an inline comment
        %}me{% endif %}'];
        yield ['{%
            # this is an inline comment
            if true # this is an inline comment
            # this is an inline comment
        %}me{% endif %}'];
    }

    #[DataProvider('getTemplateForInlineCommentsForComment')]
    public function testInlineCommentForComment(string $template)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));
        $this->assertTrue($stream->isEOF());
    }

    public static function getTemplateForInlineCommentsForComment()
    {
        yield ['{#
            Some regular comment # this is an inline comment
        #}'];
    }

    #[DataProvider('getTemplateForUnclosedBracketInExpression')]
    public function testUnclosedBracketInExpression(string $template, string $bracket)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(\sprintf('Unclosed "%s" in "index" at line 1.', $bracket));

        $lexer->tokenize(new Source($template, 'index'));
    }

    public static function getTemplateForUnclosedBracketInExpression()
    {
        yield ['{{ (1 + 3 }}', '('];
        yield ['{{ obj["a" }}', '['];
        yield ['{{ ({ a: 1) }}', '{'];
        yield ['{{ (([1]) + 3 }}', '('];
    }

    #[DataProvider('getTemplateForUnexpectedBracketInExpression')]
    public function testUnexpectedBracketInExpression(string $template, string $bracket, int $column)
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(\sprintf('Unexpected "%s" in "index" at line 1 column %d.', $bracket, $column));

        $lexer->tokenize(new Source($template, 'index'));
    }

    public static function getTemplateForUnexpectedBracketInExpression()
    {
        yield ['{{ 1 + 3) }}', ')', 9];
        yield ['{{ obj] }}', ']', 7];
        yield ['{{ { a: 1 }}', '}', 12];
        yield ['{{ ([1] + 3)) }}', ')', 13];
    }

    public function testTokensCarryTheirSourceOffset()
    {
        $template = 'Hello {{ name }}!';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        // [type, offset] pairs; offsets point at the start of each lexeme in the source
        $expected = [
            [Token::TEXT_TYPE, 0],   // "Hello "
            [Token::VAR_START_TYPE, 6],   // "{{"
            [Token::NAME_TYPE, 9],   // "name"
            [Token::VAR_END_TYPE, 14],  // "}}"
            [Token::TEXT_TYPE, 16],  // "!"
            [Token::EOF_TYPE, 17],
        ];

        foreach ($expected as [$type, $offset]) {
            $token = $stream->getCurrent();
            $this->assertTrue($token->test($type), \sprintf('Expected token "%s".', Token::typeToEnglish($type)));
            $this->assertSame($offset, $token->getOffset());

            if (!$stream->isEOF()) {
                $stream->next();
            }
        }
    }

    public function testOffsetsAllowRecoveringTheRawExpressionSource()
    {
        $template = "Hello {{ name|upper ~ '!' }}";

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        $stream->expect(Token::TEXT_TYPE);
        $start = $stream->expect(Token::VAR_START_TYPE)->getOffset();
        while (!$stream->test(Token::VAR_END_TYPE)) {
            $stream->next();
        }
        $end = $stream->getCurrent()->getOffset();

        // slice the raw expression out of the original source, between "{{" and "}}"
        $raw = trim(substr($template, $start + 2, $end - $start - 2));

        $this->assertSame("name|upper ~ '!'", $raw);
    }

    public function testOffsetsReferToTheOriginalSourceWhenLineEndingsAreNormalized()
    {
        $template = "Hello\r\n{{ name }}";

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        $stream->expect(Token::TEXT_TYPE);
        $start = $stream->expect(Token::VAR_START_TYPE)->getOffset();
        $this->assertSame('{{', substr($template, $start, 2));

        $name = $stream->expect(Token::NAME_TYPE);
        $this->assertSame('name', substr($template, $name->getOffset(), 4));

        $end = $stream->expect(Token::VAR_END_TYPE)->getOffset();
        $this->assertSame('name', trim(substr($template, $start + 2, $end - $start - 2)));
    }

    public function testBlockTagDelimitersPointAtTheMarkers()
    {
        $template = '{% set x = 1 %}';

        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        // the opening "{%" and the closing "%}" both point at the marker itself,
        // not at the whitespace the closing regex also consumes
        $this->assertSame(0, $stream->expect(Token::BLOCK_START_TYPE)->getOffset());
        $this->assertSame('{%', substr($template, 0, 2));
        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            $stream->next();
        }
        $end = $stream->getCurrent()->getOffset();
        $this->assertSame('%}', substr($template, $end, 2));
    }

    public function testClosingDelimiterLineMatchesTheMarkerLine()
    {
        $template = "{% from 'forms.twig'\n  %}";
        $env = new Environment(new ArrayLoader());

        try {
            $env->parse($env->tokenize(new Source($template, 'index')));
            $this->fail('A SyntaxError should have been thrown.');
        } catch (SyntaxError $e) {
            $this->assertSame(2, $e->getTemplateLine());
            $this->assertSame(3, $e->getTemplateColumn());
            $this->assertStringEndsWith('at line 2 column 3.', $e->getMessage());
        }
    }

    public function testSyntheticTokensHaveNoOffset()
    {
        $this->assertNull((new Token(Token::NAME_TYPE, 'foo', 1))->getOffset());
    }

    public function testSyntaxErrorReportsTheColumn()
    {
        $lexer = new Lexer(new Environment(new ArrayLoader()));

        try {
            $lexer->tokenize(new Source("{{ 1 + 3) }}\n{{ ok }}", 'index'));
            $this->fail('A SyntaxError should have been thrown.');
        } catch (SyntaxError $e) {
            $this->assertSame(1, $e->getTemplateLine());
            $this->assertSame(9, $e->getTemplateColumn());
            $this->assertStringEndsWith('at line 1 column 9.', $e->getMessage());
        }
    }

    public function testSyntaxErrorColumnUsesOriginalSourceOffsets()
    {
        $template = "x\r\n{{ 1__2 }}";
        $env = new Environment(new ArrayLoader());

        try {
            $env->parse($env->tokenize(new Source($template, 'index')));
            $this->fail('A SyntaxError should have been thrown.');
        } catch (SyntaxError $e) {
            $this->assertSame(2, $e->getTemplateLine());
            $this->assertSame(5, $e->getTemplateColumn());
            $this->assertStringEndsWith('at line 2 column 5.', $e->getMessage());
        }
    }
}
