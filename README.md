# 📜 PHP-Dom (WIP)

HTML Parser for PHP, with support for simple CSS Selectors !

## ✅ Supports

> [!WARNING]
> A lots of tests are to write yet, for this library is still in development

HTML:
- Document Parsing
- "to html" method (from objects to HTML document)
- DOM Manipulation

CSS:
- Type selector (+ universal)
- Class selector
- Id selector
- Attribute selector (Minus column & namespaces ones)
- Combinator selectors

## How to install

```bash
composer config repositories.repo-name vcs https://github.com/YonisSavary/PHP-Dom
composer require yonissavary/php-dom
```

## How to use

```php
$document = Node::makeDocument(file_get_contents($path));

echo $document->innerHTML();

foreach ($document->iterate() as $child)
    echo "$child\n";

$links = $document->querySelectorAll("a[href]");
```


## Interfaces

`HTMLElement`: nodes, texts, comments

```php
interface HTMLElement
{
    public function nodeName(): string;

    public function innerText(): string;
    public function innerHTML(): string;

    public function setParent(HTMLElement &$parent);
    public function parentNode(): ?NodeElement;
}
```


`NodeElement`: html nodes with attributes (not necessarily containers, can be input for example)

```php
interface NodeElement extends HTMLElement
{
    public function id(): ?string;
    public function classlist(): array;

    public function setAttribute(string $key, mixed $value);
    public function getAttribute(string $key): mixed;
    public function hasAttribute(string $key): bool;
    public function attributes(): array;

    public function matches(string|array $selector): bool;
    public function querySelector(string $selector): ?NodeElement;
    public function querySelectorAll(string $selector): array;

    public function appendChild(HTMLElement $node);
    public function childNodes(): array;

    /** @return \Generator|Node[] */
    public function iterate();

    public function previousSiblings(): array;
    public function nextSiblings(): array;
    public function previousSibling(): ?NodeElement;
    public function nextSibling(): ?NodeElement;
    public function getSiblings(bool $skipSelf=true): array;
}
```