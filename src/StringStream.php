<?php

namespace YonisSavary\PHPDom;

class StringStream
{
    const WHITESPACE = [" ", "\t", "\n"];

    public string $string;
    public int $size;
    public int $position = 0;

    public function __construct(string $string)
    {
        $this->string = $string;
        $this->position = 0;
        $this->size = strlen($string);
    }


    public function rewind() { $this->position = 0; }
    public function tell() { return $this->position; }
    public function seek(int $p) { $this->position = $p; }
    public function eof() { return $this->position >= $this->size; }

    public function read(int $s): string
    {
        $res = substr($this->string, $this->position, $s);
        $this->position += $s;
        return $res;
    }

    public function getChar(): string|false
    {
        if ($this->eof())
            return false;

        return $this->read(1);
    }

    public function expect(string $string): bool
    {
        return substr(
                    $this->string,
                    $this->position,
                    strlen($string)
        ) === $string;
    }

    public function readUntil(string $string, bool $inclusive=true): string
    {
        $result = "";

        while ((!$this->expect($string)) && (!$this->eof()))
        {
            $char = $this->getChar();

            if (in_array($char, ["'", '"']))
                $char .= $this->readUntil($char);

            $result .= $char;
        }

        if ($inclusive && (!$this->eof()))
            $result .= $this->read(strlen($string));

        return $result;
    }

    /**
     * Reads until we reach one of the given `$chars`
     * (which won't be included in the results, the pointer is set before the final character)
     */
    public function readUntilChars(array $chars): string
    {
        $text = "";
        do
        {
            $char = $this->getChar();
            $text .= $char;
        }
        while ((!in_array($char, $chars)) && (!$this->eof()));

        if ($this->eof())
            return $text;

        $text = substr($text, 0, strlen($text)-1);
        $this->seek($this->tell()-1);

        return $text;
    }

    /**
     * Eats characters while they are included in `$chars`
     */
    public function eats(array $chars): void
    {
        while (in_array($this->getChar(), $chars))
            continue;

        $this->seek($this->tell()-1);
    }

    public function readNode(string $nodeName): string
    {
        $html = "";
        $depth = 0;

        $initialPosition = $this->tell();
        while (true)
        {
            if ($this->eof())
            {
                $this->seek($initialPosition);
                return "";
            }

            if ($depth === 0 && $this->expect("</$nodeName"))
            {
                $this->readUntil(">");
                return $html;
            }

            if ($this->expect("<$nodeName"))
                $depth++;
            else if ($this->expect("</$nodeName"))
                $depth--;

            $html .= $this->getChar();
        }
    }



    public static function parseAttributes(string $node): array
    {
        $node = preg_replace("/\s/", " ", $node);
        $attributes = preg_replace("/^<[^ ]+|\/?>$/", "", $node);

        $attrArr = [];
        $stream = new self($attributes);
        while (!$stream->eof())
            $attrArr[] = $stream->readUntil(" ");

        $cleanAttr = [];
        $attrArr = array_filter($attrArr);
        foreach ($attrArr as $attr)
        {
            if ($attr === " ")
                continue;

            $attr = trim($attr);
            if (strpos($attr, "=") !== false)
            {
                list($k, $v) = explode("=", $attr, 2);

                $v = substr($v, 1, strlen($v)-2);
                $cleanAttr[$k] = $v;
            }
            else
            {
                $cleanAttr[$attr] = true;
            }
        }

        return $cleanAttr;
    }
}