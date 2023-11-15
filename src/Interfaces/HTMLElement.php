<?php

namespace YonisSavary\PHPDom\Interfaces;

interface HTMLElement
{
    /**
     * Unique identifier is used to compare two element
     * Two Element containing the same information are not necessarily the same in the DOM
     */
    public function getUniqueIdentifier(): string;

    /**
     * @return string the tag/node name
     */
    public function nodeName(): string;

    /**
     * Return the inner text content without html tag
     */
    public function innerText(): string;

    /**
     * Get the inner html content of the element (content only)
     */
    public function innerHTML(int $depth=0): string;

    /**
     * Get the outer html content of the element (tag + content)
     */
    public function outerHTML(int $depth=0): string;

    /**
     * Replace the parent reference of the Element
     */
    public function setParent(HTMLElement &$parent);

    /**
     * Get the Element's parent element
     */
    public function parentNode(): ?NodeElement;
}