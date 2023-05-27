<?php

namespace YonisSavary\PHPDom\src\interfaces;

interface NodeElement extends HTMLElement
{
    public function setAttribute(string $key, mixed $value);
    public function getAttribute(string $key): mixed;
    public function hasAttribute(string $key): bool;
    public function listAttributes(): array;
}