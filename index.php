<?php

use YonisSavary\PHPDom\Classes\Node\Node;

require_once "./bootstrap.php";


$path = "Tests/Pages/phpdom-sample.html";

$document = Node::makeDocument(file_get_contents($path));
print_r($document->querySelector("li > a"));
