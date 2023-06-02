<?php

const TO_LOAD = [
    "src",
    "src/Interfaces",
    "src/Node"
];

foreach (TO_LOAD as $dir)
{
    foreach (glob("$dir/*.php") as $file)
        require_once $file;
}
