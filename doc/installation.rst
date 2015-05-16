Installation
============

You have multiple ways to install Twig.

Installing the Twig PHP package
-------------------------------

Installing via Composer (recommended)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Install `Composer`_ and run the following command to get the latest version:

.. code-block:: bash

    composer require twig/twig:~1.0

Installing from the tarball release
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Download the most recent tarball from the `download page`_
2. Verify the integrity of the tarball http://fabien.potencier.org/article/73/signing-project-releases
3. Unpack the tarball
4. Move the files somewhere in your project

Installing the development version
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: bash

    git clone git://github.com/twigphp/Twig.git

Installing the C extension
--------------------------

.. note::

    The C extension is **optional** but it brings some nice performance
    improvements. Note that the extension is not a replacement for the PHP
    code; it only implements a small part of the PHP code to improve the
    performance at runtime; you must still install the regular PHP code.

Twig comes with a C extension that enhances the performance of the Twig
runtime engine; install it like any other PHP extensions:

.. code-block:: bash

    cd ext/twig
    phpize
    ./configure
    make
    make install

For Windows:

1. Setup the build environment following the `PHP documentation`_
2. Put Twig's C extension source code into ``C:\php-sdk\phpdev\vcXX\x86\php-source-directory\ext\twig``
3. Use the ``configure --disable-all --enable-cli --enable-twig=shared`` command instead of step 14
4. ``nmake``
5. Copy the ``C:\php-sdk\phpdev\vcXX\x86\php-source-directory\Release_TS\php_twig.dll`` file to your PHP setup.

.. tip::

    For Windows ZendServer, ZTS is not enabled as mentioned in `Zend Server
    FAQ`_.

    You have to use ``configure --disable-all --disable-zts --enable-cli
    --enable-twig=shared`` to be able to build the twig C extension for
    ZendServer.

    The built DLL will be available in
    ``C:\\php-sdk\\phpdev\\vcXX\\x86\\php-source-directory\\Release``

Finally, enable the extension in your ``php.ini`` configuration file:

.. code-block:: ini

    extension=twig.so #For Unix systems
    extension=php_twig.dll #For Windows systems

And from now on, Twig will automatically compile your templates to take
advantage of the C extension. Note that this extension does not replace the
PHP code but only provides an optimized version of the
``Twig_Template::getAttribute()`` method.

.. _`download page`:     https://github.com/twigphp/Twig/tags
.. _`Composer`:          https://getcomposer.org/download/
.. _`PHP documentation`: https://wiki.php.net/internals/windows/stepbystepbuild
.. _`Zend Server FAQ`:   http://www.zend.com/en/products/server/faq#faqD6
