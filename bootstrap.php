<?php

const TO_LOAD = [
    "Classes",
    "Classes/Interfaces",
    "Classes/Node"
];

foreach (TO_LOAD as $dir)
{
    foreach (glob("src/$dir/*.php") as $file)
        require_once $file;
}
