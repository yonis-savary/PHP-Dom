<?php

namespace YonisSavary\PHPDom;

use YonisSavary\PHPDom\Node\Node;
use YonisSavary\PHPDom\StringStream;

/**
 * To process CSS Selectors, we break them into pieces, example
 * ```css
 * section.hero > h1.giant
 * ```
 * Will be broken into two `Selector` instances one for `section.hero` and one for `h1.giant`
 * The first selector will be considered as a parent and the second as a child of the first one
 *
 * The selector class contains information about one selector and a link to its parent selector
 */
class Selector
{
    const COMBINATOR_DESCENDANT          = " ";
    const COMBINATOR_CHILD               = ">";
    const COMBINATOR_NEXT_SIBLING        = "+";
    const COMBINATOR_SUBSEQUENT_SIBLINGS = "~";

    protected ?array $checkers = null;

    /**
     * Break a Selector string into multiple `Selector` instances
     * Always return an array of instances even if the given selector contains only one selector
     */
    public static function fromString(string $selector): array
    {
        if (str_contains($selector, ","))
            return array_merge(...array_map(self::fromString(...), explode(",", $selector)));

        $stream = new StringStream($selector);

        $parenthesisStack = [
            "parenthesis" => 0,
            "brackets" => 0,
            "curlyBrackets" => 0
        ];

        $isInStack = fn():bool => array_sum(array_values($parenthesisStack)) > 0;
        $isOutOfStack = fn():bool => (!($isInStack()));

        $parts = [];
        $text = "";
        $nextCombinatorType = self::COMBINATOR_DESCENDANT;

        $resetStates = function(string $nextType=self::COMBINATOR_DESCENDANT) use (&$text, &$nextCombinatorType) {
            $text = "";
            $nextCombinatorType = $nextType;
        };

        while (!$stream->eof())
        {
            $char = $stream->getChar();
            $text .= $char;

            switch ($char)
            {
                case "{": $parenthesisStack["curlyBrackets"]++; break;
                case "}": $parenthesisStack["curlyBrackets"]--; break;
                case "[": $parenthesisStack["brackets"]++; break;
                case "]": $parenthesisStack["brackets"]--; break;
                case "(": $parenthesisStack["parenthesis"]++; break;
                case ")": $parenthesisStack["parenthesis"]--; break;
            }

            if (trim($text) !== "" && $char === " " && $isOutOfStack())
            {
                $parts[] = [trim($text), $nextCombinatorType];
                $stream->eats(StringStream::WHITESPACE);
                $resetStates();
            }

            if ($char === ">" && $isOutOfStack())
                $resetStates(self::COMBINATOR_CHILD);
            if ($char === "+" && $isOutOfStack())
                $resetStates(self::COMBINATOR_NEXT_SIBLING);
            if ($char === "~" && $isOutOfStack())
                $resetStates(self::COMBINATOR_SUBSEQUENT_SIBLINGS);
        }

        if ($text)
            $parts[] = [trim($text), $nextCombinatorType];

        $last = null;
        foreach ($parts as [$selector, $combinatorType])
            $last = new self($selector, $combinatorType, $last);

        return [$last];
    }


    protected function __construct(
        public readonly string $elementSelector,
        public readonly string $combinatorType=self::COMBINATOR_DESCENDANT,
        public readonly ?Selector $parent=null
    ){}

    public function getParent(): ?Selector
    {
        return $this->parent;
    }

    protected function getAttributeConditionValue(string $attributeValueExpression): array
    {
        $expression = $attributeValueExpression;
        $sensitive = true;

        if (str_ends_with($expression, "i"))
        {
            $sensitive = false;
            $expression = preg_replace("/ *i$/i", "", $expression);
        }
        $expression = preg_replace("/ *s$/i", "", $expression);
        $expression = preg_replace("^[\"']|[\"']$", "", $expression);

        return [$expression, $sensitive];
    }

    protected function getAttributeChecker(string $expression): callable
    {
        $expression = substr($expression, 1, strlen($expression)-1);

        $getAttributeValueSensitive = function($splitChar) use ($expression) {
            list($attr, $value) = explode($splitChar, $expression, 2);
            list($value, $sensitive) = $this->getAttributeChecker($value);

            return [$attr, $value, $sensitive];
        };

        // VALUE IN WORDS [attr~=value]
        if (str_contains($expression, "~="))
        {
            list($attr, $value, $sensitive) = $getAttributeValueSensitive("~=");

            return $sensitive ?
                fn(Node $node) => in_array($value, explode(" ", $node->getAttribute($attr))):
                fn(Node $node) => in_array(strtolower($value), explode(" ", strtolower($node->getAttribute($attr))))
            ;
        }

        // VALUE OR VALUE + '-' [attr|=value]
        if (str_contains($expression, "|="))
        {
            list($attr, $value, $sensitive) = $getAttributeValueSensitive("|=");

            return fn(Node $node) => preg_match(
                "/^".preg_quote($value)."(-|$)/" . ($sensitive ? "": "i"),
                $node->getAttribute($attr)
            );
        }

        // BEGIN WITH [attr^=value]
        if (str_contains($expression, "^="))
        {
            list($attr, $value, $sensitive) = $getAttributeValueSensitive("^=");

            return $sensitive ?
                fn(Node $node) => str_starts_with($node->getAttribute($attr), $value):
                fn(Node $node) => str_starts_with(strtolower($node->getAttribute($attr)), strtolower($value));
        }

        // END WITH [attr$=value]
        if (str_contains($expression, "$="))
        {
            list($attr, $value, $sensitive) = $getAttributeValueSensitive("$=");

            return $sensitive ?
                fn(Node $node) => str_ends_with($node->getAttribute($attr), $value):
                fn(Node $node) => str_ends_with(strtolower($node->getAttribute($attr)), strtolower($value));
        }

        // CONTAIN [attr*=value]
        if (str_contains($expression, "*="))
        {
            list($attr, $value, $sensitive) = $getAttributeValueSensitive("*=");

            return $sensitive ?
                fn(Node $node) => str_contains($node->getAttribute($attr), $value):
                fn(Node $node) => str_contains(strtolower($node->getAttribute($attr)), strtolower($value));
        }

        // EQUAL [attr=value]
        if (str_contains($expression, "="))
        {
            list($attr, $value, $sensitive) = $getAttributeValueSensitive("=");

            return $sensitive ?
                fn(Node $node) => $node->getAttribute($attr) === $value:
                fn(Node $node) => strtolower($node->getAttribute($attr)) === strtolower($value);
        }

        return fn(Node $node) => $node->hasAttribute($expression);
    }


    public function getCheckers(): array
    {
        if ($this->checkers)
            return $this->checkers;

        $checkers = [];

        $stream = new StringStream($this->elementSelector);

        $specialCharacters = [".", "#", "[", ":"];

        while (!$stream->eof())
        {
            $char = $stream->getChar();
            $expression = $char . $stream->readUntilChars($specialCharacters);

            if (!in_array($char, $specialCharacters))
            {
                $checkers[] = $expression === "*" ?
                    fn() => true:
                    fn(Node $node) => $node->nodeName() === $expression;

                continue;
            }

            switch ($char)
            {
                case ".":
                    $checkers[] = fn(Node $node) => in_array(substr($expression, 1), $node->classlist());
                    break;
                case "#":
                    $checkers[] = fn(Node $node) => $node->id() === substr($expression, 1);
                    break;
                case "[":
                    $checkers[] = $this->getAttributeChecker($expression);
                    break;
                case ":":
                    trigger_error("pseudo-classes and pseudo-element are not supported yet", E_USER_WARNING);
                    break;
            }
        }

        $this->checkers = $checkers;
        return $this->checkers;
    }
}