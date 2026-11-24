<?php

namespace App\Services\WooCommerce;

use App\Exceptions\WooCommerceException;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WooCommerceClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public static function fromConfig(): self
    {
        /** @var array<string, mixed> $config */
        $config = config('woocommerce', []);

        return new self($config);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->decode($this->request()->get($endpoint, $query), 'GET', $endpoint);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int|string, mixed>
     */
    public function put(string $endpoint, array $data): array
    {
        return $this->decode($this->request()->put($endpoint, $data), 'PUT', $endpoint);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function products(array $query = []): array
    {
        return $this->get('products', $query);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function product(int $productId): array
    {
        return $this->get("products/{$productId}");
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function orders(array $query = []): array
    {
        return $this->get('orders', $query);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function order(int $orderId): array
    {
        return $this->get("orders/{$orderId}");
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function customers(array $query = []): array
    {
        return $this->get('customers', $query);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function updateStock(int $productId, float $quantity, bool $manageStock = true): array
    {
        return $this->put("products/{$productId}", [
            'manage_stock' => $manageStock,
            'stock_quantity' => $quantity,
        ]);
    }

    /**
     * Recorre todas las páginas de un recurso.
     *
     * @param  array<string, mixed>  $query
     * @return Generator<int, array<int, array<string, mixed>>>
     */
    public function paginate(string $endpoint, array $query = []): Generator
    {
        $perPage = (int) ($this->config['per_page'] ?? 100);

        for ($page = 1; ; $page++) {
            $items = $this->get($endpoint, array_merge($query, [
                'per_page' => $perPage,
                'page' => $page,
            ]));

            if (! is_array($items) || $items === []) {
                return;
            }

            /** @var array<int, array<string, mixed>> $items */
            yield $items;

            if (count($items) < $perPage) {
                return;
            }
        }
    }

    private function request(): PendingRequest
    {
        $key = (string) ($this->config['consumer_key'] ?? '');
        $secret = (string) ($this->config['consumer_secret'] ?? '');

        if ($key === '' || $secret === '') {
            throw WooCommerceException::notConfigured();
        }

        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/')
            .'/wp-json/'.$this->config['api_version'];

        return Http::baseUrl($baseUrl)
            ->withBasicAuth($key, $secret)
            ->acceptJson()
            ->asJson()
            ->timeout((int) ($this->config['timeout'] ?? 20))
            ->withOptions(['verify' => (bool) ($this->config['verify'] ?? true)]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decode(Response $response, string $method, string $endpoint): array
    {
        if ($response->failed()) {
            throw WooCommerceException::requestFailed($method, $endpoint, $response->status(), $response->body());
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }
}
