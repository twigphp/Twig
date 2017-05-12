<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Extension implements Twig_ExtensionInterface
{
    protected $signature = '';
    private $signatureNeedsUpdate = true;

    public function getTokenParsers()
    {
        return array();
    }

    public function getNodeVisitors()
    {
        return array();
    }

    public function getFilters()
    {
        return array();
    }

    public function getTests()
    {
        return array();
    }

    public function getFunctions()
    {
        return array();
    }

    public function getOperators()
    {
        return array();
    }

    final public function getSignature()
    {
        if ($this->signatureNeedsUpdate()) {
            $this->updateSignature();
        }

        return $this->signature;
    }

    final public function signatureNeedsUpdate()
    {
        return (bool) $this->signatureNeedsUpdate;
    }

    final protected function flagSignatureForUpdate()
    {
        $this->signatureNeedsUpdate = true;
    }

    protected function updateSignature()
    {
        $this->signature = '';
        $this->signatureNeedsUpdate = false;
    }
}
