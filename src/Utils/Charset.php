<?php
declare(strict_types=1);

namespace ModbusTcpClient\Utils;


class Charset
{
    // NOTE: In PHP 8.1 the ASCII validation is stricter and input code units over 0x80 (7bit) are considered as illegal.
    // This means that before 8.1 you could mb_convert 'ø' (decimal 248) ASCII to UTF-8 and it would work but from 8.1
    // you need to use true 8bit charsets ala ISO-8859-1.

    // 'ASCII' is 7-bit charset and 'ISO-8859-1' is 8-bit charset which supports some additional characters.
    // See: https://en.wikipedia.org/wiki/ASCII#Character_set
    const ASCII = "ASCII";
    // ISO-8859-1 is a common european encoding, able to encode 'äöåéü' and other characters (for example
    // 'ø' decimal 248), the first 127 characters being the same as in ASCII.
    // See: https://en.wikipedia.org/wiki/ISO/IEC_8859-1#Code_page_layout
    const ISO_8859_1 = "ISO-8859-1";

    public static string $defaultCharset = self::ISO_8859_1;

    public static function getCurrentEndianness(string $charset = null): string
    {
        return $charset === null ? static::$defaultCharset : $charset;
    }
}
