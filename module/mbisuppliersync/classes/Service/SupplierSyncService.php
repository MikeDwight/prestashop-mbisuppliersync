<?php

class MbiSupplierSyncSupplierSyncService
{
    /** @var MbiSupplierSync */
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * Run a full synchronization.
     *
     * @param string $trigger "bo" or "cron"
     * @return array
     */
    public function runSync($trigger = 'bo')
    {
        $startedAt = microtime(true);

        // 1) Create run (running)
        $runId = $this->createRun($trigger);

        try {
            // 2) Fetch API payload
            $baseUrl = (string) Configuration::get('MBISUPPLIERSYNC_SUPPLIER_API_BASE_URL');
            $apiKey = (string) Configuration::get('MBISUPPLIERSYNC_SUPPLIER_API_KEY');

            $client = new MbiSupplierSyncSupplierApiClient($baseUrl, $apiKey, 10);
            $payload = $client->fetchCatalog();

            // Currency check (V1 strict)
            if (!isset($payload['currency']) || (string) $payload['currency'] !== 'EUR') {
                throw new Exception('Unsupported currency: ' . (isset($payload['currency']) ? (string) $payload['currency'] : ''));
            }

            $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
            $total = count($items);

            $updated = 0;
            $failed = 0;

            // Update totals early
            $this->updateRunTotals($runId, $total, 0, 0);

            // 3) Loop items
            foreach ($items as $row) {
                $result = $this->processItem($runId, $row);

                if ($result['status'] === 'success') {
                    $updated++;
                } else {
                    $failed++;
                }

                // Update counters as we go (simple & traceable)
                $this->updateRunTotals($runId, $total, $updated, $failed);
            }

            // 4) Finalize run status
            $status = 'success';
            if ($failed > 0 && $updated > 0) {
                $status = 'partial';
            } elseif ($failed > 0 && $updated === 0) {
                $status = 'failed';
            }

            $executionMs = (int) round((microtime(true) - $startedAt) * 1000);
            $message = sprintf('Completed: total=%d, updated=%d, failed=%d', $total, $updated, $failed);

            $this->finalizeRun($runId, $status, $message, $executionMs);

            return [
                'run_id' => (int) $runId,
                'status' => $status,
                'total' => $total,
                'updated' => $updated,
                'failed' => $failed,
                'message' => $message,
            ];
        } catch (Exception $e) {
            $executionMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->finalizeRun($runId, 'failed', 'API error: ' . $e->getMessage(), $executionMs);


            return [
                'run_id' => (int) $runId,
                'status' => 'failed',
                'total' => 0,
                'updated' => 0,
                'failed' => 0,
                'message' => 'API error: ' . $e->getMessage(),
            ];
        }
    }

    private function processItem($runId, array $row)
    {
        $sku = isset($row['sku']) ? trim((string) $row['sku']) : '';
        $qty = isset($row['qty']) ? (int) $row['qty'] : 0;
        $price = isset($row['price']) ? (float) $row['price'] : 0.0;

        if ($sku === '' || $qty < 0 || $price < 0) {
            $this->createRunItem([
                'id_run' => $runId,
                'sku' => $sku,
                'status' => 'error',
                'error_code' => 'INVALID_DATA',
                'error_message' => 'Invalid sku / qty / price',
            ]);
            return ['status' => 'failed'];
        }

       

        $idProduct = (int) $this->findProductIdBySku($sku);
        if ($idProduct <= 0) {
            $this->createRunItem([
                'id_run' => $runId,
                'sku' => $sku,
                'status' => 'error',
                'error_code' => 'SKU_NOT_FOUND',
                'error_message' => 'SKU not found in Prestashop',
            ]);
            return ['status' => 'failed'];
        }

        try {
            $product = new Product($idProduct);
            if (!Validate::isLoadedObject($product)) {
                $this->createRunItem([
                    'id_run' => $runId,
                    'sku' => $sku,
                    'id_product' => $idProduct,
                    'status' => 'error',
                    'error_code' => 'PRODUCT_LOAD_FAILED',
                    'error_message' => 'Unable to load product',
                ]);
                return ['status' => 'failed'];
            }

            // Old values
            $oldStock = (int) StockAvailable::getQuantityAvailableByProduct($idProduct);
            $oldPrice = (float) $product->wholesale_price;

            // Apply updates
            StockAvailable::setQuantity($idProduct, 0, $qty);
            $product->wholesale_price = (float) number_format($price, 2, '.', '');
            $product->update();

            $this->createRunItem([
                'id_run' => $runId,
                'sku' => $sku,
                'id_product' => $idProduct,
                'old_stock' => $oldStock,
                'new_stock' => $qty,
                'old_price' => $oldPrice,
                'new_price' => (float) $product->wholesale_price,
                'status' => 'updated',
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            $this->createRunItem([
                'id_run' => $runId,
                'sku' => $sku,
                'id_product' => $idProduct,
                'status' => 'error',
                'error_code' => 'EXCEPTION',
                'error_message' => $e->getMessage(),
            ]);
            return ['status' => 'failed'];
        }
    }

private function findProductIdBySku($sku)
{
    $sku = pSQL(trim((string) $sku));

    $sql = "SELECT id_product FROM " . _DB_PREFIX_ . "product WHERE reference = '" . $sku . "' LIMIT 1";
    $rows = Db::getInstance()->executeS($sql);

    if (!$rows || !isset($rows[0]['id_product'])) {
        return 0;
    }

    return (int) $rows[0]['id_product'];
}







    /**
     * Insert run row (aligned with your table schema)
     */
    private function createRun($trigger)
    {
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->insert('mbisuppliersync_run', [
            'started_at' => pSQL($now),
            'ended_at' => null,
            'status' => pSQL('running'),
            'items_total' => 0,
            'items_updated' => 0,
            'items_failed' => 0,
            'message' => pSQL('Run started (trigger=' . $trigger . ')'),
            'execution_ms' => 0,
        ]);

        return (int) Db::getInstance()->Insert_ID();
    }

    private function updateRunTotals($runId, $total, $updated, $failed)
    {
        Db::getInstance()->update('mbisuppliersync_run', [
            'items_total' => (int) $total,
            'items_updated' => (int) $updated,
            'items_failed' => (int) $failed,
        ], 'id_run=' . (int) $runId);
    }

    private function finalizeRun($runId, $status, $message, $executionMs)
    {
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->update('mbisuppliersync_run', [
            'ended_at' => pSQL($now),
            'status' => pSQL($status),
            'message' => pSQL($message),
            'execution_ms' => (int) $executionMs,
        ], 'id_run=' . (int) $runId);
    }

    /**
     * Insert run item row (aligned with your table schema)
     */
    private function createRunItem(array $data)
    {
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->insert('mbisuppliersync_run_item', [
            'id_run' => (int) $data['id_run'],
            'sku' => pSQL(isset($data['sku']) ? $data['sku'] : ''),
            'id_product' => isset($data['id_product']) ? (int) $data['id_product'] : null,
            'old_stock' => isset($data['old_stock']) ? (int) $data['old_stock'] : null,
            'new_stock' => isset($data['new_stock']) ? (int) $data['new_stock'] : null,
            'old_price' => isset($data['old_price']) ? (float) $data['old_price'] : null,
            'new_price' => isset($data['new_price']) ? (float) $data['new_price'] : null,
            'status' => pSQL(isset($data['status']) ? $data['status'] : 'error'),
            'error_code' => isset($data['error_code']) ? pSQL($data['error_code']) : null,
            'error_message' => isset($data['error_message']) ? pSQL($data['error_message']) : null,
            'created_at' => pSQL($now),
        ]);
    }
}
