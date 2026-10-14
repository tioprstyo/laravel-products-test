<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Persists products in a JSON file instead of a database.
 *
 * File shape: {"products": [{"id", "product_name", "quantity", "price", "submitted_at", "updated_at"}]}
 */
class ProductStore
{
    public function __construct(private readonly string $path)
    {
    }

    /**
     * All products ordered by the datetime they were submitted (oldest first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $products = $this->read();

        usort($products, fn (array $a, array $b) => strcmp($a['submitted_at'], $b['submitted_at']));

        return $products;
    }

    public function find(string $id): ?array
    {
        foreach ($this->read() as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @param  array{product_name: string, quantity: int|string, price: float|string}  $data
     */
    public function create(array $data): array
    {
        return $this->mutate(function (array $products) use ($data) {
            $now = Carbon::now()->format(DATE_RFC3339_EXTENDED);

            $product = [
                'id' => (string) Str::uuid(),
                ...$this->normalize($data),
                'submitted_at' => $now,
                'updated_at' => $now,
            ];

            $products[] = $product;

            return [$products, $product];
        });
    }

    /**
     * Updates the editable fields; the original submitted datetime is kept so ordering is stable.
     */
    public function update(string $id, array $data): ?array
    {
        return $this->mutate(function (array $products) use ($id, $data) {
            foreach ($products as $index => $product) {
                if ($product['id'] === $id) {
                    $products[$index] = [
                        ...$product,
                        ...$this->normalize($data),
                        'updated_at' => Carbon::now()->format(DATE_RFC3339_EXTENDED),
                    ];

                    return [$products, $products[$index]];
                }
            }

            return [$products, null];
        });
    }

    public function truncate(): void
    {
        $this->mutate(fn () => [[], null]);
    }

    private function normalize(array $data): array
    {
        return [
            'product_name' => trim((string) $data['product_name']),
            'quantity' => (int) $data['quantity'],
            'price' => round((float) $data['price'], 2),
        ];
    }

    private function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $handle = fopen($this->path, 'r');
        flock($handle, LOCK_SH);
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $this->decode($contents);
    }

    /**
     * Read-modify-write under an exclusive lock so concurrent submissions are not lost.
     *
     * @param  callable(array): array{0: array, 1: mixed}  $callback
     */
    private function mutate(callable $callback): mixed
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create directory [{$directory}].");
        }

        $handle = fopen($this->path, 'c+');

        if ($handle === false) {
            throw new RuntimeException("Unable to open [{$this->path}] for writing.");
        }

        try {
            flock($handle, LOCK_EX);

            [$products, $result] = $callback($this->decode(stream_get_contents($handle)));

            $json = json_encode(['products' => array_values($products)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $json.PHP_EOL);
            fflush($handle);

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function decode(string|false $contents): array
    {
        if ($contents === false || trim($contents) === '') {
            return [];
        }

        $data = json_decode($contents, true);

        return is_array($data['products'] ?? null) ? $data['products'] : [];
    }
}
