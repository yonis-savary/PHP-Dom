<?php

namespace YonisSavary\PHPDom\Node;

use YonisSavary\PHPDom\Interfaces\HTMLElement;
use YonisSavary\PHPDom\Interfaces\NodeElement;

class TextElement implements HTMLElement
{
    protected HTMLElement $parent;

    public function setParent(HTMLElement &$parent)
    {
        $this->parent = $parent;
    }

    public function parentNode(): ?NodeElement
    {
        return $this->parent;
    }

    public function __construct(public string $content)
    {}

    public function nodeName(): string
    {
        return "#text";
    }

    public function innerText(): string
    {
        return $this->content;
    }

    public function innerHTML(int $depth=0): string
    {
        return str_repeat("\t", $depth) . $this->content;
    }
}