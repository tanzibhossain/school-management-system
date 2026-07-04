<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\PayrollEntry;
use App\Modules\School\Models\School;
use App\Services\PdfRenderingService;

/**
 * On-demand payslip generation — "Finance can call this at any time after
 * [the] run is processed", per the DevPlan. There's no separate "processed"
 * guard beyond the entry existing: a PayrollEntry row is only ever created by
 * PayrollService::processRun(), so a resolvable $entryId already implies the
 * run has been processed (approval is not required first).
 */
class PayslipService
{
    public function __construct(private readonly PdfRenderingService $pdf) {}

    public function generate(int $schoolId, PayrollEntry $entry): PayrollEntry
    {
        $entry->loadMissing(['staff.designation', 'staff.department', 'run']);
        $school = School::findOrFail($schoolId);

        $html = $this->buildHtml($school, $entry);
        $path = $this->pdf->generateAndStore(
            $html,
            "{$schoolId}/payroll/{$entry->run->year}/{$entry->run->month}/{$entry->staff_id}.pdf",
        );

        $entry->update(['payslip_path' => $path, 'payslip_generated_at' => now()]);

        return $entry->fresh();
    }

    private function buildHtml(School $school, PayrollEntry $entry): string
    {
        $rows = '';
        foreach ($entry->breakdown ?? [] as $line) {
            $sign = $line['type'] === 'earning' ? '+' : '-';
            $label = e($line['label']);
            $amount = number_format((float) $line['amount'], 2);
            $rows .= "<tr><td>{$label}</td><td>{$sign} {$amount}</td></tr>";
        }

        $schoolName = e($school->name);
        $staffName = e($entry->staff->name);
        $designation = e($entry->staff->designation?->name ?? '-');
        $period = e($entry->run->month.'/'.$entry->run->year);
        $currency = e($school->currency);
        $gross = number_format((float) $entry->gross_salary, 2);
        $deductions = number_format((float) $entry->total_deductions, 2);
        $net = number_format((float) $entry->net_salary, 2);

        return <<<HTML
            <html>
            <head><style>
                body { font-family: sans-serif; }
                table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
                h2 { margin-bottom: 0; }
            </style></head>
            <body>
                <h2>{$schoolName}</h2>
                <h3>Payslip — {$period}</h3>
                <p><strong>Staff:</strong> {$staffName} ({$designation})</p>
                <table>
                    <thead><tr><th>Component</th><th>Amount ({$currency})</th></tr></thead>
                    <tbody>{$rows}</tbody>
                </table>
                <p><strong>Gross Salary:</strong> {$currency} {$gross}</p>
                <p><strong>Total Deductions:</strong> {$currency} {$deductions}</p>
                <p><strong>Net Salary:</strong> {$currency} {$net}</p>
            </body>
            </html>
            HTML;
    }
}
