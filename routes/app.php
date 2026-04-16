<?php

use App\Models\Tenant;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Http\Controllers\Workspace\DashboardController;
use App\Http\Controllers\Workspace\BookingController;
use App\Http\Controllers\Workspace\CashflowController;
use App\Http\Controllers\Workspace\CustomerBlastController;
use App\Http\Controllers\Workspace\CustomerController;
use App\Http\Controllers\Workspace\CustomerImportController;
use App\Http\Controllers\Workspace\NewsletterController;
use App\Http\Controllers\Workspace\PaymentController;
use App\Http\Controllers\Workspace\PackageController;
use App\Http\Controllers\Workspace\QuotationController;
use App\Http\Controllers\Workspace\InvoiceController;
use App\Http\Controllers\Workspace\SettingsController;
use App\Http\Controllers\Workspace\TourSetupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('/workspace/{tenant:slug}')
    ->middleware(['auth', 'tenant.workspace'])
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding.update');

        Route::get('/tour-setup', TourSetupController::class)->name('tour-setup.index');

        Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
        Route::get('/packages/export-csv', [PackageController::class, 'exportCsv'])->name('packages.export-csv');
        Route::delete('/packages/bulk-delete', [PackageController::class, 'bulkDelete'])->name('packages.bulk-delete');
        Route::get('/packages/create', [PackageController::class, 'create'])->name('packages.create');
        Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::get('/packages/{package}', [PackageController::class, 'show'])->name('packages.show');
        Route::get('/packages/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
        Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::post('/packages/{package}/toggle-status', [PackageController::class, 'toggleStatus'])->name('packages.toggle-status');
        Route::post('/packages/{package}/duplicate', [PackageController::class, 'duplicate'])->name('packages.duplicate');
        Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/reports', function (Tenant $tenant) {
            $bookings = Booking::query()->with('package')->get();
            $packages = Package::query()->get();

            return Inertia::render('Workspace/Reports/IndexPage', [
                'workspace' => $tenant->only(['id', 'name', 'slug']),
                'metrics' => [
                    'booking_count' => $bookings->count(),
                    'paid_bookings' => $bookings->where('payment_status', Booking::PAYMENT_STATUS_PAID)->count(),
                    'pending_bookings' => $bookings->where('booking_status', Booking::BOOKING_STATUS_PENDING)->count(),
                    'cancelled_bookings' => $bookings->where('booking_status', Booking::BOOKING_STATUS_CANCELLED)->count(),
                    'revenue' => $bookings->where('payment_status', Booking::PAYMENT_STATUS_PAID)->sum(fn (Booking $booking) => (float) ($booking->total_price ?? 0)),
                    'average_package_price' => $packages->count() ? round($packages->avg('price'), 2) : 0,
                ],
                'packageBreakdown' => $packages
                    ->groupBy('type')
                    ->map(fn ($group, $type) => [
                        'type' => $type,
                        'total' => $group->count(),
                        'revenue' => round($group->sum('price'), 2),
                    ])
                    ->values()
                    ->all(),
            ]);
        })->name('reports.index');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::post('/customers/selection-token', [CustomerController::class, 'createSelectionToken'])->name('customers.selection-token');
        Route::get('/customers/export-selected', [CustomerController::class, 'exportSelected'])->name('customers.export-selected');
        Route::delete('/customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulk-delete');
        Route::post('/customers/bulk-delete/undo', [CustomerController::class, 'undoBulkDelete'])->name('customers.bulk-delete.undo');
        Route::post('/customers/import', [CustomerImportController::class, 'store'])->name('customers.import.store');
        Route::get('/customers/import/sample', [CustomerImportController::class, 'sample'])->name('customers.import.sample');
        Route::get('/customers/import/failures', [CustomerImportController::class, 'failuresReport'])->name('customers.import.failures');
        Route::get('/customers/audience/export', [CustomerController::class, 'exportAudience'])->name('customers.audience.export');
        Route::get('/customers/blast', [CustomerBlastController::class, 'index'])->name('customers.blast.index');
        Route::post('/customers/blast/selection-token', [CustomerBlastController::class, 'createSelectionToken'])->name('customers.blast.selection-token');
        Route::post('/customers/blast/draft', [CustomerBlastController::class, 'saveDraft'])->name('customers.blast.draft.save');
        Route::post('/customers/blast/log', [CustomerBlastController::class, 'logBlast'])->name('customers.blast.log');
        Route::get('/customers/blast/logs', [CustomerBlastController::class, 'listLogs'])->name('customers.blast.logs');
        Route::get('/customers/blast/export-selected', [CustomerBlastController::class, 'exportSelected'])->name('customers.blast.export-selected');
        Route::post('/customers/blast/templates', [CustomerBlastController::class, 'storeTemplate'])->name('customers.blast.templates.store');
        Route::put('/customers/blast/templates/{template}', [CustomerBlastController::class, 'updateTemplate'])->name('customers.blast.templates.update');
        Route::delete('/customers/blast/templates/{template}', [CustomerBlastController::class, 'destroyTemplate'])->name('customers.blast.templates.destroy');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/export-csv', [BookingController::class, 'exportCsv'])->name('bookings.export-csv');
        Route::delete('/bookings/bulk-delete', [BookingController::class, 'bulkDelete'])->name('bookings.bulk-delete');
        Route::get('/tourism-letters', [BookingController::class, 'tourismLettersIndex'])->name('tourism-letters.index');
        Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store'])->name('bookings.payments.store');
        Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
        Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::get('/bookings/{booking}/tourism-letter', [BookingController::class, 'previewTourismLetter'])->name('bookings.tourism-letter.preview');
        Route::get('/bookings/{booking}/tourism-letter/edit', [BookingController::class, 'tourismLetterEdit'])->name('bookings.tourism-letter.edit');
        Route::put('/bookings/{booking}/tourism-letter', [BookingController::class, 'updateTourismLetter'])->name('bookings.tourism-letter.update');
        Route::get('/bookings/{booking}/tourism-letter/download', [BookingController::class, 'downloadTourismLetter'])->name('bookings.tourism-letter.download');
        Route::post('/bookings/{booking}/tourism-letter/email', [BookingController::class, 'sendTourismLetterEmail'])->name('bookings.tourism-letter.email');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');

        // New Quotation Routes
        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/export-csv', [QuotationController::class, 'exportCsv'])->name('quotations.export-csv');
        Route::delete('/quotations/bulk-delete', [QuotationController::class, 'bulkDelete'])->name('quotations.bulk-delete');
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');

        // New Invoice Routes
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/export-csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export-csv');
        Route::get('/invoices/financial-export', [CashflowController::class, 'financialExport'])->name('cashflow.financial-export');
        Route::get('/invoices/collection-actions-export', [CashflowController::class, 'collectionActionsExport'])->name('cashflow.collection-actions-export');
        Route::delete('/invoices/bulk-delete', [InvoiceController::class, 'bulkDelete'])->name('invoices.bulk-delete');
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/payment', [InvoiceController::class, 'recordPayment'])->name('invoices.record-payment');
        Route::post('/invoices/{invoice}/mark-collection-action', [CashflowController::class, 'markCollectionAction'])->name('cashflow.mark-collection-action');
        Route::get('/quotations/{quotation}/convert', [InvoiceController::class, 'convertFromQuote'])->name('quotations.convert');

        // Newsletters
        Route::get('/newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
        Route::get('/newsletters/create', [NewsletterController::class, 'create'])->name('newsletters.create');
        Route::post('/newsletters', [NewsletterController::class, 'store'])->name('newsletters.store');
        Route::get('/newsletters/{newsletter}', [NewsletterController::class, 'show'])->name('newsletters.show');
        Route::get('/newsletters/{newsletter}/edit', [NewsletterController::class, 'edit'])->name('newsletters.edit');
        Route::put('/newsletters/{newsletter}', [NewsletterController::class, 'update'])->name('newsletters.update');
        Route::post('/newsletters/{newsletter}/send', [NewsletterController::class, 'send'])->name('newsletters.send');
        Route::post('/newsletters/{newsletter}/duplicate', [NewsletterController::class, 'duplicate'])->name('newsletters.duplicate');
        Route::delete('/newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');

        // Cashflow Command Center
        Route::get('/cashflow-command-center', [CashflowController::class, 'index'])->name('cashflow.index');
    });
