<?php

namespace App\Http\Controllers\Portal;

use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Family-portal online fee payment. Starts a gateway payment for an invoice the
 * family owns and handles the browser return. The gateway calls, execution and
 * Payment recording live in the Payment module services — this just drives them
 * from the portal and returns the family to the fees page.
 */
class PaymentController extends Controller
{
    /** Kick off a gateway payment and redirect the browser to the gateway. */
    public function initiate(Request $request, PaymentService $service): RedirectResponse
    {
        $sid = app('current_school_id');
        $data = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'gateway'    => ['required', 'in:bkash,sslcommerz'],
        ]);

        $config = PaymentConfig::firstOrCreate(['school_id' => $sid]);
        $gateways = collect($config->enabledGateways());
        if (! $config->onlineEnabled() || ! $gateways->contains(fn ($g) => $g['key'] === $data['gateway'])) {
            return back()->with('error', 'Online payment is not available right now.');
        }

        // The invoice must belong to a student in this family.
        $invoice = Invoice::where('school_id', $sid)->where('id', $data['invoice_id'])
            ->whereIn('student_id', $this->allowedStudentIds($sid))->first();

        if (! $invoice) {
            return back()->with('error', 'Invoice not found.');
        }
        if ($invoice->remainingAmount() <= 0) {
            return back()->with('error', 'This invoice is already settled.');
        }

        try {
            if ($data['gateway'] === 'sslcommerz') {
                $result = $service->initiateSslcommerz(
                    $invoice,
                    route('portal.pay.sslcommerz.return', ['result' => 'success']),
                    route('portal.pay.sslcommerz.return', ['result' => 'fail']),
                    route('portal.pay.sslcommerz.return', ['result' => 'cancel']),
                    route('payment.sslcommerz.ipn'),
                );

                return redirect()->away($result['GatewayPageURL']);
            }

            $result = $service->initiateBkash($invoice, route('portal.pay.bkash.callback'));

            return redirect()->away($result['bkashURL']);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not start the online payment. Please try again, or pay at the office.');
        }
    }

    /**
     * SSLCommerz returns the browser here (public — the gateway POSTs the result).
     * The invoice is resolved from tran_id (= invoice_number); the family session
     * cookie may be dropped on this cross-site POST, so we never rely on auth here.
     */
    public function sslcommerzReturn(Request $request, PaymentService $service, string $result): RedirectResponse
    {
        if ($result !== 'success') {
            return redirect()->route('portal.fees')
                ->with('error', $result === 'cancel' ? 'Payment was cancelled.' : 'Payment failed.');
        }

        $tranId = $request->input('tran_id');
        $valId  = $request->input('val_id');
        $status = $request->input('status');

        if (! $tranId || ! $valId || ! in_array($status, ['VALID', 'VALIDATED'], true)) {
            return redirect()->route('portal.fees')->with('error', 'Payment could not be verified.');
        }

        $invoice = Invoice::where('invoice_number', $tranId)->first();
        if (! $invoice) {
            return redirect()->route('portal.fees')->with('error', 'Invoice not found for this payment.');
        }

        try {
            $service->verifySslcommerz($invoice, $valId);

            return redirect()->route('portal.fees')->with('status', 'Payment successful — thank you!');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('portal.fees')
                ->with('error', 'We could not confirm your payment. If your account was debited, please contact the office.');
        }
    }

    /** bKash returns the browser here (public — the gateway drives the redirect). */
    public function bkashCallback(Request $request, PaymentService $service): RedirectResponse
    {
        $paymentId = $request->query('paymentID');
        $status = $request->query('status');

        if ($status !== 'success' || ! $paymentId) {
            return redirect()->route('portal.fees')->with('error', 'Payment was not completed.');
        }

        $cached = Cache::get("bkash_payment:{$paymentId}");
        if (! $cached) {
            return redirect()->route('portal.fees')->with('error', 'Payment session expired — please try again.');
        }

        try {
            $service->executeBkash($paymentId, $cached['invoice_id'], $cached['school_id']);

            return redirect()->route('portal.fees')->with('status', 'Payment successful — thank you!');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('portal.fees')
                ->with('error', 'We could not confirm your payment. If your account was debited, please contact the office.');
        }
    }

    /** Student IDs the logged-in family member may pay for. */
    private function allowedStudentIds(int $sid): Collection
    {
        $user = auth()->user();

        if ($user->hasRole('parent')) {
            return StudentGuardian::where('school_id', $sid)->where('user_id', $user->id)->pluck('student_id');
        }

        return Student::where('school_id', $sid)->where('user_id', $user->id)->pluck('id');
    }
}
