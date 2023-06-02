<?php

namespace YonisSavary\PHPDom\Tests;

use PHPUnit\Framework\TestCase;
use YonisSavary\PHPDom\Node\Node;
use YonisSavary\PHPDom\Node\TextElement;

class NodeTest extends TestCase
{
    protected function getSampleLink(): Node
    {
        $link = new Node("a", ["href" => "https://github.com/"]);
        $link->appendChild(new TextElement("GitHub"));
        return $link;
    }

    public function test___construct()
    {
        $node = $this->getSampleLink();
        $this->assertInstanceOf(Node::class, $node);
    }

    public function test_nodeName()
    {
        $node = $this->getSampleLink();
        $this->assertEquals("a", $node->nodeName());
    }

    public function test_innerText()
    {
        $node = $this->getSampleLink();
        $this->assertEquals(
            "GitHub",
            $node->innerText()
        );
    }

    public function test_innerHTML()
    {
        $node = $this->getSampleLink();
        $this->assertEquals(
            "\n<a href=\"https://github.com/\">\n\tGitHub\n</a>",
            $node->innerHTML()
        );
    }

    public function test_parentNode()
    {
        $holder = new Node("section");
        $span = new Node("span");
        $holder->appendChild($span);

        $this->assertEquals($holder, $span->parentNode());
    }

    public function test_setParent()
    {
        $span = new Node("span");
        $holder = new Node("section");
        $span->setParent($holder);

        $this->assertEquals($holder, $span->parentNode());
    }


    public function test_setAttribute()
    {
        $node = $this->getSampleLink();

        $node->setAttribute("href", "A");
        $this->assertEquals("A", $node->getAttribute("href"));

        $node->setAttribute("href", "B");
        $this->assertEquals("B", $node->getAttribute("href"));
    }

    public function test_getAttribute()
    {
        $node = $this->getSampleLink();

        $node->setAttribute("href", "A");
        $this->assertEquals("A", $node->getAttribute("href"));

        $node->setAttribute("href", "B");
        $this->assertEquals("B", $node->getAttribute("href"));

        $this->assertNull($node->getAttribute("inexistant"));
    }

    public function test_hasAttribute()
    {
        $node = $this->getSampleLink();

        $this->assertFalse($node->hasAttribute("class"));
        $node->setAttribute("class", "A");
        $this->assertTrue($node->hasAttribute("class"));

        $this->assertFalse($node->hasAttribute("inexistant"));
    }

    public function test_listAttributes()
    {
        $node = $this->getSampleLink();

        $this->assertEquals(["href"], $node->listAttributes());
        $node->setAttribute("class", "A");
        $this->assertEquals(["href", "class"], $node->listAttributes());
    }

    public function test_appendChild()
    {
        $holder = new Node("section");
        $span = new Node("span");
        $holder->appendChild($span);

        $this->assertEquals(
            [$span],
            $holder->childNodes()
        );
    }

    public function childNodes()
    {
        $holder = new Node("section");
        $this->assertEquals([], $holder->childNodes());

        $span = new Node("span");
        $holder->appendChild($span);

        $this->assertEquals([$span], $holder->childNodes());
    }
    /*

    public function test_makeDocument()
    {
        $node = $this->getSampleLink();
    }

    public function test_parseHTML()
    {
        $node = $this->getSampleLink();
    }

    public function test_getElementRegex()
    {
        $node = $this->getSampleLink();
    }

    public function test_getRegex()
    {
        $node = $this->getSampleLink();
    }

    public function test_iterate()
    {
        $node = $this->getSampleLink();
    }
    */

    public function getSampleDocument()
    {
        return Node::fromFile(__DIR__ . "/Pages/phpdom-sample.html");
    }

    public function test_querySelector()
    {
        $document = $this->getSampleDocument();
        $this->assertInstanceOf(Node::class, $document->querySelector("section"), "Base section");
        $this->assertInstanceOf(Node::class, $document->querySelector("a"), "First link");
        $this->assertInstanceOf(Node::class, $document->querySelector("li > a"), "Link inside list");
        $this->assertInstanceOf(Node::class, $document->querySelector("section ul a"), "Full selector");
    }

    public function test_querySelectorAll()
    {
        $document = $this->getSampleDocument();

        $this->assertCount(3, $document->querySelectorAll("ul a"));
    }
}