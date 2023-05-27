<?php

namespace YonisSavary\PHPDom\src\classes;

use YonisSavary\PHPDom\src\interfaces\HTMLElement;

class DeclarationElement implements HTMLElement
{
    const TYPE_DECLARATION = 0;
    const TYPE_COMMENT = 1;

    public function __construct(
        public string $content,
        public int $type=self::TYPE_COMMENT
    )
    {}

    public function nodeName(): string
    {
        return "#declaration";
    }

    public function innerText(): string
    {
        return htmlentities($this->innerHTML());
    }

    public function innerHTML(): string
    {
        $content = $this->content;
        switch ($this->type)
        {
            case self::TYPE_COMMENT:
                return "<!-- $content -->";
                break;
        }

        return "<!$content>";
    }
}