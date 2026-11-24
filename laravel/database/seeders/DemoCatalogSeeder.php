<?php

namespace Database\Seeders;

use App\Models\ProductSnapshot;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['Cuaderno universitario A4', 'cuaderno-universitario-a4', 'CUA-001', 12.90, 40, 1, 'Cuadernos'],
            ['Lapicero gel negro', 'lapicero-gel-negro', 'LAP-010', 3.50, 120, 2, 'Escritura'],
            ['Resaltador pastel x4', 'resaltador-pastel-x4', 'RES-004', 9.90, 25, 2, 'Escritura'],
            ['Block de notas A5', 'block-de-notas-a5', 'BLO-005', 7.50, 18, 1, 'Cuadernos'],
            ['Archivador lomo ancho', 'archivador-lomo-ancho', 'ARC-002', 14.90, 12, 3, 'Archivo'],
            ['Grapadora metálica', 'grapadora-metalica', 'GRA-003', 19.90, 8, 4, 'Oficina'],
            ['Mochila escolar reforzada', 'mochila-escolar-reforzada', 'MOC-007', 89.90, 6, 5, 'Mochilas'],
            ['Calculadora científica', 'calculadora-cientifica', 'CAL-009', 49.90, 0, 4, 'Oficina'],
        ];

        foreach ($products as $index => [$name, $slug, $sku, $price, $stock, $categoryId, $categoryName]) {
            $wooId = 9000 + $index;

            ProductSnapshot::updateOrCreate(
                ['woo_id' => $wooId],
                [
                    'sku' => $sku,
                    'name' => $name,
                    'slug' => $slug,
                    'type' => 'simple',
                    'status' => 'publish',
                    'price' => $price,
                    'regular_price' => $price,
                    'sale_price' => null,
                    'manage_stock' => true,
                    'stock_quantity' => $stock,
                    'stock_status' => $stock > 0 ? 'instock' : 'outofstock',
                    'category_ids' => [$categoryId],
                    'image_url' => null,
                    'payload' => [
                        'id' => $wooId,
                        'name' => $name,
                        'slug' => $slug,
                        'description' => 'Producto de demostración del catálogo TANISH.',
                        'categories' => [
                            ['id' => $categoryId, 'name' => $categoryName],
                        ],
                        'manage_stock' => true,
                        'stock_quantity' => $stock,
                    ],
                    'woo_created_at' => now(),
                    'woo_updated_at' => now(),
                ]
            );
        }
    }
}
