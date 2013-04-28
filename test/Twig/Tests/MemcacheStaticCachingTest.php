<?php

class Twig_Tests_MemcacheCachingTest extends PHPUnit_Framework_TestCase
{
    protected $memcache;
    protected $twig;

    public function setUp()
    {
        if (!class_exists('Memcached')) {
            $this->markTestSkipped('Your environment does not have Memcached installed.');
        }
        
        $this->memcache = new Memcached;
        $this->memcache->addServer('127.0.0.1', 11211);   

        $this->twig = new Twig_Environment(new Twig_Loader_String(), array('static_cache' => new Twig_StaticCache_Memcached($this->memcache) ));
    }

    public function testWritingStaticCache()
    {
        $name = 'I can resist everything except temptation.';
        $this->twig->render($name);

        $output = $this->memcache->get($this->twig->getStaticCacheKey($name));
        if ($output === false)
            $output = '';

        $this->assertRegexp('/I can resist everything except temptation./', $output);
    }
}