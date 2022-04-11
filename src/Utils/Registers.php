<?php
declare(strict_types=1);

namespace ModbusTcpClient\Utils;


class Registers
{
    /**
     * Calculates size in bytes of array of strings
     *
     * @param string[] $registers
     * @return int
     */
    public static function getRegisterArrayByteSize(array $registers): int
    {
        $result = 0;
        foreach ($registers as $register) {
            $len = strlen($register);
            if ($len < 2) {
                $result += 2;
            } else { // if double word or something exotic is set
                $result += ($len % 2 === 0) ? $len : $len + 1;
            }
        }
        return $result;
    }

    /**
     * Combines array of bytes (string) into one byte string by making sure that register odd bytes are filled
     *
     * @param string[]|null[] $registers
     * @return string
     */
    public static function getRegisterArrayAsByteString(array $registers): string
    {
        $result = '';
        foreach ($registers as $register) {
            if (null === $register) {
                $result .= "\x00\x00";
            } else {
                $len = strlen($register);
                if (!(($len % 2) === 0)) {
                    //odd length needs padding to make up whole word
                    $result .= "\x00" . $register;
                } else {
                    $result .= $register;
                }
            }
        }
        return $result;
    }
}
