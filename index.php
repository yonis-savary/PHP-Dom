<?php

use YonisSavary\PHPDom\src\classes\Node;

const TO_LOAD = [
    "src/interfaces",
    "src/classes"
];

foreach (TO_LOAD as $dir)
{
    foreach (glob($dir."/*.php") as $file)
        require_once $file;
}


Node::makeDocument(file_get_contents("./samples/index.html"));