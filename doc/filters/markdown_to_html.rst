``markdown_to_html``
====================

The ``markdown_to_html`` filter converts a block of Markdown to HTML:

.. code-block:: twig

    {% apply markdown_to_html %}
    Title
    =====

    Hello!
    {% endapply %}

Note that you can indent the Markdown content as leading whitespaces will be
removed consistently before conversion:

.. code-block:: twig

    {% apply markdown_to_html %}
        Title
        =====

        Hello!
    {% endapply %}

You can also use the filter on an included file or a variable:

.. code-block:: twig

    {{ include('some_template.markdown.twig')|markdown_to_html }}

    {{ changelog|markdown_to_html }}

.. note::

    The ``markdown_to_html`` filter is part of the ``MarkdownExtension`` which
    is not installed by default. Install it first:

    .. code-block:: bash

        $ composer require twig/markdown-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: bash

        $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extra\Markdown\MarkdownExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new MarkdownExtension());

    If you are not using Symfony, you must also register the extension runtime::

        use Twig\Extra\Markdown\DefaultMarkdown;
        use Twig\Extra\Markdown\MarkdownRuntime;
        use Twig\RuntimeLoader\RuntimeLoaderInterface;

        $twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
            public function load($class) {
                if (MarkdownRuntime::class === $class) {
                    return new MarkdownRuntime(new DefaultMarkdown());
                }
            }
        });

    Afterwards you need to install a markdown library of your choice. Some of them are
    mentioned in the ``require-dev`` section of the ``twig/markdown-extra`` package.

.. note::

    If using Symfony (full-stack), ``twig/extra-bundle`` with ``league/commonmark`` as
    your Markdown library you can configure CommonMark extensions. Register the desired
    extension(s) as a service, then tag the service with
    ``twig.markdown.league_extension``.

Using a Custom Converter
------------------------

The ``markdown_to_html`` filter delegates the conversion to a class implementing
``Twig\Extra\Markdown\MarkdownInterface``. Several implementations are provided:
``LeagueMarkdown`` (``league/commonmark``), ``MichelfMarkdown``
(``michelf/php-markdown``), and ``ErusevMarkdown`` (``erusev/parsedown``). Each
accepts a pre-configured converter in its constructor, so you can tune the
underlying library or switch to another implementation (for instance
``ParsedownExtra``, which extends ``Parsedown``)::

    use Twig\Extra\Markdown\ErusevMarkdown;

    $parsedown = new \ParsedownExtra();
    $parsedown->setSafeMode(true);

    $markdown = new ErusevMarkdown($parsedown);

When using ``twig/extra-bundle``, register your converter as the
``twig.markdown.default`` service to make it the one used by the filter:

.. code-block:: yaml

    # config/services.yaml
    services:
        twig.markdown.default:
            class: Twig\Extra\Markdown\ErusevMarkdown
            arguments:
                - '@my_configured_parsedown'

If you use ``league/commonmark``, prefer the ``commonmark`` configuration of the
bundle (for example ``html_input: escape`` and ``allow_unsafe_links: false`` to
protect against XSS) instead of replacing the service.
