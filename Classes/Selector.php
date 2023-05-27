<?php

namespace YonisSavary\PHPDom\Classes;

class Selector
{
    const S_EXPLORING_ELEMENT = 0;
    const S_EXPLORING_ATTRIBUTES = 1;
    const S_EXPLORING_PSEUDO_ELEMENT = 2;
    const S_EXPLORING_SPACES = 3;

    public array $parts = [];
    public string $regex = "";


    public static function fromString(string $selector)
    {
        $stream = new StringStream($selector);

        $mode = self::S_EXPLORING_ELEMENT;


        $parts = [];
        $token = "";

        $addPart = function($type, $newMode, $replacement=null)
            use (&$token, &$parts, &$mode)
        {
            $mode = $newMode;

            $value = $replacement ?? $token ;
            $parts[] = [$type, $value];
            $token = "";
        };

        while (!$stream->eof())
        {
            $char = $stream->getChar();

            if ($mode === self::S_EXPLORING_SPACES)
            {
                if ($char !== " ")
                {
                    $token = "";
                    $stream->seek($stream->tell()-1);
                    $addPart("select-childs", self::S_EXPLORING_ELEMENT, true);
                }
                continue;
            }

            if ($mode === self::S_EXPLORING_ELEMENT)
            {
                if ($char === ">")
                {
                    $addPart("select-childs", self::S_EXPLORING_SPACES, false);
                    continue;
                }
                if ($char === "[")
                {
                    $addPart("element", self::S_EXPLORING_ATTRIBUTES);
                    continue;
                }
                if ($char === ":")
                {
                    $addPart("element", self::S_EXPLORING_PSEUDO_ELEMENT);
                    continue;
                }
                if ($char === " ")
                {
                    $addPart("element", self::S_EXPLORING_SPACES);
                    continue;
                }

                $token .= $char;
            }
            else if ($mode === self::S_EXPLORING_ATTRIBUTES)
            {
                if ($char === "]")
                {
                    $addPart("attribute", self::S_EXPLORING_ELEMENT);
                    continue;
                }
                $token .= $char;
            }
            else if ($mode === self::S_EXPLORING_PSEUDO_ELEMENT)
            {
                if ($char === " ")
                {
                    $addPart("element", self::S_EXPLORING_SPACES);
                    continue;
                }
            }
        }

        if (strlen($token))
            $addPart("element", 0);


        return new Selector($parts);
    }


    public function __toString()
    {
        $string = "";

        foreach ($this->parts as $part)
        {
            switch ($part[0])
            {
                case "element":
                    $string .= $part[1];
                    break;
                case "select-childs":
                    $string .= $part[1] == 1 ? " > " : " ";
                    break;
                case "attribute":
                    $string .= "[$part[1]]";
                    break;
            }
        }

        return $string;
    }


    public function __construct(array $parts)
    {
        $this->parts = $parts;
        $this->cleanParts();
    }


    protected function buildRegex()
    {
        $regexParts = [];
        foreach ($this->parts as $part)
        {
            switch ($part[0])
            {
                case "element":
                    $regexParts[] = $part[1] == "*" ? ".+": preg_quote("($part[1])");
                    break;
                case "select-childs":
                    $regexParts[] = $part[1] == 1 ? "(.+ )?>": ">";
                    break;
                case "attribute":
                    $value = $part[1];
                    $value = str_replace('"', "'", $value);
                    $value = (strpos($value, "=") === false) ?
                        "$value=.+?":
                        preg_quote($value);
                    $regexParts[] = "(\\[.+?\\])? \\[$value\\]";
                    break;
            }
        }
        return "/".join(" ", $regexParts)."/";
    }

    public function cleanParts()
    {
        $indexesToIgnore = [];
        for ($i=0; $i<count($this->parts); $i++)
        {
            $part = $this->parts[$i];

            if ($part[0] == "element" && (!$part[1]))
                array_push($indexesToIgnore, $i);

            if ($part[0] == "select-childs" && $part[1] == false)
                array_push($indexesToIgnore, $i-1, $i+1);
        }

        $cleaned = [];
        for ($i=0; $i<count($this->parts); $i++)
        {
            if (in_array($i, $indexesToIgnore))
                continue;
            $cleaned[] = $this->parts[$i];
        }

        $this->parts = $cleaned;
        $this->regex = $this->buildRegex();

    }



    public function getParts()
    {
        return $this->parts;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }
}