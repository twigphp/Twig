<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_SpacelessTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $body = new \Twig\Node\Node([new \Twig\Node\TextNode('<div>   <div>   foo   </div>   </div>', 1)]);
        $node = new \Twig\Node\SpacelessNode($body, 1);

        $this->assertEquals($body, $node->getNode('body'));
    }

    public function getTests()
    {
        $body = new \Twig\Node\Node([new \Twig\Node\TextNode('<div>   <div>   foo   </div>   </div>', 1)]);
        $node = new \Twig\Node\SpacelessNode($body, 1);

        return [
            [$node, <<<EOF
// line 1
ob_start();
echo "<div>   <div>   foo   </div>   </div>";
echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));
EOF
            ],
        ];
    }
}
