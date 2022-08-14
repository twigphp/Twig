#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SymfonyDocsBuilder\BuildConfig;
use SymfonyDocsBuilder\DocBuilder;

(new Application('Twig docs Builder', '1.0'))
    ->register('build-docs')
    ->addOption('disable-cache', null, InputOption::VALUE_NONE, 'Use this option to force a full regeneration of all doc contents')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $io->text('Building all Twig docs...');

        $outputDir = __DIR__.'/output';
        $buildConfig = (new BuildConfig())
            ->setContentDir(__DIR__.'/..')
            ->setOutputDir($outputDir)
            ->setImagesDir(__DIR__.'/output/_images')
            ->setImagesPublicPrefix('_images')
            ->setTheme('rtd')
        ;

        $buildConfig->setExcludedPaths(['vendor/']);
        $buildConfig->disableJsonFileGeneration();
        $buildConfig->disableBuildCache();

        $result = (new DocBuilder())->build($buildConfig);

        if ($result->isSuccessful()) {
            // fix assets URLs to make them absolute (otherwise, they don't work in subdirectories)
            foreach (glob($outputDir.'/**/*.html') as $htmlFilePath) {
                $htmlContents = file_get_contents($htmlFilePath);
                file_put_contents($htmlFilePath, str_replace('href="assets/', 'href="/assets/', $htmlContents));
            }

            $io->success(sprintf('The Twig docs were successfully built at %s', realpath($outputDir)));
        } else {
            $io->error(sprintf("There were some errors while building the docs:\n\n%s\n", $result->getErrorTrace()));
            $io->newLine();
            $io->comment('Tip: you can add the -v, -vv or -vvv flags to this command to get debug information.');

            return 1;
        }

        return 0;
    })
    ->getApplication()
    ->setDefaultCommand('build-docs', true)
    ->run();
