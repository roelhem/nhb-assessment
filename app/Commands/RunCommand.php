<?php

namespace App\Commands;

use App\Commands\Concerns\WritesExceptionMessages;
use Illuminate\Support\Number;
use Illuminate\Console\Command;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini\IniReaderValueException;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;
use Throwable;

class RunCommand extends Command
{
    use WritesExceptionMessages;

    protected $signature = 'run {file : Path to the configuration file.}';

    protected $description = 'Calculate the maximum mortgage.';

    public function handle(
        MaximumByIncome\InputSerializer $inputSerializer,
        MaximumByIncome\CalcProvider $calcProvider
    ): int
    {
        $file = $this->argument('file');
        if($file === '-') {
            $inputStr = file_get_contents('php://stdin');
        } else {
            if(!file_exists($file)) {
                $this->stderr->writeln([
                    '<fg=red;options=bold>ERROR: Bestand niet gevonden</>',
                ]);
                return 1;
            }
            $inputStr = file_get_contents($file);
        }

        try {
            $input = $inputSerializer->deserialize($inputStr);
        } catch (IniReaderValueException $e) {
            $this->writeException($e);
            return 1;
        }

        try {
            $result = $calcProvider->calcMaximumByIncome($input);
        } catch (Throwable $e) {
            $this->writeException($e);
            return 1;
        }

        $this->stderr->writeln([
            '',
            '<info>Maximale Hypotheek: </> '.Number::currency($result->value, in: 'EUR', locale: 'nl_NL'),
            '',
        ]);
        $this->output->writeln($result->value);
        return 0;
    }
}
