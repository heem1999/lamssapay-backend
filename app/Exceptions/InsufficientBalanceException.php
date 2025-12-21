<?php

namespace App\Exceptions;

class InsufficientBalanceException extends WalletException
{
    protected $message = 'Insufficient balance for this transaction.';
}
