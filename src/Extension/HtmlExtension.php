<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension;

use Symfony\Component\Mime\MimeTypes;
use Twig\TwigFilter;

final class HtmlExtension extends AbstractExtension
{
    private $mimeTypes;

    public function __construct(MimeTypes $mimeTypes = null)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('data_uri', [$this, 'dataUri']),
        ];
    }

    /**
     * Creates a data URI (RFC 2397).
     *
     * Length validation is not perfomed on purpose, validation should
     * be done before calling this filter.
     *
     * @return string The generated data URI
     */
    public function dataUri(string $data, string $mime = null, array $parameters = []): string
    {
        $repr = 'data:';

        if (null === $mime) {
            if (null === $this->mimeTypes) {
                if (!class_exists(MimeTypes::class)) {
                    throw new \LogicException('The "data_uri" function requires the symfony/mime package to be installed.');
                }
    
                $this->mimeTypes = new MimeTypes();
            }

            try {
                $tmp = tempnam(sys_get_temp_dir(), 'mime');
                file_put_contents($tmp, $data);
                
                if (null === $mime = $this->mimeTypes->guessMimeType($tmp)) {
                    $mime = 'text/plain';
                }
            } finally {
                @unlink($tmp);
            }
        }
        $repr .= $mime;

        foreach ($parameters as $key => $value) {
            $repr .= ';'.$key.'='.rawurlencode($value);
        }

        if (0 === strpos($mime, 'text/')) {
            $repr .= ','.rawurlencode($data);
        } else {
            $repr .= ';base64,'.base64_encode($data);
        }

        return $repr;
    }
}
