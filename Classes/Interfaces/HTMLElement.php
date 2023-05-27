<?php

namespace YonisSavary\PHPDom\Classes\Interfaces;

interface HTMLElement
{
    public function nodeName(): string;

    public function innerText(): string;
    public function innerHTML(): string;

    public function setParent(HTMLElement &$parent);
    public function parentNode(): ?NodeElement;
}