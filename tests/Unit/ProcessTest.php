<?php

namespace mishahawthorn\OCRmyPDF\Tests\Unit;

use InvalidArgumentException;
use mishahawthorn\OCRmyPDF\Process;
use mishahawthorn\OCRmyPDF\UnsuccessfulCommandException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;

#[CoversClass(Process::class)]
class ProcessTest extends TestCase
{
    public function testCheckProcessCreationFailed(): void
    {
        $this->expectException(UnsuccessfulCommandException::class);
        Process::checkProcessCreation(FALSE, "");
    }

    public function testWriteWithNullStdin(): void
    {
        $process = new Process("");

        $reflectedProcess = new ReflectionClass($process);

        $reflectedProcessStdin = $reflectedProcess->getProperty('stdin');
        $reflectedProcessStdin->setAccessible(true);
        $reflectedProcessStdin->setValue($process, null);

        assertFalse($process->write("test", 0));
    }

    public function testWaitWithNullStdin(): void
    {
        $process = new Process("");

        $reflectedProcess = new ReflectionClass($process);

        $reflectedProcessStdin = $reflectedProcess->getProperty('stdin');
        $reflectedProcessStdin->setAccessible(true);
        $reflectedProcessStdin->setValue($process, null);

        assertEquals([
            "out" => "",
            "err" => ""
        ], $process->wait());
    }

    public function testCloseHandleWithNullHandle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $process = new Process("");

        $reflectedProcess = new ReflectionClass($process);

        $reflectedProcessStdin = $reflectedProcess->getProperty('handle');
        $reflectedProcessStdin->setAccessible(true);
        $reflectedProcessStdin->setValue($process, null);

        $process->closeHandle();
    }
}
