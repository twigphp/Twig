<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\DeprecatedCallableInfo;

class DeprecatedCallableInfoTest extends TestCase
{
    /**
     * @dataProvider provideTestsForTriggerDeprecation
     */
    public function testTriggerDeprecation($expected, DeprecatedCallableInfo $info)
    {
        $info->setType('function');
        $info->setName('foo');

        $deprecations = [];
        try {
            set_error_handler(function ($type, $msg) use (&$deprecations) {
                if (\E_USER_DEPRECATED === $type) {
                    $deprecations[] = $msg;
                }
    
                return false;
            });

            $info->triggerDeprecation('foo.twig', 1);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([$expected], $deprecations);
    }

    public static function provideTestsForTriggerDeprecation(): iterable
    {
        yield ['Since foo/bar 1.1: Twig Function "foo" is deprecated in foo.twig at line 1.', new DeprecatedCallableInfo('foo/bar', '1.1')];
        yield ['Since foo/bar 1.1: Twig Function "foo" is deprecated; use "alt_foo" from the "all/bar" package (available since version 12.10) instead in foo.twig at line 1.', new DeprecatedCallableInfo('foo/bar', '1.1', 'alt_foo', 'all/bar', '12.10')];
        yield ['Since foo/bar 1.1: Twig Function "foo" is deprecated; use "alt_foo" from the "all/bar" package instead in foo.twig at line 1.', new DeprecatedCallableInfo('foo/bar', '1.1', 'alt_foo', 'all/bar')];
        yield ['Since foo/bar 1.1: Twig Function "foo" is deprecated; use "alt_foo" instead in foo.twig at line 1.', new DeprecatedCallableInfo('foo/bar', '1.1', 'alt_foo')];
    }

    public function testTriggerDeprecationWithoutFileOrLine()
    {
        $info = new DeprecatedCallableInfo('foo/bar', '1.1');
        $info->setType('function');
        $info->setName('foo');

        $deprecations = [];
        try {
            set_error_handler(function ($type, $msg) use (&$deprecations) {
                if (\E_USER_DEPRECATED === $type) {
                    $deprecations[] = $msg;
                }
    
                return false;
            });

            $info->triggerDeprecation();
            $info->triggerDeprecation('foo.twig');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([
            'Since foo/bar 1.1: Twig Function "foo" is deprecated.',
            'Since foo/bar 1.1: Twig Function "foo" is deprecated in foo.twig.',
        ], $deprecations);
    }
}
