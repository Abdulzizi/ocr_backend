<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class CreditService.
 */
class CreditService
{
    protected $userModel;
    protected $creditTransactionModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->creditTransactionModel = new CreditTransaction();
    }

    public function hasEnoughCredits(int $userId, int $required = 1): bool
    {
        $user = User::find($userId);
        return $user && $user->credits >= $required;
    }

    public function deductCredits(int $userId, int $amount, string $type = 'ocr_usage', ?string $description = null): void
    {
        DB::transaction(function () use ($userId, $amount, $type, $description) {
            $user = User::lockForUpdate()->find($userId);

            if (!$user) {
                throw new Exception('User not found.');
            }

            if ($user->credits < $amount) {
                throw new Exception('Insufficient credits.');
            }

            $user->decrement('credits', $amount);

            CreditTransaction::create([
                'user_id' => $userId,
                'amount' => -$amount,
                'type' => $type,
                'description' => $description ?? 'Used ' . $amount . ' credits for OCR',
            ]);
        });
    }

    public function addCredits(int $userId, int $amount, string $type = 'admin_add', ?string $description = null): void
    {
        DB::transaction(function () use ($userId, $amount, $type, $description) {
            $user = User::lockForUpdate()->find($userId);

            if (!$user) {
                throw new Exception('User not found.');
            }

            $user->increment('credits', $amount);

            CreditTransaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
                'description' => $description ?? 'Added ' . $amount . ' credits',
            ]);
        });
    }

    public function getBalance(int $userId): int
    {
        $user = User::find($userId);
        return $user ? $user->credits : 0;
    }
}
