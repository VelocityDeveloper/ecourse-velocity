<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    /**
     * List confirmed payments with revenue totals.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $transactions = $this->filtered($request)
            ->with(['order:id,number,course_id,course_title', 'user:id,name,email,avatar_path', 'confirmer:id,name'])
            ->latest('paid_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Transaction $transaction): array => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'payment_details' => $transaction->payment_details,
                'paid_at' => $transaction->paid_at->toIso8601String(),
                'order' => $transaction->order->only(['number', 'course_id', 'course_title']),
                'student' => $transaction->user->only(['id', 'name', 'email', 'avatar']),
                'confirmer' => $transaction->confirmer?->only(['id', 'name']),
            ]);

        return Inertia::render('admin/Transactions/Index', [
            'transactions' => $transactions,
            'summary' => [
                'filtered_total' => (int) $this->filtered($request)->sum('amount'),
                'filtered_count' => $this->filtered($request)->count(),
                'today' => (int) Transaction::query()->where('paid_at', '>=', today())->sum('amount'),
                'this_month' => (int) Transaction::query()->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'all_time' => (int) Transaction::query()->sum('amount'),
            ],
            'filters' => $request->only(['from', 'to', 'search']),
        ]);
    }

    /**
     * Download the filtered payments as a CSV file that opens in Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $transactions = $this->filtered($request)->with(['order:id,number,course_title', 'user:id,name,email', 'confirmer:id,name'])->latest('paid_at')->get();

        return response()->streamDownload(function () use ($transactions): void {
            $out = fopen('php://output', 'w');
            assert($out !== false);

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal bayar', 'Nomor pesanan', 'Siswa', 'Email', 'Kursus', 'Metode', 'Rekening/QRIS', 'Jumlah (Rp)', 'Dikonfirmasi oleh'], ';');

            foreach ($transactions as $transaction) {
                $details = $transaction->payment_details ?? [];

                fputcsv($out, [
                    $transaction->paid_at->format('Y-m-d H:i'),
                    $transaction->order->number,
                    $transaction->user->name,
                    $transaction->user->email,
                    $transaction->order->course_title,
                    $transaction->payment_method === 'qris' ? 'QRIS' : 'Transfer bank',
                    isset($details['bank']) ? "{$details['bank']} {$details['account_number']}" : ($details['qris_name'] ?? ''),
                    $transaction->amount,
                    $transaction->confirmer->name ?? '',
                ], ';');
            }

            fclose($out);
        }, 'transaksi-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Payments narrowed by the date range and search of the request.
     *
     * @return Builder<Transaction>
     */
    private function filtered(Request $request): Builder
    {
        $search = $request->string('search')->toString();

        return Transaction::query()
            ->when($request->filled('from'), fn (Builder $query) => $query->where('paid_at', '>=', Carbon::parse($request->string('from')->toString())->startOfDay()))
            ->when($request->filled('to'), fn (Builder $query) => $query->where('paid_at', '<=', Carbon::parse($request->string('to')->toString())->endOfDay()))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereHas('order', fn (Builder $order) => $order
                    ->where('number', 'like', "%{$search}%")
                    ->orWhere('course_title', 'like', "%{$search}%"))
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))));
    }
}
