<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\DesignerCashbackTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CashbackController extends Controller
{
    public function index(Request $request): View
    {
        $userId = (int) $request->user()->id;
        $period = $this->resolvePeriod((string) $request->query('period', '30d'));
        $from = $this->periodStart($period);

        $daily = DesignerCashbackTransaction::dailyAccruals($userId, $period['days']);
        $maxDaily = max(1, ...array_column($daily, 'amount'));

        $transactions = DesignerCashbackTransaction::query()
            ->where('user_id', $userId)
            ->with(['order:id,supplier_id', 'order.supplier:id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('designer.profile.cashback', [
            'available' => DesignerCashbackTransaction::availableBalance($userId),
            'totalEarned' => DesignerCashbackTransaction::totalEarned($userId),
            'totalWithdrawn' => DesignerCashbackTransaction::totalWithdrawn($userId),
            'periodEarned' => DesignerCashbackTransaction::earnedInPeriod($userId, $from),
            'period' => $period['key'],
            'daily' => $daily,
            'maxDaily' => $maxDaily,
            'transactions' => $transactions,
        ]);
    }

    public function withdrawForm(Request $request): View|RedirectResponse
    {
        $available = DesignerCashbackTransaction::availableBalance((int) $request->user()->id);

        if ($available <= 0) {
            return redirect()
                ->route('profile.cashback')
                ->with('error', __('cashback.withdraw_no_balance'));
        }

        return view('designer.profile.cashback-withdraw', [
            'available' => $available,
        ]);
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $available = DesignerCashbackTransaction::availableBalance($userId);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$available],
            'payment_method' => ['required', 'string', 'in:card,bank'],
            'payment_details' => ['required', 'string', 'max:500'],
        ]);

        DesignerCashbackTransaction::create([
            'user_id' => $userId,
            'type' => DesignerCashbackTransaction::TYPE_WITHDRAWAL,
            'amount' => (int) $data['amount'],
            'status' => DesignerCashbackTransaction::STATUS_COMPLETED,
            'description' => __('cashback.withdrawal_description'),
            'meta' => [
                'payment_method' => $data['payment_method'],
                'payment_details' => trim($data['payment_details']),
            ],
        ]);

        return redirect()
            ->route('profile.cashback')
            ->with('success', __('cashback.withdraw_success'));
    }

    /**
     * @return array{key: string, days: int, label: string}
     */
    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            '24h' => ['key' => '24h', 'days' => 1, 'label' => '24h'],
            '7d' => ['key' => '7d', 'days' => 7, 'label' => '7d'],
            '90d' => ['key' => '90d', 'days' => 90, 'label' => '90d'],
            default => ['key' => '30d', 'days' => 30, 'label' => '30d'],
        };
    }

    private function periodStart(array $period): Carbon
    {
        return match ($period['key']) {
            '24h' => now()->subDay(),
            default => now()->subDays($period['days'])->startOfDay(),
        };
    }
}
