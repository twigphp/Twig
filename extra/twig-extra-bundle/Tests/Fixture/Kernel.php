<?php

namespace Twig\Extra\TwigExtraBundle\Tests\Fixture;

use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigExtraBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
        ]);

        $c->loadFromExtension('twig', [
            'default_path' => __DIR__.'/views',
        ]);

        $c->register(StrikethroughExtension::class)->addTag('twig.markdown.league_extension');
    }

    protected function configureRoutes($routes): void
    {
    }
}
