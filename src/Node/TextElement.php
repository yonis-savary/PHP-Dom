<?php

namespace YonisSavary\PHPDom\Node;

use YonisSavary\PHPDom\Interfaces\HTMLElement;
use YonisSavary\PHPDom\Interfaces\NodeElement;

class TextElement implements HTMLElement
{
    protected HTMLElement $parent;
    protected string $uniqueIdentifier;

    public function __construct(public string $content)
    {
        $this->uniqueIdentifier = uniqid("node-", true);
    }

    public function getUniqueIdentifier(): string
    {
        return $this->uniqueIdentifier;
    }

    public function setParent(HTMLElement &$parent)
    {
        $this->parent = $parent;
    }

    public function parentNode(): ?NodeElement
    {
        return $this->parent;
    }

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

    public function outerHTML(int $depth=0): string
    {
        return $this->innerHTML($depth);
    }
}