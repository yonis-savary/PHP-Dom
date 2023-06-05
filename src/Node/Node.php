<?php

namespace YonisSavary\PHPDom\Node;

use InvalidArgumentException;
use YonisSavary\PHPDom\Interfaces\HTMLElement;
use YonisSavary\PHPDom\Interfaces\NodeElement;
use YonisSavary\PHPDom\Selector;
use YonisSavary\PHPDom\StringStream;

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
        return join("\n", array_map(fn($e)=>$e->innerText(), $this->childs));
    }

    public function __toString(): string
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

        if (!count($this->childs))
            return "\n$tabs<$nodeName$attrStr/>";

        return
            "\n$tabs<$nodeName$attrStr>\n"
            .join("", array_map(fn($e)=> $e->innerHTML($depth+1), $this->childs)).
            "\n$tabs</$nodeName>";
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

    public static function fromString(string $html): Node
    {
        $node = new Node(":root");
        $node->parseHTML($html);
        return $node;
    }

    public static function fromFile(string $path): Node
    {
        if (!is_file($path))
            throw new InvalidArgumentException("[$path] file not found !");

        return self::fromString(file_get_contents($path));
    }

    public function parseHTML(string $html): void
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
                $comment = $node;
                if (!str_ends_with($comment, "-->"))
                    $comment .= $stream->readUntil("-->");

                $commentContent = preg_replace("/.*<!--|-->$/s", "", $comment);
                $this->childs[] = new DeclarationElement($commentContent, DeclarationElement::TYPE_COMMENT);
                continue;
            }
            if (str_starts_with($node, "<!"))
            {
                $declaration = substr($node, 2, strlen($node)-3);
                $this->childs[] = new DeclarationElement($declaration, DeclarationElement::TYPE_DECLARATION);
                continue;
            }

            $html = $stream->readNode($nodeName);
            $child = new Node($nodeName, StringStream::parseAttributes($node));

            if (!in_array($nodeName, ["style", "script"]))
                $child->parseHTML($html);

            $this->appendChild($child);
        }
    }

    public function getElementStringRepresentation(): string
    {
        $attributes = "";
        foreach ($this->attributes as $key=>$value)
            $attributes .= "[$key=$value]";

        return "{". $this->nodeName() ."}" . $attributes;
    }

    public function getTreeString(): string
    {
        $node = $this;
        $regex = [];

        do { array_unshift($regex, $node->getElementStringRepresentation());}
        while ($node = $node->parentNode());

        return join(">", $regex);
    }

    /** @return \Generator|Node[] */
    public function iterate()
    {
        yield $this;
        foreach ($this->childs as $child)
        {
            if ($child instanceof Node)
                yield from $child->iterate();
        }
    }

    public function querySelector(string $selector): ?Node
    {
        $selector = Selector::fromString($selector);
        $selectorPattern = $selector->getRegex();

        /** @var Node $node */
        foreach ($this->iterate() as $node)
        {
            if (preg_match($selectorPattern, $node->getTreeString()))
                return $node;
        }
        return null;
    }

    /** @return array<Node> */
    public function querySelectorAll(string $selector): array
    {
        $nodes = [];

        $selector = Selector::fromString($selector);
        $selectorPattern = $selector->getRegex();

        /** @var Node $node */
        foreach ($this->iterate() as $node)
        {
            if (preg_match($selectorPattern, $node->getTreeString()))
                $nodes[] = $node;
        }

        return $nodes;
    }
}