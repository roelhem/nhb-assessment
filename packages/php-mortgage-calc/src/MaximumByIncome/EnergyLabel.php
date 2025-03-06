<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;

enum EnergyLabel: string
{
    case Apppp_WithEnergyPerformanceGuarantee = 'A++++_WITH_ENERGY_PERFORMANCE_GUARANTEE';
    case Apppp = 'A++++';
    case Appp = 'A+++';
    case App = 'A++';
    case Ap = 'A+';
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';
    case F = 'F';
    case G = 'G';

    const self DEFAULT = self::E;
}
