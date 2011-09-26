Introduction
============

This is the documentation for Twig, the flexible, fast, and secure template
engine for PHP.

If you have any exposure to other text-based template languages, such as
Smarty, Django, or Jinja, you should feel right at home with Twig. It's both
designer and developer friendly by sticking to PHP's principles and adding
functionality useful for templating environments.

The key-features are...

* *Fast*: Twig compiles templates down to plain optimized PHP code. The
  overhead compared to regular PHP code was reduced to the very minimum.

* *Secure*: Twig has a sandbox mode to evaluate untrusted template code. This
  allows Twig to be used as a template language for applications where users
  may modify the template design.

* *Flexible*: Twig is powered by a flexible lexer and parser. This allows the
  developer to define its own custom tags and filters, and create its own DSL.

Prerequisites
-------------

Twig needs at least **PHP 5.2.4** to run.

Installation
------------

You have multiple ways to install Twig. If you are unsure what to do, go with
the tarball.

From the tarball release
~~~~~~~~~~~~~~~~~~~~~~~~

1. Download the most recent tarball from the `download page`_
2. Unpack the tarball
3. Move the files somewhere in your project

Installing the development version
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Install Subversion or Git
2. For Subversion: ``svn co http://svn.twig-project.org/trunk/ twig``, for Git:
   ``git clone git://github.com/fabpot/Twig.git``

Installing the PEAR package
~~~~~~~~~~~~~~~~~~~~~~~~~~~

1. Install PEAR
2. ``pear channel-discover pear.twig-project.org``
3. ``pear install twig/Twig`` (or ``pear install twig/Twig-beta``)

Basic API Usage
---------------

This section gives you a brief introduction to the PHP API for Twig.

The first step to use Twig is to register its autoloader::

    require_once '/path/to/lib/Twig/Autoloader.php';
    Twig_Autoloader::register();

Replace the ``/path/to/lib/`` path with the path you used for Twig
installation.

.. note::

    Twig follows the PEAR convention names for its classes, which means you
    can easily integrate Twig classes loading in your own autoloader.

.. code-block:: php

    $loader = new Twig_Loader_String();
    $twig = new Twig_Environment($loader);

    echo $twig->render('Hello {{ name }}!', array('name' => 'Fabien'));

Twig uses a loader (``Twig_Loader_String``) to locate templates, and an
environment (``Twig_Environment``) to store the configuration.

The ``render()`` method loads the template passed as a first argument and
renders it with the variables passed as a second argument.

As templates are generally stored on the filesystem, Twig also comes with a
filesystem loader::

    $loader = new Twig_Loader_Filesystem('/path/to/templates');
    $twig = new Twig_Environment($loader, array(
      'cache' => '/path/to/compilation_cache',
    ));

    echo $twig->render('index.html', array('name' => 'Fabien'));

.. _`download page`: http://www.twig-project.org/installation
