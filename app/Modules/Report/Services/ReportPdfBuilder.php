<?php

namespace App\Modules\Report\Services;

use App\Modules\School\Models\School;

/**
 * Builds report HTML for PDF export (?format=pdf on the report endpoints).
 * Rendered straight through PdfRenderingService::renderToPdf() and streamed
 * back in the HTTP response — unlike Certificate/IdCard, nothing here is
 * stored to MinIO or logged to a DB row. A report is a live, filter-driven
 * snapshot, not an official document with its own history to keep.
 */
class ReportPdfBuilder
{
    /** @param  array{payments: array<int, array<string, mixed>>, summary: array<string, mixed>}  $data */
    public function feeCollectionHtml(array $data, School $school, string $dateFrom, string $dateTo): string
    {
        $rows = '';
        foreach ($data['payments'] as $payment) {
            $classSection = trim(($payment['class_name'] ?? '').($payment['section_name'] ? " - {$payment['section_name']}" : ''));
            $rows .= '<tr>'
                .'<td>'.e($payment['paid_at']).'</td>'
                .'<td>'.e($payment['receipt_number']).'</td>'
                .'<td>'.e($payment['student_name']).'</td>'
                .'<td>'.e($classSection).'</td>'
                .'<td>'.e((string) $payment['amount']).' '.e($payment['currency']).'</td>'
                .'<td>'.e($payment['method']).'</td>'
                .'</tr>';
        }

        $totals = '';
        foreach ($data['summary']['totals_by_currency'] as $currency => $amount) {
            $totals .= '<div>Total ('.e((string) $currency).'): '.e((string) $amount).'</div>';
        }

        return $this->wrap($school, "Fee Collection Report ({$dateFrom} to {$dateTo})", <<<HTML
            <table>
                <thead><tr><th>Date</th><th>Receipt</th><th>Student</th><th>Class</th><th>Amount</th><th>Method</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
            <div class="totals">{$totals}</div>
            HTML);
    }

    /** @param  array{students: array<int, array<string, mixed>>, summary: array<string, mixed>}  $data */
    public function outstandingDuesHtml(array $data, School $school): string
    {
        $rows = '';
        foreach ($data['students'] as $student) {
            $classSection = trim(($student['class_name'] ?? '').($student['section_name'] ? " - {$student['section_name']}" : ''));
            $rows .= '<tr>'
                .'<td>'.e($student['student_name']).'</td>'
                .'<td>'.e($classSection).'</td>'
                .'<td>'.e((string) $student['invoice_count']).'</td>'
                .'<td>'.e((string) $student['oldest_due_date']).'</td>'
                .'<td>'.e((string) $student['total_due']).' '.e($student['currency']).'</td>'
                .'</tr>';
        }

        $totals = '';
        foreach ($data['summary']['totals_by_currency'] as $currency => $amount) {
            $totals .= '<div>Total Outstanding ('.e((string) $currency).'): '.e((string) $amount).'</div>';
        }

        return $this->wrap($school, 'Outstanding Dues Report', <<<HTML
            <table>
                <thead><tr><th>Student</th><th>Class</th><th>Invoices Due</th><th>Oldest Due Date</th><th>Total Due</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
            <div class="totals">{$totals}</div>
            HTML);
    }

    /** @param  array{entries: array<int, array<string, mixed>>, summary: array<string, mixed>}  $data */
    public function studentLedgerHtml(array $data, School $school, string $studentName): string
    {
        $rows = '';
        foreach ($data['entries'] as $entry) {
            $rows .= '<tr>'
                .'<td>'.e((string) $entry['date']).'</td>'
                .'<td>'.e($entry['type']).'</td>'
                .'<td>'.e($entry['description']).'</td>'
                .'<td>'.e((string) $entry['amount']).' '.e((string) ($entry['currency'] ?? '')).'</td>'
                .'</tr>';
        }

        $summary = $data['summary'];
        $summaryHtml = '<div>Total Invoiced: '.e((string) $summary['total_invoiced']).'</div>'
            .'<div>Total Paid: '.e((string) $summary['total_paid']).'</div>'
            .'<div>Total Refunded: '.e((string) $summary['total_refunded']).'</div>'
            .'<div>Current Outstanding: '.e((string) $summary['current_outstanding']).'</div>'
            .'<div>Credit Balance: '.e((string) $summary['credit_balance']).'</div>';

        return $this->wrap($school, 'Student Financial Ledger — '.$studentName, <<<HTML
            <table>
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
            <div class="totals">{$summaryHtml}</div>
            HTML);
    }

    private function wrap(School $school, string $title, string $bodyHtml): string
    {
        $schoolName = e($school->name);
        $title = e($title);

        return <<<HTML
            <html>
            <head><style>
                body { font-family: sans-serif; font-size: 11px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
                .totals { margin-top: 10px; font-weight: bold; }
                h2 { margin-bottom: 0; }
            </style></head>
            <body>
                <h2>{$schoolName}</h2>
                <h3>{$title}</h3>
                {$bodyHtml}
            </body>
            </html>
            HTML;
    }
}
