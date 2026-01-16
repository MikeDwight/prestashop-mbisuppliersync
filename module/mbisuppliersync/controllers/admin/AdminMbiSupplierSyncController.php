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
    require_once _PS_MODULE_DIR_ . 'mbisuppliersync/classes/Service/SupplierApiClient.php';
    require_once _PS_MODULE_DIR_ . 'mbisuppliersync/classes/Service/SupplierSyncService.php';

    $service = new MbiSupplierSyncSupplierSyncService($this->module);
    $result = $service->runSync('bo');

    if ($result['status'] === 'success') {
        $this->confirmations[] = $result['message'];
    } elseif ($result['status'] === 'partial') {
        $this->warnings[] = $result['message'];
    } else {
        $this->errors[] = $result['message'];
    }
}

parent::postProcess();

}

}
