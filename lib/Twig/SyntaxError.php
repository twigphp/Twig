<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Exception thrown when a syntax error occurs during lexing or parsing of a template.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_SyntaxError extends Twig_Error
{
    protected $lineno;
    protected $filename;
    protected $rawMessage;

    public function __construct($message, $lineno, $filename = null)
    {
        $this->lineno = $lineno;
        $this->filename = $filename;
        $this->rawMessage = $message;

        $this->updateRepr();

        parent::__construct($this->message, $lineno);
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
        $this->message = $this->rawMessage.' in '.($this->filename ? $this->filename : 'n/a').' at line '.$this->lineno;
    }
}
