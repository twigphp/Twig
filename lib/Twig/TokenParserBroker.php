<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 * (c) 2010 Arnaud Le Blanc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Default implementation of a token parser broker.
 *
 * @package twig
 * @author  Arnaud Le Blanc <arnaud.lb@gmail.com>
 *
 * @deprecated
 */
class Twig_TokenParserBroker implements Twig_TokenParserBrokerInterface
{
    protected $parser;
    protected $parsers = array();
    protected $brokers = array();

    /**
     * Constructor.
     *
     * @param array|Traversable $parsers A Traversable of Twig_TokenParserInterface instances
     * @param array|Traversable $brokers A Traversable of Twig_TokenParserBrokerInterface instances
     */
    public function __construct($parsers = array(), $brokers = array())
    {
        foreach ($parsers as $parser) {
            if (!$parser instanceof Twig_TokenParserInterface) {
                throw new LogicException('$parsers must a an array of Twig_TokenParserInterface');
            }
            $this->parsers[$parser->getTag()] = $parser;
        }
        foreach ($brokers as $broker) {
            if (!$broker instanceof Twig_TokenParserBrokerInterface) {
                throw new LogicException('$brokers must a an array of Twig_TokenParserBrokerInterface');
            }
            $this->brokers[] = $broker;
        }
    }

    /**
     * Adds a TokenParser.
     *
     * @param Twig_TokenParserInterface $parser A Twig_TokenParserInterface instance
     */
    public function addTokenParser(Twig_TokenParserInterface $parser)
    {
        $this->parsers[$parser->getTag()] = $parser;
    }

    /**
     * Removes a TokenParser.
     *
     * @param Twig_TokenParserInterface $parser A Twig_TokenParserInterface instance
     */
    public function removeTokenParser(Twig_TokenParserInterface $parser)
    {
        $name = $parser->getTag();
        if (isset($this->parsers[$name]) && $parser === $this->parsers[$name]) {
            unset($this->parsers[$name]);
        }
    }

    /**
     * Adds a TokenParserBroker.
     *
     * @param Twig_TokenParserBroker $broker A Twig_TokenParserBroker instance
     */
    public function addTokenParserBroker(Twig_TokenParserBroker $broker)
    {
        $this->brokers[] = $broker;
    }

    /**
     * Removes a TokenParserBroker.
     *
     * @param Twig_TokenParserBroker $broker A Twig_TokenParserBroker instance
     */
    public function removeTokenParserBroker(Twig_TokenParserBroker $broker)
    {
        if (false !== $pos = array_search($broker, $this->brokers)) {
            unset($this->brokers[$pos]);
        }
    }

    /**
     * Gets a suitable TokenParser for a tag.
     *
     * First looks in parsers, then in brokers.
     *
     * @param string $tag A tag name
     *
     * @return null|Twig_TokenParserInterface A Twig_TokenParserInterface or null if no suitable TokenParser was found
     */
    public function getTokenParser($tag)
    {
        if (isset($this->parsers[$tag])) {
            return $this->parsers[$tag];
        }
        $broker = end($this->brokers);
        while (false !== $broker) {
            $parser = $broker->getTokenParser($tag);
            if (null !== $parser) {
                return $parser;
            }
            $broker = prev($this->brokers);
        }

        return null;
    }

    public function getParsers()
    {
        return $this->parsers;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function setParser(Twig_ParserInterface $parser)
    {
        $this->parser = $parser;
        foreach ($this->parsers as $tokenParser) {
            $tokenParser->setParser($parser);
        }
        foreach ($this->brokers as $broker) {
            $broker->setParser($parser);
        }
    }
}
