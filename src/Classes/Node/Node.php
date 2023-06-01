<?php

namespace YonisSavary\PHPDom\Classes\Node;

use YonisSavary\PHPDom\Classes\Interfaces\HTMLElement;
use YonisSavary\PHPDom\Classes\Interfaces\NodeElement;
use YonisSavary\PHPDom\Classes\Selector;
use YonisSavary\PHPDom\Classes\StringStream;

class Node implements NodeElement
{
    protected string $nodeName;
    protected array $attributes=[];
    protected array $childs = [];
    protected ?Node $parent = null;

    public function __construct(string $nodeName, array $attributes=[])
    {
        $this->nodeName = $nodeName;
        $this->attributes = $attributes ?? [];
    }

    public function nodeName(): string
    {
        return $this->nodeName;
    }

    public function parentNode(): ?NodeElement
    {
        return $this->parent;
    }

    public function setParent(HTMLElement &$parent)
    {
        $this->parent = $parent;
    }

    public function innerText(): string
    {
        return htmlentities($this->innerHTML());
    }

    public function __toString()
    {
        return $this->innerHTML();
    }

    public function innerHTML(int $depth=0): string
    {
        $nodeName = $this->nodeName();

        $attributes = $this->attributes;
        $attrStr = "";
        foreach($attributes as $key => $value)
            $attrStr .= " $key=\"$value\"";

        $tabs = str_repeat("\t", $depth);

        $cr = count($this->childs) > 1 ? "\n$tabs" : "";

        return
            "\n$tabs<$nodeName$attrStr>"
            .join("", array_map(fn($e)=>$e->innerHTML($depth+1), $this->childs)).
            "$cr</$nodeName>";
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

    public function appendChild(HTMLElement $node)
    {
        $this->childs[] = $node;
        $node->setParent($this);
    }

    public function childNodes(): array
    {
        return $this->childs;
    }

    public static function makeDocument(string $html)
    {
        $node = new Node(":root");
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
                $this->appendChild(new TextElement($text));
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

        foreach ($this->childs as $c)
        {
            if ($c instanceof Node)
                $c->setParent($this);
        }
    }


    public function getElementRegex()
    {
        $attrCopy = $this->attributes;
        ksort($attrCopy);
        $attributes = "";
        foreach ($attrCopy as $key=>$value)
            $attributes .= "[$key=$value]";

        return $this->nodeName() . $attributes;
    }

    public function getRegex()
    {
        $node = $this;
        $regex = [];

        do { array_unshift($regex, $node->getElementRegex());}
        while ($node = $node->parentNode());

        return join(">", $regex);
    }


    public function iterate()
    {
        yield $this;
        foreach ($this->childs as $child)
        {
            if ($child instanceof Node)
                yield from $child->iterate();
        }
    }

    public function querySelector(string $selector)
    {
        $selector = Selector::fromString($selector);
        $selectorPattern = $selector->getRegex();

        /** @var Node $node */
        foreach ($this->iterate() as $node)
        {
            if (preg_match($selectorPattern, $node->getRegex()))
                return $node;
        }
        return null;
    }

    public function querySelectorAll(string $selector): array
    {
        $nodes = [];

        $selector = Selector::fromString($selector);
        $selectorPattern = $selector->getRegex();

        /** @var Node $node */
        foreach ($this->iterate() as $node)
        {
            if (preg_match($selectorPattern, $node->getRegex()))
                $nodes[] = $node;
        }

        return $nodes;
    }
}