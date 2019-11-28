<?php

namespace Twig\Event;

class TwigEvents
{

    /**
     * The suffix for the event fired before rendering an event.
     * Extend this class and add custom event, also register the event annotation on the custom constants.
     * Example: const PRE_EVENT_INDEX = self::PRE_EVENT . 'index';
     */
    const PRE_RENDER = 'twig.pre_render:';
}
