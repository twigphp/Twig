<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface TwigCallableInterface
{
    public function getName(): string;

    /**
     * @return callable|array{class-string, string}|null
     */
    public function getCallable();

    public function getNodeClass(): string;

    public function needsCharset(): bool;

    public function needsEnvironment(): bool;

    public function needsContext(): bool;

    public function setArguments(array $arguments): void;

    public function getArguments(): array;

    public function isVariadic(): bool;

    public function isDeprecated(): bool;

    public function getDeprecatingPackage(): string;

    public function getDeprecatedVersion(): string;

    public function getAlternative(): ?string;
}
