<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

/**
 * Exposes a template to userland.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface TemplateWrapperInterface
{
    public function render(array $context = []): string;

    public function display(array $context = []);

    public function hasBlock(string $name, array $context = []): bool;

    /**
     * @return string[] An array of defined template block names
     */
    public function getBlockNames(array $context = []): array;

    public function renderBlock(string $name, array $context = []): string;

    public function displayBlock(string $name, array $context = []);

    public function getSourceContext(): Source;

    public function getTemplateName(): string;

    /**
     * @internal
     *
     * @return Template
     */
    public function unwrap();
}
