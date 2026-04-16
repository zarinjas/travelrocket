<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportController extends Controller
{
    public function sample(Tenant $tenant): StreamedResponse
    {
        $headers = [
            'name',
            'passport_number',
            'address',
            'email',
            'phone',
            'emergency_name',
            'emergency_phone',
            'emergency_relation',
            'emergency_address',
            'tags',
            'allow_marketing',
        ];

        $sampleRow = [
            'Ahmad Bin Ali',
            'A12345678',
            'No 12, Jalan Mawar, Kuala Lumpur',
            'ahmad@example.com',
            '60123456789',
            'Siti Binti Ali',
            '60199887766',
            'Spouse',
            'No 12, Jalan Mawar, Kuala Lumpur',
            'Umrah|Domestic',
            '1',
        ];

        return response()->streamDownload(function () use ($headers, $sampleRow): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            fputcsv($output, $sampleRow);
            fclose($output);
        }, 'customer-import-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $handle = fopen($validated['csv_file']->getRealPath(), 'r');

        if (! $handle) {
            return back()->with('error', 'Unable to read CSV file.');
        }

        $header = fgetcsv($handle);

        if (! is_array($header)) {
            fclose($handle);

            return back()->with('error', 'CSV file is empty or invalid.');
        }

        $normalizedHeader = array_map(fn ($column) => trim((string) $column), $header);
        $required = [
            'name',
            'passport_number',
            'address',
            'phone',
            'emergency_name',
            'emergency_phone',
            'emergency_relation',
            'emergency_address',
        ];

        foreach ($required as $column) {
            if (! in_array($column, $normalizedHeader, true)) {
                fclose($handle);

                return back()->with('error', "Missing required CSV column: {$column}");
            }
        }

        $rows = [];
        $failures = [];
        $seenPassportNumbers = [];
        $processedRows = 0;
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $processedRows++;

            $row = [];
            foreach ($normalizedHeader as $index => $column) {
                $row[$column] = trim((string) ($data[$index] ?? ''));
            }

            $payload = [
                'tenant_id' => $tenant->id,
                'name' => $row['name'] ?? '',
                'full_name' => $row['name'] ?? '',
                'passport_number' => $row['passport_number'] ?? '',
                'document_no' => $row['passport_number'] ?? '',
                'address' => $row['address'] ?? '',
                'email' => $row['email'] !== '' ? ($row['email'] ?? null) : null,
                'phone' => $row['phone'] ?? '',
                'emergency_name' => $row['emergency_name'] ?? '',
                'emergency_phone' => $row['emergency_phone'] ?? '',
                'emergency_relation' => $row['emergency_relation'] ?? '',
                'emergency_address' => $row['emergency_address'] ?? '',
                'tags' => collect(explode('|', (string) ($row['tags'] ?? '')))
                    ->map(fn ($tag) => trim((string) $tag))
                    ->filter(fn ($tag) => $tag !== '')
                    ->values()
                    ->all(),
                'allow_marketing' => ! isset($row['allow_marketing'])
                    ? true
                    : in_array(strtolower((string) $row['allow_marketing']), ['1', 'true', 'yes', 'y'], true),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $validator = validator($payload, [
                'tenant_id' => ['required', 'integer'],
                'name' => ['required', 'string', 'max:255'],
                'full_name' => ['required', 'string', 'max:255'],
                'passport_number' => ['required', 'string', 'max:100'],
                'document_no' => ['required', 'string', 'max:100'],
                'address' => ['required', 'string'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:50'],
                'emergency_name' => ['required', 'string', 'max:255'],
                'emergency_phone' => ['required', 'string', 'max:50'],
                'emergency_relation' => ['required', 'string', 'max:100'],
                'emergency_address' => ['required', 'string'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string', 'max:50'],
                'allow_marketing' => ['required', 'boolean'],
            ]);

            if ($validator->fails()) {
                $failures[] = [
                    'row' => $line,
                    'name' => $payload['name'],
                    'passport_number' => $payload['passport_number'],
                    'reason' => $validator->errors()->first(),
                ];

                continue;
            }

            $passportKey = strtolower((string) $payload['passport_number']);

            if (isset($seenPassportNumbers[$passportKey])) {
                $failures[] = [
                    'row' => $line,
                    'name' => $payload['name'],
                    'passport_number' => $payload['passport_number'],
                    'reason' => 'Duplicate passport_number found in CSV file.',
                ];

                continue;
            }

            $seenPassportNumbers[$passportKey] = true;

            $rows[] = [
                'row' => $line,
                'payload' => $payload,
            ];
        }

        fclose($handle);

        if (count($rows) === 0) {
            return back()
                ->with('error', 'No valid rows found to import.')
                ->with('import_report', [
                    'total_rows' => $processedRows,
                    'imported_count' => 0,
                    'failed_count' => count($failures),
                        'failures' => $failures,
                ]);
        }

        $existingPassports = Customer::query()
            ->where('tenant_id', $tenant->id)
                    ->whereIn('passport_number', array_map(fn (array $row) => $row['payload']['passport_number'], $rows))
            ->pluck('passport_number')
            ->map(fn ($passport) => strtolower((string) $passport))
            ->values()
            ->all();

        $existingLookup = array_fill_keys($existingPassports, true);
        $importRows = [];

        foreach ($rows as $rowMeta) {
            $row = $rowMeta['payload'];
            $passportKey = strtolower((string) $row['passport_number']);

            if (isset($existingLookup[$passportKey])) {
                $failures[] = [
                    'row' => $rowMeta['row'],
                    'name' => $row['name'],
                    'passport_number' => $row['passport_number'],
                    'reason' => 'passport_number already exists for this tenant: '.$row['passport_number'],
                ];

                continue;
            }

            $importRows[] = $row;
        }

        if (count($importRows) === 0) {
            return back()
                ->with('error', 'Import cancelled. All rows are duplicates or invalid.')
                ->with('import_report', [
                    'total_rows' => $processedRows,
                    'imported_count' => 0,
                    'failed_count' => count($failures),
                    'failures' => $failures,
                ]);
        }

        DB::transaction(function () use ($importRows): void {
            $chunks = array_chunk($importRows, 500);

            foreach ($chunks as $chunk) {
                Customer::query()->insert($chunk);
            }
        });

        return back()
            ->with('success', count($importRows).' customers imported successfully.')
            ->with('import_report', [
                'total_rows' => $processedRows,
                'imported_count' => count($importRows),
                'failed_count' => count($failures),
                'failures' => $failures,
            ]);
    }

    public function failuresReport(Request $request, Tenant $tenant): StreamedResponse
    {
        $report = $request->session()->get('import_report', []);
        $failures = $report['failures'] ?? [];

        return response()->streamDownload(function () use ($failures): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['row', 'name', 'passport_number', 'reason']);

            foreach ($failures as $failure) {
                fputcsv($output, [
                    $failure['row'] ?? '',
                    $failure['name'] ?? '',
                    $failure['passport_number'] ?? '',
                    $failure['reason'] ?? '',
                ]);
            }

            fclose($output);
        }, 'customer-import-failures.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
