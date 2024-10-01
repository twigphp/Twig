<?php

namespace Twig\Extra\Html\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Extra\Html\HtmlExtension;

class HtmlAttrMergeTest extends TestCase
{
    /**
     * @dataProvider htmlAttrProvider
     */
    public function testMerge(array $expected, array $inputs): void
    {
        $result = HtmlExtension::htmlAttrMerge(...$inputs);

        self::assertSame($expected, $result);
    }

    public function htmlAttrProvider(): \Generator
    {
        yield 'simple test' => [
            ['id' => 'some-id', 'class' => ['some-class']],
            [
                ['id' => 'some-id'],
                ['class' => 'some-class'],
            ]
        ];
    }
}
