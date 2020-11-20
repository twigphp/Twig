<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension;

use Twig\Profiler\NodeVisitor\ProfilerNodeVisitor;
use Twig\Profiler\Profile;

/**
 * Class ProfilerExtension
 * @package Twig\Extension
 */
class ProfilerExtension extends AbstractExtension
{
    /**
     * @var array
     */
    private $actives = [];

    /**
     * ProfilerExtension constructor.
     * @param Profile $profile
     */
    public function __construct(Profile $profile)
    {
        $this->actives[] = $profile;
    }

    /**
     * @param Profile $profile
     * @return void
     */
    public function enter(Profile $profile)
    {
        $this->actives[0]->addProfile($profile);
        array_unshift($this->actives, $profile);
    }

    /**
     * @param Profile $profile
     * @return void
     */
    public function leave(Profile $profile)
    {
        $profile->leave();
        array_shift($this->actives);

        if (1 === \count($this->actives)) {
            $this->actives[0]->leave();
        }
    }

    /**
     * @return ProfilerNodeVisitor[]
     */
    public function getNodeVisitors(): array
    {
        return [new ProfilerNodeVisitor(static::class)];
    }
}
