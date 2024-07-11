<?php

namespace App\Services;

use App\Models\Balance;

class BalanceService
{
    public function __construct(
        public $team_id,
    )
    {
    }

    public function addBalance($amount, $meta = []): self
    {
        Balance::create([
            'amount' => $amount,
            'team_id' => $this->team_id,
            'meta' => $meta,
        ]);
        
        return $this;
    }

    public function subtractBalance($amount, $meta = []): self
    {
        $this->addBalance(-abs($amount), $meta);

        return $this;
    }

    public function total(): float
    {
        return (float) Balance::where('team_id', $this->team_id)->sum('amount');
    }
}