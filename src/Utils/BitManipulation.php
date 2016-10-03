<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
declare(strict_types=1);

namespace Nekland\Woketo\Utils;

/**
 * Class BitManipulation
 *
 * Glossary:
 *   - in this context a "frame" is an assembly of bytes represented by a "byte-string" or a (signed) int.
 */
class BitManipulation
{
    /**
     * Get a specific bit from a byte.
     *
     * @param int $byte
     * @param int $bitNumber
     * @return int
     */
    public static function nthBit(int $byte, int $bitNumber) : int
    {
        if ($byte < 0 || $byte > 255) {
            throw new \InvalidArgumentException(
                sprintf('The given integer %s is not a byte.', $byte)
            );
        }

        if ($bitNumber < 1 || $bitNumber > 8) {
            throw new \InvalidArgumentException(
                sprintf('The bit number %s is not a correct value for a byte (1-8 required).', $bitNumber)
            );
        }

        $realNth = pow(2, 8 - $bitNumber);

        return (int) ($realNth === ($byte & $realNth));
    }

    /**
     * Get a specific byte inside a frame represented by an int or a string.
     *
     * @param string|int $frame      Non utf8 string (this should be more precisely a bytes-string).
     * @param int        $byteNumber Starting at 0.
     * @return int
     */
    public static function nthByte($frame, int $byteNumber) : int
    {
        if (is_string($frame)) {
            $len = strlen($frame);

            if ($byteNumber < 0 || $byteNumber > ($len-1)) {
                throw new \InvalidArgumentException(
                    sprintf('The frame is only %s bytes larges but you tried to get the %sth byte.', $len, $byteNumber)
                );
            }

            return ord($frame[$byteNumber]);
        }

        if (is_int($frame)) {
            if ($frame < 0) {
                throw new \InvalidArgumentException(
                    sprintf('This method does not support negative ints as parameter for now. %s given.', $byteNumber)
                );
            }
            $hex = dechex($frame);
            $len = strlen($hex);

            // Index of the first octal of the wanted byte
            $realByteNth = $byteNumber * 2;

            if ($byteNumber < 0 || ($realByteNth + 1) > $len) {
                throw new \InvalidArgumentException(
                    sprintf('Impossible to get the byte %s from the frame %s.', $byteNumber, $frame)
                );
            }

            // Considering FF12AB (number) if you want the byte represented by AB you need to get the
            // first letter, shift it of 4 and add the next letter.
            // This may seems weird but that's because you read numbers from right to left.
            return (hexdec($hex[$realByteNth]) << 4) + hexdec($hex[$realByteNth + 1]);
            // _Notice that if the number is from right to left, your data is still from left to right_
        }

        throw new \InvalidArgumentException(
            sprintf('The frame must be an int or string, %s given.', gettype($frame))
        );
    }

    public static function partOfByte(int $byte, int $part) : int
    {
        if ($byte < 0 || $byte > 255) {
            throw new \InvalidArgumentException(sprintf('%s is not a byte', $byte));
        }

        if ($part === 1) {
            return ($byte & 240) >> 4;
        }

        if ($part === 2) {
            return $byte & 15;
        }

        throw new \InvalidArgumentException(sprintf('A byte have only 2 parts. %s asked.', $part));
    }

    /**
     * Because strings are the best way to store many bytes in PHP it can
     * be useful to make the conversion between hex (which are strings)
     * array to string.
     *
     * @param array $hexArray
     * @return string
     */
    public static function hexArrayToString(...$hexArray) : string
    {
        if (is_array($hexArray[0])) {
            $hexArray = $hexArray[0];
        }
        
        $res = '';
        foreach ($hexArray as $hexNum) {
            $res .= chr(hexdec($hexNum));
        }

        return $res;
    }

    /**
     * @param string|int $frame
     * @param int        $from        Byte where to start (should be inferior to $to).
     * @param int        $to          Byte where to stop (considering it starts at 0). The `to` value include the target
     *                                byte.
     * @param bool       $force8bytes By default PHP have a wrong behavior with 8 bytes variables. If you have 8 bytes
     *                                the returned int will be negative (because unsigned integers does not exists in PHP)
     * @return int
     */
    public static function bytesFromTo($frame, int $from, int $to, bool $force8bytes = false) : int
    {
        // No more than 64b (which return negative number when the first bit is specified)
        if (($to - $from) > 7 && (!$force8bytes && ($to - $from) !== 8)) {
            if ($force8bytes) {
                throw new \InvalidArgumentException(sprintf('Not more than 8 bytes (64bit) is supported by this method and you asked for %s bytes.', $to - $from));
            }
            throw new \InvalidArgumentException('PHP limitation: getting more than 7 bytes will return a negative number because unsigned int does not exist.');
        }

        if (is_string($frame)) {
            if ((strlen($frame) - 1) < $to) {
                throw new \InvalidArgumentException('The frame is not long enough.');
            }

            $subStringLength = $to - $from + 1;
            // Getting responsible bytes
            $subString = substr($frame, $from, $subStringLength);
            $res = 0;

            // for each byte, getting ord
            for($i = 0; $i < $subStringLength; $i++) {
                $res <<= 8;
                $res += ord($subString[$i]);
            }

            return $res;
        }

        if (!is_int($frame)) {
            throw new \InvalidArgumentException(
                sprintf('A frame can only be a string or int. %s given', gettype($frame))
            );
        }

        if ($frame < 0) {
            throw new \InvalidArgumentException('The frame cannot be a negative number');
        }

        $res = 0;
        for ($i = $from; $i <= $to; $i++) {
            $res <<= 8;
            $res += BitManipulation::nthByte($frame, $i);
        }

        return $res;
    }

    /**
     * Take a frame represented by a decimal int to transform it in a string.
     * Notice that any int is a frame and cannot be more than 8 bytes
     *
     * @param int      $frame
     * @param int|null $size  In bytes.
     * @return string
     */
    public static function intToString(int $frame, int $size = null) : string
    {
        $res = '';
        $startingBytes = true;
        for ($i = 8; $i >= 0; $i--) {
            $code = ($frame & (255 << ($i * 8))) >> ($i * 8);

            // This condition avoid to take care of front zero bytes (that are always present but we should ignore)
            if ($code !== 0 || !$startingBytes) {
                $startingBytes = false;
                $res .= chr($code);
            }
        }

        if ($size !== null) {
            $actualSize = strlen($res);
            if ($size < $actualSize) {
                $res = substr($res, $size - $actualSize);
            } else if ($size > $actualSize) {
                $missingChars = $size - $actualSize;
                for ($i = 0; $i < $missingChars; $i++) {
                    $res = chr(0) . $res;
                }
            }
        }

        return $res;
    }

    /**
     * Take an string frame and transform it to a decimal frame (inside an int).
     *
     * @param string $frame
     * @return int
     */
    public static function stringToInt(string $frame) : int
    {
        $len = strlen($frame);
        $res = 0;

        if ($len > 8) {
            throw new \InvalidArgumentException(
                sprintf('The string %s cannot be converted to int because an int cannot be more than 8 bytes (64b).', $frame)
            );
        }

        for ($i = $len - 1; $i >= 0; $i--) {
            $res += ord($frame[$len - $i - 1]) << ($i * 8);
        }

        return $res;
    }

    /**
     * Method that return frame as hex (more readable).
     * Helpful for debug !
     *
     * @param string $frame
     * @return string
     */
    public static function frameToHex(string $frame) : string
    {
        $len = strlen($frame);
        $res = '';

        for ($i = 0; $i < $len; $i++) {
            $res .= dechex(ord($frame[$i]));
        }

        return $res;
    }
}
