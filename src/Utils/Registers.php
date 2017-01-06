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