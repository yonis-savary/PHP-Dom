<?php

use YonisSavary\PHPDom\src\classes\Node;

const TO_LOAD = [
    "Classes",
    "Classes/Interfaces",
    "Classes/Node"
];

foreach (TO_LOAD as $dir)
{
    foreach (glob($dir."/*.php") as $file)
        require_once $file;
}
