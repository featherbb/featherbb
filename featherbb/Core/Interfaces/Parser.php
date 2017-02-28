<?php
namespace FeatherBB\Core\Interfaces;


class Parser extends SlimSugar
{
    /**
     * Parse post or signature message text.
     *
     * @param string &$text
     * @param integer $hideSmilies
     * @return string
     */
    public static function parseBbcode(&$text, $hideSmilies = 0)
    {
        return static::$slim->getContainer()['parser']->parseBbcode($text, $hideSmilies);
    }

    /**
     * Parse message text
     *
     * @param string $text
     * @param integer $hideSmilies
     * @return string
     */
    public static function parseMessage($text, $hideSmilies)
    {
        return static::$slim->getContainer()['parser']->parseMessage($text, $hideSmilies);
    }

    /**
     * Parse signature text
     *
     * @param string $text
     * @return string
     */
    public static function parseSignature($text)
    {
        return static::$slim->getContainer()['parser']->parseSignature($text);
    }

    /**
     * Pre-process text containing BBCodes. Check for integrity,
     * well-formedness, nesting, etc. Flag errors by wrapping offending
     * tags in a special [err] tag.
     *
     * @param string $text
     * @param array &$errors
     * @param integer $isSignature
     * @return string
     */
    public static function preparseBbcode($text, &$errors, $isSignature = false)
    {
        return static::$slim->getContainer()['parser']->preparseBbcode($text, $errors, $isSignature);
    }
}