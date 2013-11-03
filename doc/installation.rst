Installation
============

You have multiple ways to install Twig.

Installing the Twig PHP package
-------------------------------

Installing via Composer (recommended)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Install composer in your project:

.. code-block:: bash

    curl -s http://getcomposer.org/installer | php

2. Create a ``composer.json`` file in your project root:

.. code-block:: javascript

    {
        "require": {
            "twig/twig": "1.*"
        }
    }

3. Install via composer

.. code-block:: bash

    php composer.phar install

.. note::
    If you want to learn more about Composer, the ``composer.json`` file syntax
    and its usage, you can read the `online documentation`_.

Installing from the tarball release
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Download the most recent tarball from the `download page`_
2. Unpack the tarball
3. Move the files somewhere in your project

Installing the development version
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Install Git
2. ``git clone git://github.com/fabpot/Twig.git``

Installing the PEAR package
~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Install PEAR
2. ``pear channel-discover pear.twig-project.org``
3. ``pear install twig/Twig`` (or ``pear install twig/Twig-beta``)

Installing the C extension
--------------------------

.. versionadded:: 1.4
    The C extension was added in Twig 1.4.

Twig comes with a C extension that enhances the performance of the Twig
runtime engine.

You can install it via PEAR:

1. Install PEAR
2. ``pear channel-discover pear.twig-project.org``
3. ``pear install twig/CTwig`` (or ``pear install twig/CTwig-beta``)

Or manually like any other PHP extension:

.. code-block:: bash

    $ cd ext/twig
    $ phpize
    $ ./configure
    $ make
    $ make install

For Windows:

1. Setup the build environment following the `PHP documentation`_
2. Put twig C extension source code into ``C:\php-sdk\phpdev\vcXX\x86\php-source-directory\ext\twig``
3. Use the ``configure --disable-all --enable-cli --enable-twig=shared`` command instead of step 14
4. ``nmake``
5. Copy the ``C:\php-sdk\phpdev\vcXX\x86\php-source-directory\Release_TS\php_twig.dll`` file to your PHP setup.

.. tip::

    For Windows ZendServer, TS is not enabled as mentionned in `Zend Server
    FAQ`_.

    You have to use `configure --disable-all --disable-zts --enable-cli
    --enable-twig=shared` to be able to build the twig C extension for
    ZendServer.

    The built DLL will be available in
    C:\\php-sdk\\phpdev\\vcXX\\x86\\php-source-directory\\Release

Finally, enable the extension in your ``php.ini`` configuration file:

.. code-block:: ini

    extension=twig.so #For Unix systems
    extension=php_twig.dll #For Windows systems

And from now on, Twig will automatically compile your templates to take
advantage of the C extension. Note that this extension does not replace the
PHP code but only provides an optimized version of the
``Twig_Template::getAttribute()`` method.

.. _`download page`: https://github.com/fabpot/Twig/tags
.. _`online documentation`: http://getcomposer.org/doc
.. _`PHP documentation`: https://wiki.php.net/internals/windows/stepbystepbuild
.. _`Zend Server FAQ`: http://www.zend.com/en/products/server/faq#faqD6
