<?php

namespace ModbusTcpClient\Composer;

use ModbusTcpClient\Exception\InvalidArgumentException;

class Range
{
    private int $min;
    private int $max;

    public function __construct(int $minIncluding, int $maxIncluding)
    {
        $this->min = min($minIncluding, $maxIncluding);
        $this->max = max($minIncluding, $maxIncluding);
    }

    public function overlaps(int $min, int $max): bool
    {
        return $max >= $this->min && $this->max >= $min;
    }

    /**
     * @param array<int> $range
     * @return Range
     */
    public static function fromIntArray(array $range): Range
    {
        $count = count($range);
        if ($count == 1) {
            return new Range($range[0], $range[0]);
        } else if ($count == 2) {
            return new Range($range[0], $range[1]);
        }
        throw new InvalidArgumentException('Range can only be created from array with 1 or 2 elements');
    }
}
