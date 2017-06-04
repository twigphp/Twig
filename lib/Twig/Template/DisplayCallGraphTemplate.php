<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Displays the call graph (templates names and block names) in a visual way directly in the generated HTML.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class Twig_Template_DisplayCallGraphTemplate extends Twig_Template
{
    protected $templateStart = '<div style="border: 1px solid rgba(240, 181, 24, 0.3); margin: 5px;"><span style="background-color: rgba(240, 181, 24, 0.3); color: black; font-family: monospace;">Template "%s"</span>';
    protected $templateEnd = '</div>';
    protected $blockStart = '<div style="border: 1px solid rgba(100, 189, 99, 0.2); margin: 5px;"><span style="background-color: rgba(100, 189, 99, 0.2); color: black; font-family: monospace;">Block "%s"</span>';
    protected $blockEnd = '</div>';

    /**
     * @var string[]
     */
    protected $templateBlackList = array();

    /**
     * @var string[]
     */
    protected $blockBlackList = array();

    /**
     * {@inheritdoc}
     */
    public function display(array $context, array $blocks = array())
    {
        if (!$this->isTemplateEnabled()) {
            parent::display($context, $blocks);

            return;
        }

        echo sprintf($this->templateStart, htmlspecialchars($this->getTemplateName()));
        parent::display($context, $blocks);
        echo $this->templateEnd;
    }

    /**
     * {@inheritdoc}
     */
    public function displayBlock($name, array $context, array $blocks = array(), $useBlocks = true)
    {
        if (!$this->isTemplateEnabled() || !$this->isBlockEnabled($name)) {
            parent::displayBlock($name, $context, $blocks, $useBlocks);

            return;
        }

        echo sprintf($this->blockStart, htmlspecialchars($name));
        parent::displayBlock($name, $context, $blocks, $useBlocks);
        echo $this->blockEnd;
    }

    /**
     * Checks if the call graph must be displayed for this template.
     *
     * @return bool
     */
    protected function isTemplateEnabled()
    {
        foreach ($this->templateBlackList as $prefix) {
            if (false !== strpos($this->getTemplateName(), $prefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the call graph must be displayed for the given block.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function isBlockEnabled($name)
    {
        foreach ($this->blockBlackList as $prefix) {
            if (false !== strpos($name, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
