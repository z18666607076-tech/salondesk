<?php

namespace App\Ai\Contracts;

use App\Ai\BookingProposal;

interface BookingAssistant
{
    public function propose(string $message): BookingProposal;
}
