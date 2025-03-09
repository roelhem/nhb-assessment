<?php

namespace App\Commands\Concerns;

use Exception;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions\CalcErrorException;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions\CalcInputException;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini\IniReaderValueException;
use Throwable;

trait WritesExceptionMessages
{
    use WritesToStdErr;

    /**
     * @throws Throwable
     */
    protected function writeException(Throwable $e): void
    {
        global $argv;
        if($e instanceof IniReaderValueException) {
            $this->stderr->writeln([
                '',
                '<fg=red;options=bold>ERROR:          Ongeldig configuratiebestand</>',
                '<fg=red>section:        </>'.($e->section ?? '<fg=gray><toplevel></>'),
                '<fg=red>key:            </>'.$e->key,
                '<fg=red>current value:  </>'.$e->value,
                '<fg=red>expected value: </>'.$e->expectedMessage,
                '',
                'Pas het configuratiebestand aan en probeer het nogmaals.',
                '',
            ]);
            return;
        }

        if($e instanceof CalcErrorException) {
            $this->stderr->writeln([
                '',
                '<fg=red;options=bold>ERROR: Fout tijdens calculatie</>',
                '<fg=red>message: </>'.($e->getMessage() ?? ''),
                '',
                'Controleer de configuratie van het '.$argv[0].' programma.',
                '',
                "<fg=red>$e</>"
            ]);
            return;
        }

        if($e instanceof CalcInputException) {
            $this->stderr->writeln([
                '',
                '<fg=red;options=bold>ERROR: Ongeldige invoer voor calculatie</>',
                '<fg=red>message: </>'.($e->getMessage() ?? ''),
                '',
                'Pas de invoergegevens aan en probeer het nogmaals.',
                '',
            ]);
            return;
        }

        throw $e;
    }
}
