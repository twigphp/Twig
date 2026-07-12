<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Profiler\Dumper;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Profiler\Dumper\HtmlDumper;
use Twig\Profiler\Profile;

class HtmlTest extends ProfilerTestCase
{
    public function testDump(): void
    {
        $dumper = new HtmlDumper();
        $this->assertStringMatchesFormat(<<<EOF
<pre>main <span style="color: #d44">%d.%dms/%d%</span>
└ <span style="background-color: #ffd">index.twig</span> <span style="color: #d44">%d.%dms/%d%</span>
  └ embedded.twig::block(<span style="background-color: #dfd">body</span>)
  └ <span style="background-color: #ffd">embedded.twig</span>
  │ └ <span style="background-color: #ffd">included.twig</span>
  └ index.twig::macro(<span style="background-color: #ddf">foo</span>)
  └ <span style="background-color: #ffd">embedded.twig</span>
    └ <span style="background-color: #ffd">included.twig</span>
</pre>
EOF, $dumper->dump($this->getProfile()));
    }

    public function testDumpEscapesTemplateAndProfileNames(): void
    {
        $root = new Profile('main');
        $child = new Profile('<img src=x onerror=alert(1)>', Profile::TEMPLATE);
        $grandchild = new Profile('<img src=x onerror=alert(2)>', Profile::MACRO, '<img src=x onerror=alert(3)>');

        (new \ReflectionProperty($child, 'profiles'))->setValue($child, [$grandchild]);
        (new \ReflectionProperty($root, 'profiles'))->setValue($root, [$child]);

        $output = (new HtmlDumper())->dump($root);

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $output);
        $this->assertStringNotContainsString('<img src=x onerror=alert(2)>', $output);
        $this->assertStringNotContainsString('<img src=x onerror=alert(3)>', $output);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $output);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $output);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(3)&gt;', $output);
    }

    public function testDumpEscapesRootProfileName(): void
    {
        $root = new Profile('template-name', Profile::ROOT, '<img src=x onerror=alert(1)>');

        $output = (new HtmlDumper())->dump($root);

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $output);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $output);
    }
}
