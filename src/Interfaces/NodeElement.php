<?php

namespace YonisSavary\PHPDom\Interfaces;

interface NodeElement extends HTMLElement
{
    public function id(): ?string;
    public function classlist(): array;

    public function setAttribute(string $key, mixed $value);
    public function getAttribute(string $key): mixed;
    public function hasAttribute(string $key): bool;
    public function attributes(): array;

    public function matches(string|array $selector): bool;
    public function querySelector(string $selector): ?NodeElement;
    public function querySelectorAll(string $selector): array;

    public function appendChild(HTMLElement $node);
    public function childNodes(): array;

    /** @return \Generator|Node[] */
    public function iterate();

    public function previousSiblings(): array;
    public function nextSiblings(): array;
    public function previousSibling(): ?NodeElement;
    public function nextSibling(): ?NodeElement;
    public function getSiblings(bool $skipSelf=true): array;
}