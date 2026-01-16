<?php

class AdminMbiSupplierSyncController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->meta_title = $this->l('Supplier Sync');
    }

    public function initContent()
    {
        parent::initContent();

        $cronToken = (string) Configuration::get(Mbisuppliersync::CONF_CRON_TOKEN);
        $cronUrl = $this->context->link->getModuleLink(
            $this->module->name,
            'cron',
            ['token' => $cronToken],
            true
        );

        $configureUrl = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->module->name,
        ]);

        $this->context->smarty->assign([
            'mbiss_cron_url' => $cronUrl,
            'mbiss_configure_url' => $configureUrl,
        ]);

        $runs = Db::getInstance()->executeS('
            SELECT id_run, started_at, ended_at, status,
                  items_total, items_updated, items_failed,
                  message, execution_ms
            FROM '._DB_PREFIX_.'mbisuppliersync_run
            ORDER BY started_at DESC
            LIMIT 50
        ');

        $selectedRun = null;
        $selectedItems = [];

        $idRun = (int) Tools::getValue('id_run');
        if ($idRun > 0) {
            $sqlRun = 'SELECT id_run, started_at, ended_at, status,
                              items_total, items_updated, items_failed,
                              message, execution_ms
                      FROM '._DB_PREFIX_.'mbisuppliersync_run
                      WHERE id_run = '.(int)$idRun;

            $selectedRun = Db::getInstance()->getRow($sqlRun);

            if ($selectedRun) {
                $sqlItems = 'SELECT id_run_item, id_run, sku, id_product,
                                    old_stock, new_stock, old_price, new_price,
                                    status, error_code, error_message, created_at
                            FROM '._DB_PREFIX_.'mbisuppliersync_run_item
                            WHERE id_run = '.(int)$idRun.'
                            ORDER BY id_run_item ASC';

                $selectedItems = Db::getInstance()->executeS($sqlItems);
            }
        }



        $this->context->smarty->assign([
            'mbiss_runs' => $runs,
            'mbiss_selected_run' => $selectedRun,
            'mbiss_selected_items' => $selectedItems,
        ]);


        // Template module : views/templates/admin/runs.tpl
        $this->setTemplate('runs.tpl');
    }

    public function postProcess()
{
    if (Tools::isSubmit('submitMbiSupplierSyncRun')) {
        try {
            $start = microtime(true);

            // 1) Création du run
            Db::getInstance()->insert('mbisuppliersync_run', [
                'started_at'    => date('Y-m-d H:i:s'),
                'ended_at'      => date('Y-m-d H:i:s'),
                'status'        => 'partial',
                'items_total'   => 3,
                'items_updated' => 1,
                'items_failed'  => 1,
                'message'       => 'Simulation Étape 2 : aucune API appelée',
                'execution_ms'  => 0,
            ]);

            $idRun = (int) Db::getInstance()->Insert_ID();

            // 2) Items mockés
            $now = date('Y-m-d H:i:s');

            Db::getInstance()->insert('mbisuppliersync_run_item', [
                'id_run'      => $idRun,
                'sku'         => 'SKU-TEST-001',
                'id_product'  => 1,
                'old_stock'   => 10,
                'new_stock'   => 12,
                'old_price'   => 19.90,
                'new_price'   => 18.50,
                'status'      => 'updated',
                'created_at'  => $now,
            ]);

            Db::getInstance()->insert('mbisuppliersync_run_item', [
                'id_run'      => $idRun,
                'sku'         => 'SKU-TEST-002',
                'id_product'  => null,
                'status'      => 'skipped',
                'created_at'  => $now,
            ]);

            Db::getInstance()->insert('mbisuppliersync_run_item', [
                'id_run'         => $idRun,
                'sku'            => 'SKU-TEST-003',
                'status'         => 'error',
                'error_code'     => 'PRODUCT_NOT_FOUND',
                'error_message'  => 'Produit introuvable dans Prestashop',
                'created_at'     => $now,
            ]);

            // 3) Durée réelle
            $executionMs = (int) ((microtime(true) - $start) * 1000);
            Db::getInstance()->update(
                'mbisuppliersync_run',
                ['execution_ms' => $executionMs],
                'id_run = ' . (int) $idRun
            );

            $this->confirmations[] = sprintf(
                'Simulation lancée avec succès. Run #%d créé.',
                $idRun
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'MBI Supplier Sync – erreur simulation : ' . $e->getMessage(),
                3
            );
            $this->errors[] = 'Erreur lors de la création du run de simulation.';
        }
    }

    parent::postProcess();
}

}
