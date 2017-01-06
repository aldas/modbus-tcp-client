<?php

namespace ModbusTcpClient\Utils;


class Registers
{
    public static function getRegisterArrayByteSize(array $registers)
    {
        $result = 0;
        foreach ($registers as $register) {
            if (null === $register || strlen($register) === 1) {
                $result += 2;
            } else { // if double word or something exotic is set
                $result += strlen($register);
            }
        }
        return $result;
    }

    public static function getRegisterArrayAsByteString(array $registers)
    {
        $result = '';
        foreach ($registers as $register) {
            if (null === $register) {
                $result .= "\x00\x00";
            } else {
                $len = strlen($register);
                if ($len === 1) {
                    $result .= "\x00{$register}";
                } else if ($len === 2) {
                    $result .= $register;
                } else {
                    $result .= self::reverseWordsInBinaryToLowFirst($register);
                }
            }
        }
        return $result;
    }

    /**
     * reverse words order in binary. 2 bytes make up 1 word. we need to send  first word (at the end of binary) first and so on
     */
    private static function reverseWordsInBinaryToLowFirst($binary)
    {
        $words = str_split($binary, 2);
        $count = strlen($binary);

        $wordsInReverse = implode('', array_reverse($words));
        // if last word (now first) is single byte we need to pad it with 0 byte
        if (!(($count % 2) === 0)) {
            return "\x0" . $wordsInReverse;
        }
        return $wordsInReverse;
    }

}