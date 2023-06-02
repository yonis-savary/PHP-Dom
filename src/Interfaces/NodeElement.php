<?php

namespace YonisSavary\PHPDom\Interfaces;

interface NodeElement extends HTMLElement
{
    public function setAttribute(string $key, mixed $value);
    public function getAttribute(string $key): mixed;
    public function hasAttribute(string $key): bool;
    public function listAttributes(): array;

    public function querySelector(string $selector): ?NodeElement;
    public function querySelectorAll(string $selector): array;

    public function appendChild(HTMLElement $node);
    public function childNodes(): array;
}