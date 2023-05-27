<?php

namespace YonisSavary\PHPDom\src\classes;

use YonisSavary\PHPDom\src\interfaces\NodeElement;

class Node implements NodeElement
{
    protected string $nodeName;
    protected array $attributes=[];
    protected array $childs = [];

    public function __construct(string $nodeName, array $attributes=[])
    {
        $this->nodeName = $nodeName;
        $this->attributes = $attributes ?? [];
    }

    public function nodeName(): string
    {
        return $this->nodeName;
    }

    public function innerText(): string
    {
        return htmlentities($this->innerHTML());
    }

    public function innerHTML(): string
    {
        return "";
    }

    public function setAttribute(string $key, mixed $value)
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function listAttributes(): array
    {
        return array_keys($this->attributes);
    }




    public static function makeDocument(string $html)
    {
        $node = new Node("Document");
        $node->parseHTML($html);
        return $node;
    }

    public function parseHTML(string $html)
    {
        $html = trim($html);
        $stream = new StringStream($html);

        while (!$stream->eof())
        {
            $text = $stream->readUntil("<", false);

            if (trim($text) !== "")
            {
                $this->childs[] = new TextElement($text);
                continue;
            }

            $node = $stream->readUntil(">");
            $nodeName = preg_replace("/^<|(\\s.*)?>$/s", "", $node);

            if (str_starts_with($node, "</"))
                continue;

            if (str_starts_with($node, "<!--"))
            {
                $comment = $stream->readUntil("-->");
                $this->childs[] = new DeclarationElement($comment, DeclarationElement::TYPE_COMMENT);
                continue;
            }
            if (str_starts_with($node, "<!"))
            {
                $comment = $stream->readUntil(">");
                $this->childs[] = new DeclarationElement($comment, DeclarationElement::TYPE_DECLARATION);
                continue;
            }

            $html = $stream->readNode($nodeName);
            $child = new Node($nodeName, StringStream::parseAttributes($node));
            $child->parseHTML($html);
            $this->childs[] = $child;
        }
    }
}