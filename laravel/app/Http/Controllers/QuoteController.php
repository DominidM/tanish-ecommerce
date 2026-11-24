<?php

namespace App\Http\Controllers;

use App\Models\ProductSnapshot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class QuoteController extends Controller
{
    public function add(Request $request, ProductSnapshot $product): RedirectResponse
    {
        $quantity = max(1, (int) $request->input('quantity', 1));
        $quote = $this->quote();

        $key = (string) $product->woo_id;
        $quote[$key] = ($quote[$key] ?? 0) + $quantity;

        session(['quote' => $quote]);

        return back()->with('status', 'Producto agregado a la cotización.');
    }

    public function remove(ProductSnapshot $product): RedirectResponse
    {
        $quote = $this->quote();
        unset($quote[(string) $product->woo_id]);

        session(['quote' => $quote]);

        return redirect()->route('quote.show');
    }

    public function clear(): RedirectResponse
    {
        session()->forget('quote');

        return redirect()->route('quote.show');
    }

    public function show(): View
    {
        [$items, $total] = $this->items();

        return view('storefront.quote', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function whatsapp(): RedirectResponse
    {
        [$items, $total] = $this->items();

        $lines = ['Hola TANISH, quiero cotizar:'];

        foreach ($items as $item) {
            $lines[] = sprintf(
                '- %s x%d = %s %s',
                $item['product']->name,
                $item['quantity'],
                config('tanish.currency', 'PEN'),
                number_format($item['subtotal'], 2)
            );
        }

        if ($items->isEmpty()) {
            return redirect()->route('storefront.catalog');
        }

        $lines[] = '';
        $lines[] = 'Total: '.config('tanish.currency', 'PEN').' '.number_format($total, 2);

        $url = 'https://wa.me/'.config('tanish.whatsapp_number')
            .'?text='.rawurlencode(implode("\n", $lines));

        return redirect()->away($url);
    }

    /**
     * @return array<string, int>
     */
    private function quote(): array
    {
        /** @var array<string, int> $quote */
        $quote = session('quote', []);

        return $quote;
    }

    /**
     * @return array{0: Collection<int, array{product: ProductSnapshot, quantity: int, subtotal: float}>, 1: float}
     */
    private function items(): array
    {
        $quote = $this->quote();

        $products = ProductSnapshot::query()
            ->whereIn('woo_id', array_keys($quote))
            ->get()
            ->keyBy('woo_id');

        $items = collect($quote)
            ->map(function (int $quantity, int|string $wooId) use ($products): ?array {
                $product = $products->get((int) $wooId);

                if (! $product instanceof ProductSnapshot) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => (float) $product->price * $quantity,
                ];
            })
            ->filter()
            ->values();

        return [$items, (float) $items->sum('subtotal')];
    }
}
