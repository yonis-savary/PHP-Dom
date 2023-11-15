<?php

namespace YonisSavary\PHPDom\Tests;

use PHPUnit\Framework\TestCase;
use YonisSavary\PHPDom\Node\Node;

class NodeTest extends TestCase
{
    public static function getDocument(string $html): Node
    {
        return Node::fromString($html);
    }


    public function test_id()
    {
        $node = new Node("section", ["id" => "first"]);
        $this->assertEquals("first", $node->id());

        $node = new Node("section");
        $this->assertNull($node->id());
    }

    public function test_classlist()
    {
        $node = new Node("section");
        $this->assertEquals([], $node->classlist());

        $node = new Node("section", ["class"=> "button green icon"]);
        $this->assertEquals(["button", "green", "icon"], $node->classlist());
    }


    public function test_setAttribute()
    {
        $node = new Node("section");

        $node->setAttribute("class", "button");
        $this->assertEquals("button", $node->getAttribute("class"));

        $node->setAttribute("class", "link");
        $this->assertEquals("link", $node->getAttribute("class"));
    }

    public function test_getAttribute()
    {
        $node = new Node("section");

        $this->assertNull($node->getAttribute("class"));

        $node->setAttribute("class", "button");
        $this->assertEquals("button", $node->getAttribute("class"));
    }

    public function test_hasAttribute()
    {
        $node = new Node("section");

        $this->assertFalse($node->hasAttribute("class"));

        $node->setAttribute("class", "button");
        $this->assertTrue($node->hasAttribute("class"));
    }

    public function test_attributes()
    {
        $attributes = [
            "id" => "saveButton",
            "class" => "button green"
        ];

        $node = new Node("section", $attributes);

        $this->assertEquals($attributes, $node->attributes());
    }

    public function test_appendChild()
    {
        $parent = new Node("section");
        $child = new Node("button");

        $parent->appendChild($child);

        $this->assertEquals($parent, $child->parentNode());
        $this->assertCount(1, $parent->childNodes());
    }

    public function test_childNodes()
    {
        $parent = new Node("section");
        $child = new Node("button");
        $secondChild = new Node("button");

        $parent->appendChild($child);
        $this->assertCount(1, $parent->childNodes());

        $parent->appendChild($secondChild);
        $this->assertCount(2, $parent->childNodes());
    }


    /** @return \Generator|Node[] */
    public function test_iterate()
    {
        $doc = self::getDocument("
            <section>
                <section>
                    <label>Some input</label>
                    <input type='text'>
                </section>
                <section>
                    <button>Save</button>
                    <button>Cancel</button>
                </section>
            </section>
        ");

        $acc = [];

        foreach ($doc->iterate() as $child)
        {
            $nodeName = $child->nodeName();
            $acc[$nodeName] ??= 0;
            $acc[$nodeName]++;
        }

        $this->assertEquals([
            "section" => 3,
            "label" => 1,
            "input" => 1,
            "button" => 2,
            ":root" => 1
        ], $acc);
    }


    protected function getSiblingsDoc()
    {
        return $this->getDocument("
            <button id='one'>one</button>
            <button id='two'>two</button>
            <button id='three'>three</button>
            <button id='four'>four</button>
            <button id='five'>five</button>
            <button id='six'>six</button>
        ");
    }

    public function test_previousSiblings()
    {
        $doc = $this->getSiblingsDoc();
        $third = $doc->querySelector("#three");

        $this->assertCount(2, $third->previousSiblings());
    }

    public function test_nextSiblings()
    {
        $doc = $this->getSiblingsDoc();
        $third = $doc->querySelector("#three");

        $this->assertCount(3, $third->nextSiblings());
    }

    public function test_previousSibling()
    {
        $doc = $this->getSiblingsDoc();
        $third = $doc->querySelector("#three");

        $this->assertEquals("two", $third->previousSibling()->id());
    }

    public function test_nextSibling()
    {
        $doc = $this->getSiblingsDoc();
        $third = $doc->querySelector("#three");

        $this->assertEquals("four", $third->nextSibling()->id());
    }

    public function test_getSiblings()
    {
        $doc = $this->getSiblingsDoc();
        $third = $doc->querySelector("#three");

        $this->assertCount(5, $third->getSiblings());
        $this->assertCount(6, $third->getSiblings(false));
    }




    public function test_matches()
    {
        $strToNode = fn(string $node) => Node::fromString($node)->childNodes()[0];
        $assertMatches = fn(string $selector, string $node) => $this->assertTrue($strToNode($node)->matches($selector));
        $assertDoNotMatches = fn(string $selector, string $node) => $this->assertFalse($strToNode($node)->matches($selector));

        # Classlist tests
        $assertMatches(".button.green", "<button class='button green'>button</button>");
        $assertMatches(".green.button", "<button class='button green'>button</button>");
        $assertDoNotMatches(".button.green", "<button class='button'>button</button>");

        # ID tests
        $assertMatches(".button.green#saveBtn", "<button id='saveBtn' class='button green'>button</button>");
        $assertDoNotMatches(".button.green#saveBtn", "<button class='button green'>button</button>");

        # EQUAL [attr=value]
        $assertMatches("[type='text']", "<input type='text'>");
        $assertDoNotMatches("[type='text']", "<input type='number'>");

        # BEGIN WITH [attr^=value]
        $assertMatches("[class^='php']", "<code class='php'></code>");
        $assertDoNotMatches("[class^='sql']", "<code class='php'></code>");

        # END WITH [attr$=value]
        $assertMatches("[class$='php']", "<code class='language-php'></code>");
        $assertDoNotMatches("[class$='sql']", "<code class='language-php'></code>");

        # CONTAIN [attr*=value]
        $assertMatches("[class*='php']", "<code class='language php snippet'></code>");
        $assertDoNotMatches("[class*='sql']", "<code class='language php snippet'></code>");

        # VALUE IN WORDS [attr~=value]
        $assertMatches("[class~='php']", "<code class='language php snippet'></code>");
        $assertDoNotMatches("[class~='lang']", "<code class='language php snippet'></code>");

        # VALUE OR VALUE + '-' [attr|=value]
        $assertMatches("[class|='language']", "<code class='language'></code>");
        $assertMatches("[class|='language']", "<code class='language-php'></code>");
        $assertDoNotMatches("[class|='language']", "<code class='php language'></code>");
    }


    public function test_querySelector()
    {
        $tree = $this->getDocument("
            <nav>
                <h1 class='title'>App!</h1>
                <a href=''>Home<a>
            </nav>
            <body>
                <button id='myButton'></button>

                <div>
                    <b>Warning !</b>
                    <label>Hello</label>
                    <input type='text'>
                </div>

                <
            </body>
        ");

        # Test different combinator

        // Selector::COMBINATOR_CHILD;
        $this->assertNotNull($tree->querySelector("body > button"));
        $this->assertNotNull($tree->querySelector("body > div > b"));
        $this->assertNull($tree->querySelector("body > b"));

        // Selector::COMBINATOR_DESCENDANT;
        $this->assertNotNull($tree->querySelector("button"));
        $this->assertNotNull($tree->querySelector("body button"));
        $this->assertNotNull($tree->querySelector("body div b"));
        $this->assertNull($tree->querySelector("div button"));

        // Selector::COMBINATOR_NEXT_SIBLING;
        $this->assertNotNull($tree->querySelector("h1 + a"));
        $this->assertNull($tree->querySelector("h1 + button"));

        // Selector::COMBINATOR_SUBSEQUENT_SIBLINGS;
        $this->assertNotNull($tree->querySelector("h1 ~ a"));
        $this->assertNull($tree->querySelector("h1 ~ button"));
    }

    public function test_querySelectorAll()
    {
        # Basics

        $tree = $this->getDocument("
            <section id='first'>
                <a href=''></a>
                <a></a>
                <a href=''></a>
            </section>
            <div id='second'>
                <a></a>
                <a href=''></a>
                <a href=''></a>
            </div>
        ");

        $this->assertCount(6, $tree->querySelectorAll("a"));
        $this->assertCount(3, $tree->querySelectorAll("#first a"));
        $this->assertCount(3, $tree->querySelectorAll("#second a"));
        $this->assertCount(4, $tree->querySelectorAll("a[href]"));
        $this->assertCount(6, $tree->querySelectorAll("div a, section a"));

        # Combinators !

        $tree = $this->getDocument("
            <section id='first'>
                <h1>Title</h1>
                <a href=''></a>
                <a></a>
                <a href=''></a>
            </section>
            <div id='second'>
                <a></a>
                <section>
                    <h1 id='secondTitle'></h1>
                    <a href=''></a>
                    <a href=''></a>
                </section>
            </div>
        ");

        $this->assertCount(6, $tree->querySelectorAll("a"));
        $this->assertCount(3, $tree->querySelectorAll("div a"));
        $this->assertCount(2, $tree->querySelectorAll("h1 + a"));
        $this->assertCount(5, $tree->querySelectorAll("h1 ~ a"));
        $this->assertCount(2, $tree->querySelectorAll("#secondTitle ~ a"));

    }
}