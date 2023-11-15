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

    /**
     * Eats character until bumping into "</nodeName"
     * - Support nested element
     * - Return empty string if the document's end was reached without closing the tag
     */
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
}