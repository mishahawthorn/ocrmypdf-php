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
     * @param Command $command Generated command
     * @param string $stdout Value from stdout
     * @param string $stderr Value from stderr
     * @return bool Returns true upon successful execution
     * @throws UnsuccessfulCommandException
     * @throws NoWritePermissionsException
     */
    public static function checkCommandExecution(Command $command, string $stdout, string $stderr): bool
    {
        if ($command->useFileAsOutput) {
            $file = $command->getOutputPDFPath();
            if (file_exists($file) && filesize($file) > 0) return true;
        }

        if (!$command->useFileAsOutput && $stdout === '') {
            return true;
        }

        if (!str_contains(strtoupper($stderr), 'ERROR')) {
            return true;
        }

        $msg = [];
        $msg[] = 'Error: The command output contains an error.';
        $msg[] = '';
        $msg[] = 'Generated command:';
        $msg[] = "$command";
        $msg[] = '';
        $msg[] = 'Returned message:';
        $arrayStderr = explode(PHP_EOL, $stderr);
        array_pop($arrayStderr);
        $msg = array_merge($msg, $arrayStderr);
        $msg = join(PHP_EOL, $msg);

        throw new UnsuccessfulCommandException($msg);
    }

    /**
     * @throws NoWritePermissionsException
     */
    public function __toString(): string
    {
        $cmd = [];

        $cmd[] = self::escape($this->executable);

        if ($this->threadLimit !== null) $cmd[] = "--jobs=$this->threadLimit";

        foreach ($this->parameters as $key => $value) {
            if ($value !== true) {
                $paramKeyValue = $key;
                $paramKeyValue .= "='";
                if (is_array($value)) {
                    $paramKeyValue .= join(',', $value);
                } else {
                    $paramKeyValue .= $value;
                }
                $paramKeyValue .= "'";
                $cmd[] = $paramKeyValue;
            } else {
                $cmd[] = $key;
            }
        }

        $cmd[] = $this->useFileAsInput ? self::escape((string)$this->inputFilePath) : "-";
        $cmd[] = $this->useFileAsOutput ? self::escape($this->getOutputPDFPath()) : "-";

        return join(' ', $cmd);
    }

    /**
     * @throws NoWritePermissionsException
     */
    public function getOutputPDFPath(): string
    {
        if ($this->outputPDFPath === null) {
            $tempPath = tempnam($this->getTempDir(), 'ocr_');
            if ($tempPath === false) {
                throw new NoWritePermissionsException("Cannot create temporary file in {$this->getTempDir()}");
            }
            $this->outputPDFPath = $this->getTempDir()
                . DIRECTORY_SEPARATOR
                . basename($tempPath)
                . ".pdf";
        }
        return $this->outputPDFPath;
    }

    public function getTempDir(): string
    {
        return $this->tempDir !== null ? $this->tempDir : sys_get_temp_dir();
    }

    public function getOCRmyPDFVersion(): string
    {
        exec(self::escape($this->executable) . ' --version 2>&1', $output);
        return (string)reset($output);
    }

    public static function escape(string $str): string
    {
        $charlist = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? '$"`' : '$"\\`';
        return '"' . addcslashes($str, $charlist) . '"';
    }
}