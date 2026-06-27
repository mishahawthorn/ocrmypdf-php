<?php

namespace mishahawthorn\OCRmyPDF;

use InvalidArgumentException;

class Process
{
    /**
     * @var ?resource $stdin
     */
    private $stdin;

    /**
     * @var ?resource $stdout
     */
    private $stdout;

    /**
     * @var ?resource $stderr
     */
    private $stderr;
    private mixed $handle;

    /**
     * Maximum runtime in seconds before the process is terminated, or null for
     * no limit.
     */
    private ?float $timeout;

    /**
     * @param list<string>|string $command Argument vector (preferred) or, for
     *   testing only, a command string. The array form is passed straight to
     *   proc_open() so no shell is involved.
     * @param float|null $timeout Maximum runtime in seconds, or null for none.
     * @throws UnsuccessfulCommandException
     */
    public function __construct(array|string $command, ?float $timeout = null)
    {
        $this->timeout = $timeout;

        $streamDescriptors = [
            ["pipe", "r"],
            ["pipe", "w"],
            ["pipe", "w"]
        ];
        $this->handle = proc_open(
            command: $command,
            descriptor_spec: $streamDescriptors,
            pipes: $pipes,
            cwd: NULL,
            env_vars: NULL,
            options: ["bypass_shell" => true]
        );

        /** @var array<resource> $pipes */
        if (isset($pipes[0])) {
            $this->stdin = $pipes[0];
        }
        if (isset($pipes[1])) {
            $this->stdout = $pipes[1];
        }
        if (isset($pipes[2])) {
            $this->stderr = $pipes[2];
        }

        self::checkProcessCreation($this->handle, self::describe($command));

        //This can avoid deadlock on some cases (when stderr buffer is filled up before writing to stdout and vice versa)
        if (is_resource($this->stdout)) {
            stream_set_blocking($this->stdout, false);
        }
        if (is_resource($this->stderr)) {
            stream_set_blocking($this->stderr, false);
        }
    }

    /**
     * @param list<string>|string $command
     */
    private static function describe(array|string $command): string
    {
        return is_array($command) ? join(' ', $command) : $command;
    }

    /**
     * @throws UnsuccessfulCommandException
     */
    public static function checkProcessCreation(mixed $processHandle, string|Command $command): void
    {
        if (is_resource($processHandle) === true) {
            return;
        }
        $msg = [];
        $msg[] = 'Error: The command could not be launched.';
        $msg[] = '';
        $msg[] = 'Generated command:';
        $msg[] = "$command";
        $msg = join(PHP_EOL, $msg);

        throw new UnsuccessfulCommandException($msg);
    }

    public function write(string $data, int $dataLength): bool
    {
        if (is_resource($this->stdin) === false) {
            return false;
        }

        $total = 0;
        do {
            $res = fwrite($this->stdin, substr($data, $total));
            if ($res === false) {
                break;
            }

            $total += $res;
        } while ($total < $dataLength);
        return $total === $dataLength;
    }

    /**
     * Block until the process exits (or the timeout elapses), draining stdout
     * and stderr without busy-waiting.
     *
     * @return array{out: string, err: string, exitCode: int|null, timedOut: bool}
     */
    public function wait(): array
    {
        $data = [
            "out" => "",
            "err" => "",
            "exitCode" => null,
            "timedOut" => false,
        ];

        if (is_resource($this->stdout) === false
            || is_resource($this->stderr) === false
            || is_resource($this->handle) === false
        ) {
            return $data;
        }

        $deadline = $this->timeout !== null ? microtime(true) + $this->timeout : null;

        while (true) {
            if ($deadline !== null) {
                $remaining = $deadline - microtime(true);
                if ($remaining <= 0) {
                    $this->terminate();
                    $data["timedOut"] = true;
                    break;
                }
                $tick = min($remaining, 0.2);
            } else {
                $tick = 0.2;
            }

            $read = [$this->stdout, $this->stderr];
            $write = $except = null;
            $sec = (int)floor($tick);
            $usec = (int)round(($tick - $sec) * 1_000_000);
            $ready = @stream_select($read, $write, $except, $sec, $usec);

            if ($ready === false) {
                // Interrupted (e.g. by a signal); retry.
                continue;
            }

            foreach ($read as $stream) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) continue;
                if ($stream === $this->stdout) {
                    $data["out"] .= $chunk;
                } else {
                    $data["err"] .= $chunk;
                }
            }

            $status = proc_get_status($this->handle);
            if ($status["running"] === false) {
                // exitcode is only valid the first time running flips to false.
                $data["exitCode"] = $status["exitcode"] === -1 ? null : $status["exitcode"];
                break;
            }
        }

        // Final drain: data may remain buffered in the pipes after the process
        // exits. Without this, large stdout payloads (e.g. PDF data) truncate.
        $data["out"] .= $this->drain($this->stdout);
        $data["err"] .= $this->drain($this->stderr);

        return $data;
    }

    /**
     * @param resource|null $stream
     */
    private function drain($stream): string
    {
        $buffer = "";
        if (is_resource($stream)) {
            while (($chunk = fread($stream, 8192)) !== false && $chunk !== '') {
                $buffer .= $chunk;
            }
        }
        return $buffer;
    }

    /**
     * Terminate the running process: SIGTERM, then SIGKILL if it lingers.
     */
    private function terminate(): void
    {
        if (is_resource($this->handle) === false) {
            return;
        }
        proc_terminate($this->handle);

        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $status = proc_get_status($this->handle);
            if ($status["running"] === false) {
                return;
            }
            usleep(50_000);
        }
        proc_terminate($this->handle, 9);
    }

    public function closeStreams(?string $stream = null): self
    {
        switch ($stream) {
            case "stdin":
                $this->closeStream($this->stdin);
                break;
            case "stdout":
                $this->closeStream($this->stdout);
                break;
            case "stderr":
                $this->closeStream($this->stderr);
                break;
            case null:
                $this->closeStream($this->stdin);
                $this->closeStream($this->stdout);
                $this->closeStream($this->stderr);
                break;
        }
        return $this;
    }

    public function closeHandle(): self
    {
        if (is_resource($this->handle) === false) {
            throw new InvalidArgumentException("Process handle is not a resource");
        }
        proc_close($this->handle);
        return $this;
    }

    public function closeStdin(): void
    {
        $this->closeStream($this->stdin);
    }

    /**
     * @param resource|null $stream
     */
    private function closeStream(&$stream): void
    {
        if (is_resource($stream) && get_resource_type($stream) === 'stream') {
            fclose($stream);
            $stream = null;
        }
    }
}
