<?php

namespace YonisSavary\PHPDom;

use PHPUnit\Framework\TestCase;

class StringStreamTest extends TestCase
{
    public function test_rewind()
    {
        $stream = new StringStream("abc");

        $this->assertEquals("a", $stream->getChar());
        $this->assertEquals("b", $stream->getChar());

        $stream->rewind();
        $this->assertEquals("a", $stream->getChar());
    }

    public function test_seek()
    {
        $stream = new StringStream("abc");

        $stream->seek(1);
        $this->assertEquals("b", $stream->getChar());
        $this->assertEquals("c", $stream->getChar());

        $stream->seek(0);
        $this->assertEquals("a", $stream->getChar());

        $stream->seek(3);
        $this->assertFalse($stream->getChar());
    }

    public function test_tell()
    {
        $stream = new StringStream("abc");

        $this->assertEquals(0, $stream->tell());
        $this->assertEquals("a", $stream->getChar());

        $this->assertEquals(1, $stream->tell());
        $this->assertEquals("b", $stream->getChar());

        $this->assertEquals(2, $stream->tell());
        $this->assertEquals("c", $stream->getChar());

        $this->assertEquals(3, $stream->tell());
        $this->assertFalse($stream->getChar());
    }

    public function test_eof()
    {
        $stream = new StringStream("abc");

        $stream->getChar();
        $this->assertFalse($stream->eof());
        $stream->getChar();
        $this->assertFalse($stream->eof());
        $stream->getChar();
        $this->assertTrue($stream->eof());

        $stream->rewind();
        $this->assertFalse($stream->eof());
    }

    public function test_read()
    {
        $stream = new StringStream("abc");

        $this->assertEquals("abc", $stream->read(3));
        $this->assertTrue($stream->eof());

        $stream->rewind();
        $this->assertEquals("a", $stream->read(1));
        $this->assertFalse($stream->eof());

        $this->assertEquals("bc", $stream->read(99));
        $this->assertTrue($stream->eof());
    }

    public function test_getChar()
    {
        $stream = new StringStream("abc");

        $this->assertEquals("a", $stream->getChar());
        $this->assertEquals("b", $stream->getChar());
        $this->assertEquals("c", $stream->getChar());
        $this->assertFalse($stream->getChar());

        $stream->rewind();
        $this->assertEquals("a", $stream->getChar());
    }

    public function test_expect()
    {
        $stream = new StringStream("abc");

        $this->assertFalse($stream->expect("bc"));

        $stream->getChar();
        $this->assertTrue($stream->expect("bc"));
    }

    public function test_readUntil()
    {
        $stream = new StringStream("abc-123-def");

        $stream->seek(0);
        $this->assertEquals("abc", $stream->readUntil("-", false));
        $stream->seek(0);
        $this->assertEquals("abc-", $stream->readUntil("-", true));

        $stream->seek(4);
        $this->assertEquals("123", $stream->readUntil("-", false));
        $stream->seek(4);
        $this->assertEquals("123-", $stream->readUntil("-", true));

        // EOF in both case
        $stream->seek(8);
        $this->assertEquals("def", $stream->readUntil("-", false));
        $stream->seek(8);
        $this->assertEquals("def", $stream->readUntil("-", true));


        // One of readUntil properties is that is can ignore "strings" (which are just quoted content)
        $stream = new StringStream("<math content='3>2'>");
        $this->assertEquals("<math content='3>2'>", $stream->readUntil(">"));

        $stream = new StringStream('<math content="3>2">');
        $this->assertEquals('<math content="3>2">', $stream->readUntil(">"));
    }

    public function test_readUntilChars()
    {

        $stream = new StringStream("111a222b333c");

        $this->assertEquals("111", $stream->readUntilChars("abc"));
        $stream->getChar();
        $this->assertEquals("222", $stream->readUntilChars("abc"));
        $stream->getChar();
        $this->assertEquals("333", $stream->readUntilChars("abc"));
        $stream->getChar();

    }

    public function test_eats()
    {
        $stream = new StringStream("1_____2_____3");
        //               Positions: 0000000000111
        //                          0123456789012

        $stream->eats("_");
        $this->assertEquals(0, $stream->tell());

        $stream->getChar();
        $stream->eats("_");
        $this->assertEquals(6, $stream->tell());

        $stream->getChar();
        $stream->eats("_");
        $this->assertEquals(12, $stream->tell());
    }

    public function test_readNode()
    {
        $stream = new StringStream(
            "<a><b><a></a></b></a>"
        );

        $stream->seek(3);
        $html = $stream->readNode("a");

        $this->assertEquals("<b><a></a></b>", $html);
    }
}