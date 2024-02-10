<?php

namespace Twig\Extra\TwigExtraBundle\Tests\Fixture;

use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\Test\NotificationAssertionsTrait;
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
        $config = [
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'php_errors' => [
                'log' => true,
            ],
        ];

        // the "handle_all_throwables" option was introduced in FrameworkBundle 6.2 (and so was the NotificationAssertionsTrait)
        if (trait_exists(NotificationAssertionsTrait::class)) {
            $config['handle_all_throwables'] = true;
        }

        $c->loadFromExtension('framework', $config);
        $c->loadFromExtension('twig', [
            'default_path' => __DIR__.'/views',
        ]);

        $c->register(StrikethroughExtension::class)->addTag('twig.markdown.league_extension');
    }

    protected function configureRoutes($routes): void
    {
    }
}
