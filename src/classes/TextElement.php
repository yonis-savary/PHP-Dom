<?php

namespace YonisSavary\PHPDom\src\classes;

use YonisSavary\PHPDom\src\interfaces\HTMLElement;

class TextElement implements HTMLElement
{
    public function __construct(public string $content)
    {}

    public function nodeName(): string
    {
        return "#text";
    }

    public function innerText(): string
    {
        return htmlentities($this->content);
    }

    public function innerHTML(): string
    {
        return $this->content;
    }
}