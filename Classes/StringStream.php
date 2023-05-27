<?php

namespace YonisSavary\PHPDom\Classes;

class StringStream
{
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

    public function read(string $s)
    {
        $res = substr($this->string, $this->position, $s);
        $this->position += $s;
        return $res;
    }

    public function getChar()
    {
        if ($this->eof())
            return false;

        return $this->read(1);
    }

    public function expect(string $string)
    {
        $position = $this->tell();

        $next = $this->read(strlen($string));
        $expect = $next === $string;

        $this->seek($position);
        return $expect;
    }

    public function readUntil(string $string, bool $inclusive=true)
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


    public function readNode(string $nodeName)
    {
        $html = "";
        $depth = 0;
        while (true)
        {
            if ($this->eof())
                return "";

            if ($depth === 0 && $this->expect("</$nodeName"))
                return $html;

            if ($this->expect("<$nodeName"))
                $depth++;
            else if ($this->expect("</$nodeName"))
                $depth--;

            $html .= $this->getChar();
        }
    }



    public static function parseAttributes(string $node)
    {
        $node = preg_replace("/\s/", " ", $node);
        $attributes = preg_replace("/^<[^ ]+|\\/?>$/", "", $node);

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