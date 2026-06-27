<?php

namespace mishahawthorn\OCRmyPDF;

class Command
{
    public string $executable = 'ocrmypdf';
    public bool $useFileAsInput = true;
    public bool $useFileAsOutput = true;
    public int|null $inputDataSize;
    public string|null $inputData;

    /**
     * Whether OCR'd plaintext should be written to a sidecar file.
     */
    public bool $useSidecar = false;

    /**
     * Path of the sidecar text file. Lazily generated when null.
     */
    public ?string $sidecarPath = null;

    /**
     * True when the output path was generated internally (a temp file we own
     * and may safely delete on failure), false when supplied by the caller.
     */
    public bool $generatedOutputPath = false;

    /**
     * True when the sidecar path was generated internally.
     */
    public bool $generatedSidecarPath = false;

    /**
     * Human-readable meanings for OCRmyPDF exit codes.
     *
     * @see https://ocrmypdf.readthedocs.io/en/latest/advanced.html#return-code-policy
     * @var array<int, string>
     */
    public const EXIT_CODE_MESSAGES = [
        1  => 'Invalid arguments, exited with an error.',
        2  => 'The input file does not seem to be a valid PDF.',
        3  => 'An external program required by OCRmyPDF is missing.',
        4  => 'An output file was created, but it does not seem to be a valid PDF. The file will be available.',
        5  => 'The user running OCRmyPDF does not have sufficient permissions to read the input file and write the output file.',
        6  => 'The file already appears to contain text so it may not need OCR. See the --skip-text, --force-ocr and --redo-ocr arguments.',
        7  => 'An error occurred in an external program (child process) and OCRmyPDF cannot continue.',
        8  => 'The input PDF is encrypted. OCRmyPDF does not read or write encrypted PDFs. Remove the encryption and try again.',
        9  => 'A custom configuration file was forwarded to Tesseract using --tesseract-config, and Tesseract rejected this file.',
        10 => 'A valid PDF was created, PDF/A conversion failed. The file will be available.',
        15 => 'Some other error occurred.',
        130 => 'The program was interrupted by pressing Ctrl+C.',
    ];

    /**
     * @param array<string, bool|string|string[]> $parameters
     */
    public function __construct(
        public ?string $inputFilePath = null,
        public ?string $outputPDFPath = null,
        public ?string $tempDir = null,
        public ?int    $threadLimit = null,
        public array   $parameters = []
    )
    {
    }

    /**
     * Determine whether the invocation succeeded based primarily on the
     * process exit code, falling back to output inspection when the exit code
     * is unavailable.
     *
     * @param Command $command Generated command
     * @param string $stdout Value from stdout
     * @param string $stderr Value from stderr
     * @param int|null $exitCode Process exit code (null when unknown)
     * @return bool Returns true upon successful execution
     * @throws UnsuccessfulCommandException
     * @throws NoWritePermissionsException
     */
    public static function checkCommandExecution(
        Command $command,
        string  $stdout,
        string  $stderr,
        ?int    $exitCode = null
    ): bool
    {
        if ($exitCode === 0) {
            return true;
        }

        if ($exitCode === null) {
            // No exit code available: fall back to inspecting output.
            if ($command->useFileAsOutput) {
                $file = $command->getOutputPDFPath();
                if (file_exists($file) && filesize($file) > 0) return true;
            } elseif ($stdout !== '') {
                return true;
            }
            if (!str_contains(strtoupper($stderr), 'ERROR')) {
                return true;
            }
        }

        $msg = [];
        $msg[] = 'Error: The command exited unsuccessfully.';
        if ($exitCode !== null) {
            $meaning = self::EXIT_CODE_MESSAGES[$exitCode] ?? 'Unknown error.';
            $msg[] = "Exit code $exitCode: $meaning";
        }
        $msg[] = '';
        $msg[] = 'Generated command:';
        $msg[] = "$command";
        $msg[] = '';
        $msg[] = 'Returned message:';
        $arrayStderr = explode(PHP_EOL, rtrim($stderr, PHP_EOL));
        $msg = array_merge($msg, $arrayStderr);
        $msg = join(PHP_EOL, $msg);

        throw new UnsuccessfulCommandException($msg);
    }

    /**
     * Build the command as an argument vector. Passing this array directly to
     * proc_open() avoids spawning a shell, eliminating quoting/injection
     * concerns around parameter values.
     *
     * @return list<string>
     * @throws NoWritePermissionsException
     */
    public function toArray(): array
    {
        $args = [$this->executable];

        if ($this->threadLimit !== null) {
            $args[] = "--jobs=$this->threadLimit";
        }

        foreach ($this->parameters as $key => $value) {
            if ($value === true) {
                $args[] = $key;
                continue;
            }
            $stringValue = is_array($value) ? join(',', $value) : $value;
            $args[] = "$key=$stringValue";
        }

        if ($this->useSidecar) {
            $args[] = "--sidecar=" . $this->getSidecarPath();
        }

        $args[] = $this->useFileAsInput ? (string)$this->inputFilePath : '-';
        $args[] = $this->useFileAsOutput ? $this->getOutputPDFPath() : '-';

        return $args;
    }

    /**
     * Render the command as a human-readable, shell-escaped string. Used for
     * logging and error messages only — never for execution.
     *
     * @throws NoWritePermissionsException
     */
    public function __toString(): string
    {
        return join(' ', array_map([self::class, 'escape'], $this->toArray()));
    }

    /**
     * @throws NoWritePermissionsException
     */
    public function getOutputPDFPath(): string
    {
        if ($this->outputPDFPath === null) {
            $this->outputPDFPath = $this->generateTempFile('.pdf');
            $this->generatedOutputPath = true;
        }
        return $this->outputPDFPath;
    }

    /**
     * @throws NoWritePermissionsException
     */
    public function getSidecarPath(): string
    {
        if ($this->sidecarPath === null) {
            $this->sidecarPath = $this->generateTempFile('.txt');
            $this->generatedSidecarPath = true;
        }
        return $this->sidecarPath;
    }

    /**
     * Reserve a unique temp filename with the given suffix. The zero-byte stub
     * created by tempnam() is removed so no orphan is left behind.
     *
     * @throws NoWritePermissionsException
     */
    private function generateTempFile(string $suffix): string
    {
        $tempPath = tempnam($this->getTempDir(), 'ocr_');
        if ($tempPath === false) {
            throw new NoWritePermissionsException("Cannot create temporary file in {$this->getTempDir()}");
        }
        @unlink($tempPath);
        return $tempPath . $suffix;
    }

    public function getTempDir(): string
    {
        return $this->tempDir !== null ? $this->tempDir : sys_get_temp_dir();
    }

    /**
     * @throws UnsuccessfulCommandException When the executable cannot be launched.
     */
    public function getOCRmyPDFVersion(): string
    {
        $process = new Process([$this->executable, '--version']);
        $output = $process->wait();
        $process->closeStreams()->closeHandle();

        $combined = $output["out"] !== '' ? $output["out"] : $output["err"];
        $firstLine = strtok($combined, "\n");
        return $firstLine === false ? '' : trim($firstLine);
    }

    public static function escape(string $str): string
    {
        $charlist = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? '$"`' : '$"\\`';
        return '"' . addcslashes($str, $charlist) . '"';
    }
}
