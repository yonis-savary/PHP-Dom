# PHP-Dom (WIP)
HTML Parser for PHP

# How to install

```bash
composer config repositories.repo-name vcs https://github.com/YonisSavary/PHP-Dom
composer require yonissavary/php-dom
```

# How to use

```php
$document = Node::makeDocument(file_get_contents($path));

echo $document->innerHTML();

foreach ($document->iterate() as $child)
    echo "$child\n";

$links = $document->querySelectorAll("a[href]");
```