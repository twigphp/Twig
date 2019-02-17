<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Extension\AbstractExtension;
use Twig\Profiler\NodeVisitor\ProfilerNodeVisitor;
use Twig\Profiler\Profile;

class Twig_Extension_Profiler extends AbstractExtension
{
    private $actives = [];

    public function __construct(Profile $profile)
    {
        $this->actives[] = $profile;
    }

    public function enter(Profile $profile)
    {
        $this->actives[0]->addProfile($profile);
        array_unshift($this->actives, $profile);
    }

    public function leave(Profile $profile)
    {
        $profile->leave();
        array_shift($this->actives);

        if (1 === \count($this->actives)) {
            $this->actives[0]->leave();
        }
    }

    public function getNodeVisitors()
    {
        return [new ProfilerNodeVisitor(\get_class($this))];
    }

    public function getName()
    {
        return 'profiler';
    }
}

class_alias('Twig_Extension_Profiler', 'Twig\Extension\ProfilerExtension', false);
class_exists('Twig_Profiler_Profile');
