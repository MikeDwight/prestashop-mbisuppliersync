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

        // Template module : views/templates/admin/runs.tpl
        $this->setTemplate('runs.tpl');
    }
}
