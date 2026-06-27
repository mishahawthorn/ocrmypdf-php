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
use function PHPUnit\Framework\assertTrue;

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
        $process = new Process(['true']);

        $reflectedProcess = new ReflectionClass($process);

        $reflectedProcessStdin = $reflectedProcess->getProperty('stdin');
        $reflectedProcessStdin->setAccessible(true);
        $reflectedProcessStdin->setValue($process, null);

        assertFalse($process->write("test", 0));
    }

    public function testWaitWithNullStdout(): void
    {
        $process = new Process(['true']);

        $reflectedProcess = new ReflectionClass($process);

        $reflectedProcessStdout = $reflectedProcess->getProperty('stdout');
        $reflectedProcessStdout->setAccessible(true);
        $reflectedProcessStdout->setValue($process, null);

        assertEquals([
            "out" => "",
            "err" => "",
            "exitCode" => null,
            "timedOut" => false,
        ], $process->wait());
    }

    public function testWaitReportsExitCode(): void
    {
        $process = new Process(['false']);
        $output = $process->wait();
        assertEquals(1, $output["exitCode"]);
        assertFalse($output["timedOut"]);
    }

    public function testWaitTimesOut(): void
    {
        $process = new Process(['sleep', '5'], 0.2);
        $output = $process->wait();
        assertTrue($output["timedOut"]);
    }

    public function testCloseHandleWithNullHandle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $process = new Process(['true']);

        $reflectedProcess = new ReflectionClass($process);

        $reflectedProcessStdin = $reflectedProcess->getProperty('handle');
        $reflectedProcessStdin->setAccessible(true);
        $reflectedProcessStdin->setValue($process, null);

        $process->closeHandle();
    }
}
