<?php

namespace App\Http\Controllers;

use App\Models\ProductSnapshot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $products = ProductSnapshot::query()
            ->where('status', 'publish')
            ->orderByDesc('woo_updated_at')
            ->limit(8)
            ->get();

        return view('storefront.home', [
            'products' => $products,
            'categories' => $this->categories(),
        ]);
    }

    public function catalog(Request $request): View
    {
        $query = ProductSnapshot::query()->where('status', 'publish');

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $category = (int) $request->query('categoria', 0);

        if ($category > 0) {
            $query->whereJsonContains('category_ids', $category);
        }

        $products = $query
            ->orderBy('name')
            ->paginate((int) config('tanish.front.products_per_page', 12))
            ->withQueryString();

        return view('storefront.catalog', [
            'products' => $products,
            'categories' => $this->categories(),
            'search' => $search,
            'category' => $category,
        ]);
    }

    public function product(string $identifier): View
    {
        $product = ProductSnapshot::query()
            ->where('status', 'publish')
            ->where(function ($builder) use ($identifier): void {
                $builder->where('slug', $identifier)
                    ->orWhere('woo_id', $identifier);
            })
            ->firstOrFail();

        $related = ProductSnapshot::query()
            ->where('status', 'publish')
            ->whereKeyNot($product->getKey())
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('storefront.product', [
            'product' => $product,
            'related' => $related,
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string, count: int}>
     */
    private function categories(): array
    {
        $counts = [];

        ProductSnapshot::query()
            ->where('status', 'publish')
            ->get(['payload'])
            ->each(function (ProductSnapshot $product) use (&$counts): void {
                foreach (($product->payload['categories'] ?? []) as $category) {
                    $id = (int) ($category['id'] ?? 0);
                    $name = (string) ($category['name'] ?? '');

                    if ($id <= 0 || $name === '') {
                        continue;
                    }

                    $counts[$id] ??= ['id' => $id, 'name' => $name, 'count' => 0];
                    $counts[$id]['count']++;
                }
            });

        usort($counts, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice($counts, 0, 12);
    }
}
