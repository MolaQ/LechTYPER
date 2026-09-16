<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function create(Request $request, int $days): RedirectResponse
    {
        abort_unless(in_array($days, [7, 30], true), 404);
        abort_unless(config('services.przelewy24.merchant_id') && config('services.przelewy24.api_key'), 503, 'Płatności Przelewy24 nie są jeszcze skonfigurowane.');

        $amount = (int) config("services.przelewy24.amounts.{$days}");
        abort_unless($amount > 0, 503, 'Cena tego planu Premium nie jest jeszcze skonfigurowana.');
        $sessionId = (string) Str::uuid();
        $currency = 'PLN';
        $sign = $this->sign(['sessionId' => $sessionId, 'merchantId' => (int) config('services.przelewy24.merchant_id'), 'amount' => $amount, 'currency' => $currency, 'crc' => config('services.przelewy24.crc')]);
        $response = Http::withBasicAuth(config('services.przelewy24.merchant_id'), config('services.przelewy24.api_key'))
            ->post(config('services.przelewy24.url').'/api/v1/transaction/register', [
                'merchantId' => (int) config('services.przelewy24.merchant_id'),
                'posId' => (int) config('services.przelewy24.pos_id'),
                'sessionId' => $sessionId,
                'amount' => $amount,
                'currency' => $currency,
                'description' => config('app.name').' - Premium '.$days.' dni',
                'email' => $request->user()->email,
                'country' => 'PL',
                'language' => 'pl',
                'urlReturn' => route('payment.return'),
                'urlStatus' => route('payment.notify'),
                'sign' => $sign,
            ]);

        abort_unless($response->successful() && $response->json('data.token'), 502, 'Nie udało się rozpocząć płatności.');
        Payment::create(['user_id' => $request->user()->id, 'session_id' => $sessionId, 'amount' => $amount, 'currency' => $currency, 'premium_days' => $days, 'status' => 'pending', 'payload' => $response->json()]);

        return redirect()->away(config('services.przelewy24.url').'/trnRequest/'.$response->json('data.token'));
    }

    public function notify(Request $request): JsonResponse
    {
        $data = $request->validate(['sessionId' => ['required', 'string'], 'orderId' => ['required', 'integer'], 'amount' => ['required', 'integer'], 'currency' => ['required', 'string']]);
        $payment = Payment::where('session_id', $data['sessionId'])->where('status', 'pending')->firstOrFail();
        $sign = $this->sign(['sessionId' => $data['sessionId'], 'orderId' => $data['orderId'], 'amount' => $data['amount'], 'currency' => $data['currency'], 'crc' => config('services.przelewy24.crc')]);

        if (! hash_equals($sign, (string) $request->input('sign'))) {
            abort(403, 'Nieprawidłowy podpis płatności.');
        }

        $verify = Http::withBasicAuth(config('services.przelewy24.merchant_id'), config('services.przelewy24.api_key'))->post(config('services.przelewy24.url').'/api/v1/transaction/verify', ['merchantId' => (int) config('services.przelewy24.merchant_id'), 'posId' => (int) config('services.przelewy24.pos_id'), 'sessionId' => $payment->session_id, 'orderId' => $data['orderId'], 'amount' => $payment->amount, 'currency' => $payment->currency, 'sign' => $this->sign(['sessionId' => $payment->session_id, 'orderId' => $data['orderId'], 'amount' => $payment->amount, 'currency' => $payment->currency, 'crc' => config('services.przelewy24.crc')])]);
        abort_unless($verify->successful(), 502, 'Nie udało się potwierdzić płatności.');
        $payment->update(['order_id' => $data['orderId'], 'status' => 'paid', 'payload' => $request->all()]);
        $user = $payment->user;
        $start = $user->hasActivePremium() && $user->premium_until ? $user->premium_until : now();
        $user->update(['is_premium' => true, 'premium_until' => $start->addDays($payment->premium_days), 'premium_source' => 'payment']);

        return response()->json(['status' => 'ok']);
    }

    public function return(): RedirectResponse
    {
        return redirect()->route('stats')->with('status', 'Płatność została przekazana do weryfikacji.');
    }

    private function sign(array $values): string
    {
        return hash('sha384', json_encode($values, JSON_UNESCAPED_SLASHES));
    }
}
