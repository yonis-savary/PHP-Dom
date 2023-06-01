<?php

use YonisSavary\PHPDom\Classes\Node\Node;
use YonisSavary\PHPDom\Classes\Selector;

require_once "./bootstrap.php";



$path = "Tests/Pages/phpdom-sample.html";

$document = Node::makeDocument(file_get_contents($path));
foreach ($document->iterate() as $child)
    printf("%s\n", $child->getRegex());

$s = Selector::fromString("section[class^='flex'] > a[href]");
print_r($s->buildRegex());
echo "\n";