<?php

namespace App\Commands;

use BcMath\Number;
use DateTime;
use Illuminate\Support\Carbon;
use LaravelZero\Framework\Commands\Command;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;

class InitCommand extends Command
{
    protected $signature = 'init {file? : Path to the configuration file}';

    protected $description = 'Initialize a new configuration file.';

    /**
     * Execute the console command.
     */
    public function handle(MaximumByIncome\InputSerializer $inputSerializer): int
    {
        $config = $inputSerializer->serialize(new MaximumByIncome\Input(
            calculationDate: Carbon::now(),
            mainPerson: new MaximumByIncome\Person(
                dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
                yearlyIncome: new Number(0),
            )
        ));

        $header = config('mortgage-calc.ini_header', '');

        $contents = <<<INI
            $header

            ;; -------------------------------------------------------------------------------- ;;
            ;;   MAXIMUM HYPOTHEEK BEREKENEN - CONFIGURATIE BESTAND                             ;;
            ;; -------------------------------------------------------------------------------- ;;
            ;;                                                                                  ;;
            ;; Dit is een configuratie bestand om de maximale hypotheek te berekenen op basis   ;;
            ;; het inkomen. Vul de gegevens in dit bestand aan en activeer het commando met     ;;
            ;;                                                                                  ;;
            ;;     nhb-assessment --file {pad_naar_did_bestand}                                 ;;
            ;;                                                                                  ;;
            ;; Om de maximale hypotheek te berekenen.                                           ;;
            ;; -------------------------------------------------------------------------------- ;;

            $config
            INI;


        $file = $this->argument('file');
        if($file) {
            $dir = dirname($file);
            if($dir !== '' && !file_exists($dir)) mkdir($dir, 0755, true);
            file_put_contents($file, $contents);
            $this->line("Nieuw configuratiebestand geïnitialiseerd: $file");
        } else {
            $this->output->writeln($contents);
        }
        return 0;
    }
}
