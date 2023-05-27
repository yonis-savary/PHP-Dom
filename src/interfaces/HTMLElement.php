<?php

namespace YonisSavary\PHPDom\src\interfaces;

interface HTMLElement
{
    public function nodeName(): string;

    public function innerText(): string;
    public function innerHTML(): string;
}