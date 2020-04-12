<?php

namespace ModbusTcpClient\Network;


class SerialStreamCreator implements StreamCreator
{
    /**
     * For `stty` options see http://man7.org/linux/man-pages/man1/stty.1.html
     *
     * Dear reader - if you know that some option are not needed here then add a comment/issue in github
     */
    const DEFAULT_STTY_MODES = [
        'cs8', // set character size 8 bits
        '9600', // set baud rate 9600
        '-icanon', // disable enable special characters: erase, kill, werase, rprnt
        'min 0', // with -icanon, set N characters minimum for a completed read
        'ignbrk', // enable ignore break characters
        '-brkint', // disable breaks cause an interrupt signal
        '-icrnl', // disable translate carriage return to newline
        '-imaxbel', // disable beep and do not flush a full input buffer on a character
        '-opost', // disable postprocess output
        '-onlcr', // disable translate newline to carriage return-newline
        '-isig', // disable interrupt, quit, and suspend special characters
        '-iexten', // disable non-POSIX special characters
        '-echo', // disable echo input characters
        '-echoe', // disable echo erase characters as backspace-space-backspace
        '-echok', // disable echo a newline after a kill character
        '-echoctl', // disable same as [-]ctlecho
        '-echoke', // disable kill all line by obeying the echoprt and echoe settings
        '-noflsh', // disable flushing after interrupt and quit special characters
        '-ixon', // disable XON/XOFF flow control
        '-crtscts', // disable RTS/CTS handshaking
    ];

    private $sttyModes = self::DEFAULT_STTY_MODES;

    public function __construct(array $options = [])
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            throw new \RuntimeException('this stream can not be created in Windows!');
        }

        if (array_key_exists('sttyModes', $options)) {
            $this->sttyModes = $options['sttyModes'];
        }
    }

    /**
     * @param BinaryStreamConnection $conn
     * @return resource
     */
    public function createStream(BinaryStreamConnection $conn)
    {
        $device = $conn->getUri();

        $sttyModes = implode(' ', $this->sttyModes);
        $cmd = escapeshellcmd("stty -F ${device} ${sttyModes}");
        $sttyResult = exec($cmd);
        if ($sttyResult === false) {
            throw new IOException('stty failed to configure device');
        }

        $stream = fopen($device, 'w+b');
        if ($stream === false) {
            throw new IOException('failed to open device');
        }

        return $stream;
    }
}
