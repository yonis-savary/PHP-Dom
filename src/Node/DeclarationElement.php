<?php

namespace YonisSavary\PHPDom\Node;

use YonisSavary\PHPDom\Interfaces\HTMLElement;
use YonisSavary\PHPDom\Interfaces\NodeElement;

class DeclarationElement implements HTMLElement
{
    const TYPE_DECLARATION = 0;
    const TYPE_COMMENT = 1;

    protected HTMLElement $parent;

    public function __construct(
        public string $content,
        public int $type=self::TYPE_COMMENT
    ){}

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
        return "#declaration";
    }

    public function innerText(): string
    {
        return "";
    }

    public function innerHTML(int $depth=0): string
    {
        $content = $this->content;
        $tabs = str_repeat("\t", $depth);
        switch ($this->type)
        {
            case self::TYPE_COMMENT:
                return "$tabs<!-- $content -->";
        }

        return "$tabs<!$content>";
    }
}