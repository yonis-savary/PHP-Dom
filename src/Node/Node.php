<?php

namespace YonisSavary\PHPDom\Node;

use InvalidArgumentException;
use YonisSavary\PHPDom\Interfaces\HTMLElement;
use YonisSavary\PHPDom\Interfaces\NodeElement;
use YonisSavary\PHPDom\Selector;
use YonisSavary\PHPDom\StringStream;

class Node implements NodeElement
{
    const STANDALONE_ELEMENTS = [
        "input", "br"
    ];
    const UNPARSED_ELEMENTS = [
        "style", "script"
    ];

    protected string $nodeName;
    protected array $attributes=[];
    protected array $childNodes = [];
    protected ?Node $parent = null;

    protected ?string $id = null;
    protected array $classList = [];

    public function __construct(string $nodeName, array $attributes=[])
    {
        $this->nodeName = $nodeName;
        $this->attributes = $attributes ?? [];
        $this->refreshSpecialAttributes();
    }

    /**
     * Refreshes special class attributes that are bound to some HTML attributes like the id and the classlist
     */
    protected function refreshSpecialAttributes()
    {
        $this->id = $this->getAttribute("id");
        $this->classList = explode(" ", $this->getAttribute("class") ?? "");
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function classlist(): array
    {
        return $this->classList;
    }

    public function nodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * @return ?Node The parent Node or null if no parent set
     */
    public function parentNode(): ?Node
    {
        return $this->parent;
    }

    public function setParent(HTMLElement &$parent)
    {
        $this->parent = $parent;
    }

    public function innerText(): string
    {
        return join("\n", array_map(fn($e)=>$e->innerText(), $this->childNodes));
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

        if (!count($this->childNodes))
            return "\n$tabs<$nodeName$attrStr/>";

        return
            "\n$tabs<$nodeName$attrStr>\n"
            .join("", array_map(fn($e)=> $e->innerHTML($depth+1), $this->childNodes)).
            "\n$tabs</$nodeName>";
    }

    public function setAttribute(string $key, mixed $value)
    {
        $this->attributes[$key] = $value;
        $this->refreshSpecialAttributes();
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function removeAttribute(string $key): void
    {
        unset($this->attributes[$key]);
        $this->refreshSpecialAttributes();
    }

    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * @return array<string,string> Attribute list as associative array
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function appendChild(HTMLElement $node)
    {
        $this->childNodes[] = $node;
        $node->setParent($this);
    }

    public function childNodes(): array
    {
        return $this->childNodes;
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
            $nodeName = preg_replace("/^<|(\s.*)?>$/s", "", $node);

            if (str_starts_with($node, "</"))
                continue;

            if (str_starts_with($node, "<!--"))
            {
                $comment = $node;
                if (!str_ends_with($comment, "-->"))
                    $comment .= $stream->readUntil("-->");

                $commentContent = preg_replace("/.*<!--|-->$/s", "", $comment);
                $this->childNodes[] = new DeclarationElement($commentContent, DeclarationElement::TYPE_COMMENT);
                continue;
            }
            if (str_starts_with($node, "<!"))
            {
                $declaration = substr($node, 2, strlen($node)-3);
                $this->childNodes[] = new DeclarationElement($declaration, DeclarationElement::TYPE_DECLARATION);
                continue;
            }

            $html = "";
            if (!in_array($nodeName, self::STANDALONE_ELEMENTS))
                $html = $stream->readNode($nodeName);

            $child = new Node($nodeName, StringStream::parseAttributes($node));

            if (!in_array($nodeName, self::UNPARSED_ELEMENTS))
                $child->parseHTML($html);

            $this->appendChild($child);
        }
    }

    /** @return \Generator|Node[] */
    public function iterate()
    {
        yield $this;
        foreach ($this->childNodes as $child)
        {
            if ($child instanceof Node)
                yield from $child->iterate();
        }
    }

    protected function matchSingleSelector(Selector $selector, bool $parentCanMatch=false): bool
    {
        $checkers = $selector->getCheckers();

        $thisElementMatches = true;

        foreach ($checkers as $checkCondition)
        {
            if ($checkCondition($this) === false)
            {
                $thisElementMatches = false;
                break;
            }
        }

        if (!$thisElementMatches)
        {
            if ($parentCanMatch && $this->parentNode())
                return $this->parentNode()->matchSingleSelector($selector, $parentCanMatch);

            return false;
        }

        if ($parent = $selector->getParent())
        {
            if (!$this->parentNode())
                return false;

            switch ($selector->combinatorType)
            {
                case Selector::COMBINATOR_DESCENDANT:
                    return $this->parentNode()->matchSingleSelector($parent, true);
                    break;

                case Selector::COMBINATOR_CHILD:
                    return $this->parentNode()->matchSingleSelector($parent, false);
                    break;

                case Selector::COMBINATOR_NEXT_SIBLING:
                    return $this->previousSibling()->matchSingleSelector($parent);
                    break;

                case Selector::COMBINATOR_SUBSEQUENT_SIBLINGS:
                    foreach ($this->previousSiblings() as $previous)
                    {
                        if ($previous->matchSingleSelector($parent))
                            return true;
                    }
                    break;
                default:
                    trigger_error("Unknown combinator type \"". $selector->combinatorType ."\"", E_USER_ERROR);
            }
        }

        return true;
    }

    /**
     * @param string|array<Selector> $selectors CSS Selector as a String, or the results of `Selector::fromString`
     * @return bool `true` if the Element fully matches the given selector
     */
    public function matches(string|array $selectors): bool
    {
        if ($selectors instanceof Selector)
            $selectors = [$selectors];

        if (!is_array($selectors))
            $selectors = Selector::fromString($selectors);

        foreach ($selectors as $selector)
        {
            if ($this->matchSingleSelector($selector))
                return true;
        }
        return false;
    }

    /**
     * @return ?Node The first child element that match the given selector or `null` is nothing was found
     */
    public function querySelector(string $selector): ?Node
    {
        $selector = Selector::fromString($selector);

        /** @var Node $node */
        foreach ($this->iterate() as $node)
        {
            if ($node->matches($selector))
                return $node;
        }
        return null;
    }

    /**
     * @return array<Node> Every child elements that matches the given selector
    */
    public function querySelectorAll(string $selector): array
    {
        $nodes = [];
        $selector = Selector::fromString($selector);

        /** @var Node $node */
        foreach ($this->iterate() as $node)
        {
            if ($node->matches($selector))
                $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * @return array<Node> Previous elements matching the same parent
     */
    public function previousSiblings(): array
    {
        $siblings = $this->getSiblings(false);

        for ($i=0; $i<count($siblings); $i++)
        {
            if ($siblings[$i] == $this)
                break;
        }

        return array_slice($siblings, 0, $i-1);
    }

    /**
     * @return array<Node> Next elements matching the same parent
     */
    public function nextSiblings(): array
    {
        $siblings = $this->getSiblings(false);

        for ($i=0; $i<count($siblings); $i++)
        {
            if ($siblings[$i] == $this)
                break;
        }

        return array_slice($siblings, $i+1);
    }

    /**
     * @return ?Node The previous sibling from the parent node (or null)
     */
    public function previousSibling(): ?NodeElement
    {
        $previousSiblings = $this->previousSiblings();
        return $previousSiblings[count($previousSiblings)-1] ?? null;
    }

    /**
     * @return ?Node The next sibling from the parent node (or null)
     */
    public function nextSibling(): ?NodeElement
    {
        return $this->nextSiblings()[0] ?? null;
    }

    /**
     * @param bool $skipSelf If `true`, the current element is filtered out of the results
     * @return array Get every NodeElement siblings
     */
    public function getSiblings(bool $skipSelf=true): array
    {
        $siblings = $this->parentNode()->childNodes();
        $siblings = array_filter($siblings, fn($x) => $x instanceof NodeElement);

        if (!$skipSelf)
            return $siblings;

        return array_filter($siblings, fn($x) => $x != $this);
    }
}