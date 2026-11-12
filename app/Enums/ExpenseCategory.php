<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Bensin = 'bensin';
    case Konsumsi = 'konsumsi';
    case Gaji = 'gaji';
    case Lainnya = 'lainnya';
}
