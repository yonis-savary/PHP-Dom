<?php

namespace YonisSavary\PHPDom\Node;

use YonisSavary\PHPDom\Interfaces\NodeElement;
use YonisSavary\PHPDom\StringStream;

class NodeUtils
{
    public static function parseAttributes(string $nodeTagHTML): array
    {
        $nodeTagHTML = preg_replace("/\s/", " ", $nodeTagHTML);

        // Raw string as "a='1' b='2' c='3'"
        $attributeString = preg_replace("/^<[^ ]+|\/?>$/", "", $nodeTagHTML);

        // Arraw as ["a='1'", "b='2'", "c='3'"]
        $attributesPairs = [];

        // Associative array as ["a"=>"1", "b"=>"2", "c"=>"3"]
        $attributes = [];

        $stream = new StringStream($attributeString);
        while (!$stream->eof())
            $attributesPairs[] = $stream->readUntil(" ");

        $attributesPairs = array_map(trim(...), $attributesPairs);
        $attributesPairs = array_filter($attributesPairs);

        foreach ($attributesPairs as $attr)
        {
            if (!str_contains($attr, "="))
            {
                $attributes[$attr] = true;
                continue;
            }

            list($name, $value) = explode("=", $attr, 2);

            $value = preg_replace("/^[\"']|[\"']$/", "", $value);
            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * This function's purpose it to break some HTML string into multiple HTMLElement objects
     * and insert them into `$node`
     *
     * @param NodeElement $node Container that will receive the new nodes
     * @param string $html HTML content
     */
    public static function parseHTML(NodeElement $node, string $html): void
    {
        $html = trim($html);
        $stream = new StringStream($html);

        while (!$stream->eof())
        {
            $text = $stream->readUntil("<", false);

            if (trim($text) !== "")
            {
                $node->appendChild(new TextElement($text));
                continue;
            }

            $newNodeHTML = $stream->readUntil(">");
            $newNodeName = preg_replace("/^<|(\s.*)?>$/s", "", $newNodeHTML);

            if (str_starts_with($newNodeHTML, "</"))
                continue;

            if (str_starts_with($newNodeHTML, "<!--"))
            {
                $comment = $newNodeHTML;
                if (!str_ends_with($comment, "-->"))
                    $comment .= $stream->readUntil("-->");

                $commentContent = preg_replace("/.*<!--|-->$/s", "", $comment);
                $node->appendChild(new DeclarationElement($commentContent, DeclarationElement::TYPE_COMMENT));
                continue;
            }

            if (str_starts_with($newNodeHTML, "<!"))
            {
                $declaration = substr($newNodeHTML, 2, strlen($newNodeHTML)-3);
                $node->appendChild(new DeclarationElement($declaration, DeclarationElement::TYPE_DECLARATION));
                continue;
            }

            $html = "";
            if (!in_array($newNodeName, Node::STANDALONE_ELEMENTS))
                $html = $stream->readNode($newNodeName);

            $child = new Node($newNodeName, NodeUtils::parseAttributes($newNodeHTML));

            if (in_array($newNodeName, Node::UNPARSED_ELEMENTS))
                $child->appendChild(new TextElement($html));
            else
                self::parseHTML($child, $html);

            $node->appendChild($child);
        }
    }
}