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
            'gateway' => ['required', 'in:bkash,sslcommerz,stripe,paypal'],
        ]);

        $config = PaymentConfig::firstOrCreate(['school_id' => $sid]);
        $gateways = collect($config->enabledGateways());
        if (! $config->onlineEnabled() || ! $gateways->contains(fn ($g) => $g['key'] === $data['gateway'])) {
            return back()->with('error', __('Online Payment Is Not Available Right Now.'));
        }

        // The invoice must belong to a student in this family.
        $invoice = Invoice::where('school_id', $sid)->where('id', $data['invoice_id'])
            ->whereIn('student_id', $this->allowedStudentIds($sid))->first();

        if (! $invoice) {
            return back()->with('error', __('Invoice Not Found.'));
        }
        if ($invoice->remainingAmount() <= 0) {
            return back()->with('error', __('This Invoice Is Already Settled.'));
        }

        try {
            if ($data['gateway'] === 'paypal') {
                $result = $service->initiatePayPal(
                    $invoice,
                    route('portal.pay.paypal.return'),
                    route('portal.pay.paypal.return', ['cancel' => 1]),
                );

                return redirect()->away($result['approveUrl']);
            }

            if ($data['gateway'] === 'stripe') {
                $result = $service->initiateStripe(
                    $invoice,
                    route('portal.pay.stripe.return').'?session_id={CHECKOUT_SESSION_ID}',
                    route('portal.pay.stripe.return'), // cancel — no session_id
                );

                return redirect()->away($result['checkoutUrl']);
            }

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

            return back()->with('error', __('Could Not Start The Online Payment. Please Try Again, Or Pay At The Office.'));
        }
    }

    /**
     * PayPal returns the browser here (public GET). On approval PayPal appends
     * ?token={ORDER_ID}; the cancel_url carries ?cancel=1.
     */
    public function paypalReturn(Request $request, PaymentService $service): RedirectResponse
    {
        if ($request->boolean('cancel')) {
            return redirect()->route('portal.fees')->with('error', __('Payment Was Cancelled.'));
        }

        $orderId = $request->query('token');
        if (! $orderId) {
            return redirect()->route('portal.fees')->with('error', __('Payment Could Not Be Verified.'));
        }

        $cached = Cache::get("paypal_order:{$orderId}");
        if (! $cached) {
            return redirect()->route('portal.fees')->with('error', __('Payment Session Expired — Please Try Again.'));
        }

        try {
            $service->verifyPayPal($orderId, $cached['invoice_id'], $cached['school_id']);

            return redirect()->route('portal.fees')->with('status', 'Payment successful — thank you!');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('portal.fees')
                ->with('error', __('We Could Not Confirm Your Payment. If Your Account Was Charged, Please Contact The Office.'));
        }
    }

    /**
     * Stripe returns the browser here (public GET). A session_id means success;
     * its absence means the customer cancelled on the Stripe page.
     */
    public function stripeReturn(Request $request, PaymentService $service): RedirectResponse
    {
        $sessionId = $request->query('session_id');
        if (! $sessionId) {
            return redirect()->route('portal.fees')->with('error', __('Payment Was Cancelled.'));
        }

        $cached = Cache::get("stripe_session:{$sessionId}");
        if (! $cached) {
            return redirect()->route('portal.fees')->with('error', __('Payment Session Expired — Please Try Again.'));
        }

        try {
            $service->verifyStripe($sessionId, $cached['invoice_id'], $cached['school_id']);

            return redirect()->route('portal.fees')->with('status', 'Payment successful — thank you!');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('portal.fees')
                ->with('error', __('We Could Not Confirm Your Payment. If Your Card Was Charged, Please Contact The Office.'));
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
        $valId = $request->input('val_id');
        $status = $request->input('status');

        if (! $tranId || ! $valId || ! in_array($status, ['VALID', 'VALIDATED'], true)) {
            return redirect()->route('portal.fees')->with('error', __('Payment Could Not Be Verified.'));
        }

        $invoice = Invoice::where('invoice_number', $tranId)->first();
        if (! $invoice) {
            return redirect()->route('portal.fees')->with('error', __('Invoice Not Found For This Payment.'));
        }

        try {
            $service->verifySslcommerz($invoice, $valId);

            return redirect()->route('portal.fees')->with('status', 'Payment successful — thank you!');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('portal.fees')
                ->with('error', __('We Could Not Confirm Your Payment. If Your Account Was Debited, Please Contact The Office.'));
        }
    }

    /** bKash returns the browser here (public — the gateway drives the redirect). */
    public function bkashCallback(Request $request, PaymentService $service): RedirectResponse
    {
        $paymentId = $request->query('paymentID');
        $status = $request->query('status');

        if ($status !== 'success' || ! $paymentId) {
            return redirect()->route('portal.fees')->with('error', __('Payment Was Not Completed.'));
        }

        $cached = Cache::get("bkash_payment:{$paymentId}");
        if (! $cached) {
            return redirect()->route('portal.fees')->with('error', __('Payment Session Expired — Please Try Again.'));
        }

        try {
            $service->executeBkash($paymentId, $cached['invoice_id'], $cached['school_id']);

            return redirect()->route('portal.fees')->with('status', 'Payment successful — thank you!');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('portal.fees')
                ->with('error', __('We Could Not Confirm Your Payment. If Your Account Was Debited, Please Contact The Office.'));
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
