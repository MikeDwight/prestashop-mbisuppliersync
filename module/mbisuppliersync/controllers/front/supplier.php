<?php

class MbiSupplierSyncSupplierModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        // Toujours JSON
        header('Content-Type: application/json; charset=utf-8');

        // (Option V1) petite auth simple via X-API-KEY
        // Tu peux mettre la clé en config plus tard. Là on tolère vide = pas de check.
        $expectedKey = (string) Configuration::get('MBISUPPLIERSYNC_SUPPLIER_API_KEY');
        $providedKey = isset($_SERVER['HTTP_X_API_KEY']) ? (string) $_SERVER['HTTP_X_API_KEY'] : '';

        if ($expectedKey !== '' && $providedKey !== $expectedKey) {
            http_response_code(401);
            die(json_encode([
                'ok' => false,
                'error' => 'unauthorized',
                'message' => 'Invalid API key',
            ]));
        }

        // Payload fournisseur fictif (SKU = reference produit)
        $now = gmdate('c');

        $data = [
            'supplier' => 'MBI-DEMO-SUPPLIER',
            'generated_at' => $now,
            'currency' => 'EUR',
            'items' => [
                [
                    'sku' => 'ABC-001',
                    'qty' => 12,
                    'price' => 19.90,
                    'updated_at' => $now,
                ],
                [
                    'sku' => 'ABC-002',
                    'qty' => 0,
                    'price' => 5.50,
                    'updated_at' => $now,
                ],
                [
                    'sku' => 'UNKNOWN-SKU',
                    'qty' => 3,
                    'price' => 9.99,
                    'updated_at' => $now,
                ],
            ],
        ];

        http_response_code(200);
        die(json_encode($data));
    }
}
