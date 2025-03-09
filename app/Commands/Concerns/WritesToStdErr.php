<?php

namespace App\Commands\Concerns;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

trait WritesToStdErr
{
    protected StreamOutput|OutputStyle $stderr {
        get => $this->getStderr();
    }

    protected function getStderr(): StreamOutput|OutputStyle
    {
        $result = $this->getOutput();
        $resultOutput = $result->getOutput();
        if($resultOutput instanceof ConsoleOutputInterface) {
            return $resultOutput->getErrorOutput();
        }
        return $result;
    }
}
