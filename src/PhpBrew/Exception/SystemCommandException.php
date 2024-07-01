<?php

declare(strict_types=1);

namespace PhpBrew\Exception;

use PhpBrew\Buildable;
use RuntimeException;

class SystemCommandException extends RuntimeException
{
    protected $logFile;

    protected $build;

    public function __construct($message, ?Buildable $build = null, $logFile = null)
    {
        parent::__construct($message);
        $this->build = $build;
        $this->logFile = $logFile;
    }

    public function getLogFile()
    {
        if ($this->logFile) {
            return $this->logFile;
        }
        if ($this->build) {
            return $this->build->getBuildLogPath();
        }
    }
}
