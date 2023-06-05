<?php

namespace YonisSavary\PHPDom;

class Selector
{
    protected const S_PARSING_ELEMENT = 0;
    protected const S_PARSING_ATTRIBUTES = 1;
    protected const S_PARSING_PSEUDO_ELEMENT = 2;
    protected const S_PARSING_SPACES = 3;

    public array $parts = [];
    public string $regex = "";

    public static function fromString(string $selector): Selector
    {
        $stream = new StringStream($selector);

        $state = self::S_PARSING_ELEMENT;
        $parts = [];
        $token = "";

        $addPart = function($type, $newState, $replacement=null)
            use (&$token, &$parts, &$state)
        {
            $state = $newState;

            $value = $replacement ?? $token ;
            $parts[] = [$type, $value];
            $token = "";
        };

        while (!$stream->eof())
        {
            $char = $stream->getChar();

            if ($state === self::S_PARSING_SPACES)
            {
                if ($char !== " ")
                {
                    $token = "";
                    $stream->seek($stream->tell()-1);
                    $addPart("select-childs", self::S_PARSING_ELEMENT, true);
                }
                continue;
            }

            if ($state === self::S_PARSING_ELEMENT)
            {
                if ($char === ">")
                {
                    $addPart("select-childs", self::S_PARSING_SPACES, false);
                    continue;
                }
                if ($char === "[")
                {
                    $addPart("element", self::S_PARSING_ATTRIBUTES);
                    continue;
                }
                if ($char === ":")
                {
                    $addPart("element", self::S_PARSING_PSEUDO_ELEMENT);
                    continue;
                }
                if ($char === " ")
                {
                    $addPart("element", self::S_PARSING_SPACES);
                    continue;
                }

                $token .= $char;
            }
            else if ($state === self::S_PARSING_ATTRIBUTES)
            {
                if ($char === "]")
                {
                    $addPart("attribute", self::S_PARSING_ELEMENT);
                    continue;
                }
                $token .= $char;
            }
            else if ($state === self::S_PARSING_PSEUDO_ELEMENT)
            {
                if ($char === " ")
                {
                    $addPart("element", self::S_PARSING_SPACES);
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

    protected function attributeToRegex($attributeSelector)
    {
        $baseSelector = fn($str) => "(?=[^\\n>]*\\[$str\\])[^\\n>]*";

        $attributeSelector = str_replace('"', "", $attributeSelector);
        $attributeSelector = str_replace("'", "", $attributeSelector);

        if (strpos($attributeSelector, "^="))
            return $baseSelector(sprintf("%s=%s.+?", ...explode("^=", $attributeSelector)));
        else if (strpos($attributeSelector, "*="))
            return $baseSelector(sprintf("%s=.+?%s.+?", ...explode("*=", $attributeSelector)));
        else if (strpos($attributeSelector, "$="))
            return $baseSelector(sprintf("%s=.+?%s", ...explode("$=", $attributeSelector)));
        else if (strpos($attributeSelector, "="))
            return $baseSelector(sprintf("%s=%s", ...explode("=", $attributeSelector)));

        return $baseSelector($attributeSelector);
    }

    public function buildRegex(): string
    {
        $regexParts = [];
        foreach ($this->parts as $part)
        {
            switch ($part[0])
            {
                case "element":
                    $regexParts[] = ($part[1] == "*" ? "\\{.+?\\}": preg_quote("{".$part[1]."}")) . "[^>]*";
                    break;
                case "select-childs":
                    $regexParts[] = $part[1] == 1 ? "(.+)?>": ">";
                    break;
                case "attribute":
                    $value = $part[1];
                    $regexParts[] = $this->attributeToRegex($value);
                    break;
            }
        }
        return "/".join("", $regexParts)."$/";
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
            {
                if ($this->parts[$i-1][0] === "select-childs")
                    $indexesToIgnore[] = $i-1;
                if ($this->parts[$i+1][0] === "select-childs")
                    $indexesToIgnore[] = $i+1;
            }
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