<?php

namespace Twig\Event;

use Symfony\Contracts\EventDispatcher\Event;

class PreRenderEvent extends Event
{

    /**
     * @var array
     */
    private $context;

    /**
     * @param array $context
     */
    public function __construct(array $context = [])
    {
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     * @return PreRenderEvent
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
}
