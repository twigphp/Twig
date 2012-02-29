``date``
========

.. versionadded:: 1.1
    The timezone support has been added in Twig 1.1.

.. versionadded:: 1.5
    The default date format support has been added in Twig 1.5.

.. versionadded:: 1.6.1
    The default timezone support has been added in Twig 1.6.1.

The ``date`` filter formats a date to a given format:

.. code-block:: jinja

    {{ post.published_at|date("m/d/Y") }}

The ``date`` filter accepts strings (it must be in a format supported by the
`date`_ function), `DateTime`_ instances, or `DateInterval`_ instances. For
instance, to display the current date, filter the word "now":

.. code-block:: jinja

    {{ "now"|date("m/d/Y") }}

To escape words and characters in the date format use ``\\`` in front of each character:

.. code-block:: jinja

    {{ post.published_at|date("F jS \\a\\t g:ia") }}

You can also specify a timezone:

.. code-block:: jinja

    {{ post.published_at|date("m/d/Y", "Europe/Paris") }}

If no format is provided, Twig will use the default one: ``F j, Y H:i``. This
default can be easily changed by calling the ``setDateFormat()`` method on the
``core`` extension instance. The first argument is the default format for
dates and the second one is the default format for date intervals:

.. code-block:: php

    $twig = new Twig_Environment($loader);
    $twig->getExtension('core')->setDateFormat('d/m/Y', '%d days');

The default timezone can also be set globally by calling ``setTimezone()``:

.. code-block:: php

    $twig = new Twig_Environment($loader);
    $twig->getExtension('core')->setTimezone('Europe/Paris');

.. _`date`:         http://www.php.net/date
.. _`DateTime`:     http://www.php.net/DateTime
.. _`DateInterval`: http://www.php.net/DateInterval
