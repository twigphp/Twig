<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig base exception.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Error extends Exception
{
    protected $lineno;
    protected $filename;
    protected $rawMessage;

    public function __construct($message, $lineno = -1, $filename = 'n/a')
    {
        $this->lineno = $lineno;
        $this->filename = $filename;
        $this->rawMessage = $message;

        $this->updateRepr();

        parent::__construct($this->message);
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;

        $this->updateRepr();
    }

    protected function updateRepr()
    {
        $this->message = $this->rawMessage;

        if ('n/a' != $this->filename) {
            $this->message .= sprintf(' in %s', $this->filename);
        }

        if ($this->lineno >= 0) {
            $this->message .= sprintf(' at line %d', $this->lineno);
        }
    }
}
