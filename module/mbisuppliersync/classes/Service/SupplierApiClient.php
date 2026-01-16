<?php

class MbiSupplierSyncSupplierApiClient
{
    /** @var string */
    private $baseUrl;

    /** @var string */
    private $apiKey;

    /** @var int */
    private $timeout;

    public function __construct($baseUrl, $apiKey = '', $timeout = 10)
    {
        $this->baseUrl = rtrim((string) $baseUrl, '/');
        $this->apiKey = (string) $apiKey;
        $this->timeout = (int) $timeout;
    }

    /**
     * Fetch supplier payload.
     *
     * @return array{supplier:string,generated_at:string,currency:string,items:array<int,array{sku:string,qty:int,price:float,updated_at:string}>}
     * @throws Exception
     */
    public function fetchCatalog()
    {
        if ($this->baseUrl === '') {
        throw new Exception('Supplier API base URL is empty');
    }

    // If baseUrl starts with "file://", read local JSON file
    if (strpos($this->baseUrl, 'file://') === 0) {
        $path = substr($this->baseUrl, 7);
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new Exception('Unable to read supplier file: ' . $path);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Exception('Supplier file contains invalid JSON');
        }

        return $this->normalize($decoded);
    }

    // HTTP mode (kept for later)
    $url = $this->baseUrl;


        $ch = curl_init();
        if ($ch === false) {
            throw new Exception('Unable to init cURL');
        }

        $headers = [
            'Accept: application/json',
        ];
        if ($this->apiKey !== '') {
            $headers[] = 'X-API-KEY: ' . $this->apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $errno) {
            throw new Exception('Supplier API request failed: ' . ($err ?: 'unknown cURL error'));
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception('Supplier API returned HTTP ' . $httpCode . ': ' . $this->shorten($raw));
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Exception('Supplier API returned invalid JSON');
        }

        return $this->normalize($decoded);
    }

    private function normalize(array $decoded)
    {
        $supplier = isset($decoded['supplier']) ? (string) $decoded['supplier'] : '';
        $generatedAt = isset($decoded['generated_at']) ? (string) $decoded['generated_at'] : '';
        $currency = isset($decoded['currency']) ? (string) $decoded['currency'] : '';

        $items = [];
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            foreach ($decoded['items'] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $sku = isset($row['sku']) ? trim((string) $row['sku']) : '';
                $qty = isset($row['qty']) ? (int) $row['qty'] : 0;
                $price = isset($row['price']) ? (float) $row['price'] : 0.0;
                $updatedAt = isset($row['updated_at']) ? (string) $row['updated_at'] : '';

                $items[] = [
                    'sku' => $sku,
                    'qty' => $qty,
                    'price' => $price,
                    'updated_at' => $updatedAt,
                ];
            }
        }

        return [
            'supplier' => $supplier,
            'generated_at' => $generatedAt,
            'currency' => $currency,
            'items' => $items,
        ];
    }

    private function shorten($s, $max = 200)
    {
        $s = (string) $s;
        if (Tools::strlen($s) <= $max) {
            return $s;
        }
        return Tools::substr($s, 0, $max) . '...';
    }
}
