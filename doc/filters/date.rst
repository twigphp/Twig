``date``
========

.. versionadded:: 1.1
    The timezone support has been added in Twig 1.1.

.. versionadded:: 1.5
    The default date format support has been added in Twig 1.5.

The ``date`` filter formats a date to a given format:

.. code-block:: jinja

    {{ post.published_at|date("m/d/Y") }}

The ``date`` filter accepts any date format supported by `date`_ and
`DateTime`_ instances. For instance, to display the current date, filter the
word "now":

.. code-block:: jinja

    {{ "now"|date("m/d/Y") }}

To escape words and characters in the date format use ``\\`` in front of each character:

.. code-block:: jinja

    {{ post.published_at|date("F jS \\a\\t g:ia") }}

You can also specify a timezone:

.. code-block:: jinja

    {{ post.published_at|date("m/d/Y", "Europe/Paris") }}

If no format is provided, Twig will use the default one: ``F j, Y H:i``. This
default can be easily changed:

.. code-block:: php

    $twig = new Twig_Environment($loader);
    $twig->getExtension('core')->setDateFormat('d/m/Y');

.. _`date`:     http://www.php.net/date
.. _`DateTime`: http://www.php.net/manual/en/datetime.construct.php
