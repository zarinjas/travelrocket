<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function store(Request $request, Tenant $tenant, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,gateway'],
            'payment_date' => ['required', 'date'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $receiptPath = null;
        $receiptFile = $validated['receipt'] ?? null;

        if ($receiptFile) {
            $directory = "receipts/{$tenant->id}";
            $extension = strtolower((string) $receiptFile->getClientOriginalExtension());
            $fileName = hash('sha256', $tenant->id.'|'.Str::uuid()->toString().'|'.microtime(true)).($extension ? ".{$extension}" : '');
            $receiptPath = $receiptFile->storeAs($directory, $fileName, 'public');
        }

        DB::transaction(function () use ($validated, $booking, $receiptPath): void {
            $payment = Payment::query()->create([
                'booking_id' => $booking->id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'receipt_path' => $receiptPath,
            ]);

            $booking->refresh();
            $totalPaid = (float) $booking->payments()->sum('amount');
            $totalPrice = (float) $booking->total_price;
            $balanceDue = max(0, $totalPrice - $totalPaid);

            $paymentStatus = Booking::PAYMENT_STATUS_UNPAID;
            if ($balanceDue <= 0 && $totalPrice > 0) {
                $paymentStatus = Booking::PAYMENT_STATUS_PAID;
            } elseif ($totalPaid > 0) {
                $paymentStatus = Booking::PAYMENT_STATUS_PARTIAL;
            }

            $booking->update([
                'balance_due' => $balanceDue,
                'payment_status' => $paymentStatus,
            ]);
        });

        return redirect()
            ->route('bookings.edit', ['tenant' => $tenant, 'booking' => $booking])
            ->with('success', 'Payment recorded successfully.');
    }
}
