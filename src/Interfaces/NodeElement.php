<?php

namespace YonisSavary\PHPDom\Interfaces;

use YonisSavary\PHPDom\Selector;

interface NodeElement extends HTMLElement
{
    /**
     * Return the "id" attribute value
     */
    public function id(): ?string;

    /**
     * Return the "class" attribtue value (exploded by spaces)
     */
    public function classlist(): array;

    /**
     * Set a new value for an attribute
     * (If `$value` is null, the attribute should be unset)
     */
    public function setAttribute(string $name, mixed $value): void;

    /**
     * Get an attribute value or null if undefined
     */
    public function getAttribute(string $name): mixed;

    /**
     * @return bool Does the attribute is set ?
     */
    public function hasAttribute(string $name): bool;

    /**
     * Simply unset an attribute
     */
    public function removeAttribute(string $name): void;

    /**
     * Should return an associative array of the Node's attributes
     */
    public function attributes(): array;

    /**
     * Specific method to check if the Node matches a Selector object
     * (Used by `matches()` method)
     * @param Selector $selector Selector to check
     * @param bool $parentCanMatch Check if the Node's parent matches the Selector if not matched
     */
    public function matchSingleSelector(Selector $selector, bool $parentCanMatch=false): bool;

    /**
     * @param string|array<Selector> $selectors CSS Selector as a String, or the results of `Selector::fromString`
     * @return bool `true` if the Element fully matches the given selector
     */
    public function matches(string|array $selector): bool;

    /**
     * @return ?NodeElement The first child element that match the given selector or `null` is nothing was found
     */
    public function querySelector(string $selector): ?NodeElement;

    /**
     * @return array<NodeElement> Every child elements that matches the given selector
    */
    public function querySelectorAll(string $selector): array;

    /**
     * Append childs to the node content
     */
    public function appendChild(HTMLElement ...$nodes): void;

    /**
     * List every child of the Node (including non-Node HTML elements)
     */
    public function childNodes(): array;

    /**
     * Recursively Iterate between every NodeElement childs
     * - Generate itself
     * - Generate its child
     * @return \Generator|NodeElement[]
     */
    public function iterate();

    /**
     * @return array<NodeElement> Previous elements matching the same parent
     */
    public function previousSiblings(): array;

    /**
     * @return array<NodeElement> Next elements matching the same parent
     */
    public function nextSiblings(): array;

    /**
     * @return ?NodeElement The previous sibling from the parent node (or null)
     */
    public function previousSibling(): ?NodeElement;

    /**
     * @return ?NodeElement The next sibling from the parent node (or null)
     */
    public function nextSibling(): ?NodeElement;

    /**
     * @param bool $skipSelf If `true`, the current element is filtered out of the results
     * @return array<> Get every NodeElement siblings
     */
    public function getSiblings(bool $skipSelf=true): array;
}