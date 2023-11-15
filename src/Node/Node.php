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

    /**
     * Unparsed elements are put into dom but
     * their content is stored as a text element
     */
    const UNPARSED_ELEMENTS = [
        "style",
        "script"
    ];

    protected string $nodeName;
    protected array $attributes=[];
    protected array $childNodes = [];
    protected ?Node $parent = null;

    protected ?string $id = null;
    protected array $classList = [];

    protected string $uniqueIdentifier;


    public function __construct(string $nodeName, array $attributes=[])
    {
        $this->nodeName = $nodeName;
        $this->attributes = $attributes ?? [];
        $this->refreshSpecialAttributes();

        $this->uniqueIdentifier = uniqid("node-", true);
    }

    /**
     * @inheritDoc
     */
    public function getUniqueIdentifier(): string
    {
        return $this->uniqueIdentifier;
    }

    /**
     * Refreshes special class attributes that are bound to some HTML attributes like the id and the classlist
     */
    protected function refreshSpecialAttributes()
    {
        $this->id = $this->getAttribute("id");
        $this->classList = array_filter(explode(" ", $this->getAttribute("class") ?? ""));
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

    public function __toString(): string
    {
        return $this->outerHTML();
    }

    public function outerHTML(int $depth=0): string
    {
        $node = $this->nodeName();

        $attributes = $this->attributes;
        $attrStr = "";
        foreach($attributes as $key => $value)
            $attrStr .= " $key=\"$value\"";

        $attrStr = trim($attrStr);
        $tabs = "\n" . str_repeat("\t", $depth);

        if (in_array($node, self::STANDALONE_ELEMENTS))
            return $tabs . "<$node $attrStr/>";

        return $tabs . "<$node $attrStr>\n". $this->innerHTML() . $tabs . "</$node>";
    }

    public function innerHTML(int $depth=0): string
    {
        return join("", array_map(fn($e)=> $e->outerHTML($depth+1), $this->childNodes));
    }

    public function innerText(): string
    {
        return join("\n", array_map(fn($e)=>$e->innerText(), $this->childNodes));
    }

    public function setAttribute(string $key, mixed $value): void
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

    public function appendChild(HTMLElement ...$nodes): void
    {
        foreach ($nodes as $node)
        {
            $this->childNodes[] = $node;
            $node->setParent($this);
        }
    }

    public function childNodes(): array
    {
        return $this->childNodes;
    }

    public static function fromString(string $html): Node
    {
        $node = new Node(":root");
        NodeUtils::parseHTML($node, $html);

        return $node;
    }

    public static function fromFile(string $path): Node
    {
        if (!is_file($path))
            throw new InvalidArgumentException("[$path] file not found !");

        return self::fromString(file_get_contents($path));
    }

    public function iterate()
    {
        yield $this;
        foreach ($this->childNodes as $child)
        {
            if ($child instanceof NodeElement)
                yield from $child->iterate();
        }
    }

    public function matchSingleSelector(Selector $selector, bool $parentCanMatch=false): bool
    {
        $checkers = $selector->getCheckers();

        $thisElementMatches = true;

        foreach ($checkers as $checkCondition)
        {
            if ($checkCondition($this) == false)
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

                case Selector::COMBINATOR_CHILD:
                    return $this->parentNode()->matchSingleSelector($parent, false);

                case Selector::COMBINATOR_NEXT_SIBLING:
                    if ($previous = $this->previousSibling())
                        return $previous->matchSingleSelector($parent);
                    return false;

                case Selector::COMBINATOR_SUBSEQUENT_SIBLINGS:
                    foreach ($this->previousSiblings() as $previous)
                    {
                        if ($previous->matchSingleSelector($parent))
                            return true;
                    }
                    return false;
                 default:
                    trigger_error("Unknown combinator type \"". $selector->combinatorType ."\"", E_USER_ERROR);
            }
        }

        return true;
    }

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

    public function previousSiblings(): array
    {
        $siblings = $this->getSiblings(false);

        for ($i=0; $i<count($siblings); $i++)
        {
            if ($siblings[$i]->getUniqueIdentifier() == $this->getUniqueIdentifier())
                break;
        }

        return array_slice($siblings, 0, $i);
    }

    public function nextSiblings(): array
    {
        $siblings = $this->getSiblings(false);

        for ($i=0; $i<count($siblings); $i++)
        {
            if ($siblings[$i]->getUniqueIdentifier() == $this->getUniqueIdentifier())
                break;
        }

        return array_slice($siblings, $i+1);
    }

    public function previousSibling(): ?NodeElement
    {
        $previousSiblings = $this->previousSiblings();
        return $previousSiblings[count($previousSiblings)-1] ?? null;
    }

    public function nextSibling(): ?NodeElement
    {
        return $this->nextSiblings()[0] ?? null;
    }

    public function getSiblings(bool $skipSelf=true): array
    {
        $siblings = $this->parentNode()->childNodes();
        $siblings = array_filter($siblings, fn($x) => $x instanceof NodeElement);

        if (!$skipSelf)
            return $siblings;

        return array_filter($siblings, fn($x) => $x != $this);
    }
}