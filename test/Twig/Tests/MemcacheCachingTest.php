<?php

class Twig_Tests_MemcacheCachingTest extends PHPUnit_Framework_TestCase
{
    protected $memcache;
    protected $env;

    public function setUp()
    {
        if (!class_exists('Memcached')) {
            $this->markTestSkipped('Your environment does not have Memcached installed.');
        }
        
        $this->memcache = new Memcached;
        $this->memcache->addServer('localhost', 11211);

        $this->env = new Twig_Environment(new Twig_Loader_String(), array('cache' => new Twig_Cache_Memcache($this->memcache)));
    }

    public function testWritingToMemcache()
    {
        $name = 'This is just a text.';
        $template = $this->env->loadTemplate($name);
        $cacheFileName = $this->env->getCacheFilename($name);

        $this->assertRegexp('/This is just a text./', $this->memcache->get($cacheFileName));
    }

    public function testClearingMemcache()
    {
        $name = 'Will be deleted.';
        $template = $this->env->loadTemplate($name);
        $cacheFileName = $this->env->getCacheFilename($name);

        $this->assertRegexp('/Will be deleted./', $this->memcache->get($cacheFileName));
        $this->env->clearCacheFiles();
        $this->assertFalse($this->memcache->get($cacheFileName));
    }
}
