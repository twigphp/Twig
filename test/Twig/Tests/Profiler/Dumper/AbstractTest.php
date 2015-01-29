<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class Twig_Tests_Profiler_Dumper_AbstractTest extends PHPUnit_Framework_TestCase
{
    protected function getProfile()
    {
        $profile = new Twig_Profiler_Profile();
        $index = new Twig_Profiler_Profile('index.twig', Twig_Profiler_Profile::TEMPLATE);
        $profile->addProfile($index);
        $body = new Twig_Profiler_Profile('embedded.twig', Twig_Profiler_Profile::BLOCK, 'body');
        $body->leave();
        $index->addProfile($body);
        $embedded = new Twig_Profiler_Profile('embedded.twig', Twig_Profiler_Profile::TEMPLATE);
        $included = new Twig_Profiler_Profile('included.twig', Twig_Profiler_Profile::TEMPLATE);
        $embedded->addProfile($included);
        $index->addProfile($embedded);
        $included->leave();
        $embedded->leave();

        $macro = new Twig_Profiler_Profile('index.twig', Twig_Profiler_Profile::MACRO, 'foo');
        $macro->leave();
        $index->addProfile($macro);

        $embedded = clone $embedded;
        $index->addProfile($embedded);
        usleep(500);
        $embedded->leave();

        usleep(4500);
        $index->leave();

        $profile->leave();

        return $profile;
    }
}
