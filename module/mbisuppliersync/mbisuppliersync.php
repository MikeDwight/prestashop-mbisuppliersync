<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Mbisuppliersync extends Module

{
    const CONF_API_BASE_URL = 'MBISS_API_BASE_URL';
    const CONF_API_TOKEN    = 'MBISS_API_TOKEN';
    const CONF_CRON_TOKEN   = 'MBISS_CRON_TOKEN';
    const CONF_PRICE_MODE   = 'MBISS_PRICE_MODE';
    const CONF_API_TIMEOUT  = 'MBISS_API_TIMEOUT';
    const CONF_LOG_LEVEL    = 'MBISS_LOG_LEVEL';
    const CONF_DRY_RUN      = 'MBISS_DRY_RUN';

    public function __construct()
    {
        $this->name = 'mbisuppliersync';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'MBI Demo';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MBI — Supplier Sync (Stock & Price)');
        $this->description = $this->l('Sync stock and price from a supplier REST API (demo).');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {

        Configuration::updateValue('MBISUPPLIERSYNC_SUPPLIER_API_BASE_URL', Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'module/mbisuppliersync/supplier');
        Configuration::updateValue('MBISUPPLIERSYNC_SUPPLIER_API_KEY', '');


        if (!parent::install()) {
            return false;
        }

        // SQL
        if (!$this->installSql()) {
            return false;
        }

        // Config defaults
        Configuration::updateValue(self::CONF_API_BASE_URL, '');
        Configuration::updateValue(self::CONF_API_TOKEN, '');
        Configuration::updateValue(self::CONF_CRON_TOKEN, Tools::passwdGen(32));
        Configuration::updateValue(self::CONF_PRICE_MODE, 'price'); // or wholesale_price
        Configuration::updateValue(self::CONF_API_TIMEOUT, 8);
        Configuration::updateValue(self::CONF_LOG_LEVEL, 'normal'); // normal|verbose
        Configuration::updateValue(self::CONF_DRY_RUN, 0);

        // Hooks (minimal for admin assets)
        if (!$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }

        // Admin tab
        if (!$this->installTab()) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        $this->uninstallTab();
        $this->uninstallSql();

        Configuration::deleteByName(self::CONF_API_BASE_URL);
        Configuration::deleteByName(self::CONF_API_TOKEN);
        Configuration::deleteByName(self::CONF_CRON_TOKEN);
        Configuration::deleteByName(self::CONF_PRICE_MODE);
        Configuration::deleteByName(self::CONF_API_TIMEOUT);
        Configuration::deleteByName(self::CONF_LOG_LEVEL);
        Configuration::deleteByName(self::CONF_DRY_RUN);

        return true;
    }

    public function getContent()
    {
        // Étape 1 : on met une page simple (pas encore le vrai écran).
        $cronToken = Configuration::get(self::CONF_CRON_TOKEN);
        $cronUrl = $this->context->link->getModuleLink($this->name, 'cron', ['token' => $cronToken], true);

        return '<div class="panel">'
            . '<h3>' . $this->displayName . '</h3>'
            . '<p>Module skeleton installed. Next: configuration form + BO runs page.</p>'
            . '<p><strong>Cron URL:</strong> ' . htmlspecialchars($cronUrl) . '</p>'
            . '</div>';
    }

    public function hookDisplayBackOfficeHeader()
    {
        // On inclura CSS/JS plus tard si besoin
        if (Tools::getValue('controller') === 'AdminMbiSupplierSync') {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    private function installSql()
    {
        $sqlFile = dirname(__FILE__) . '/sql/install.sql';
        if (!file_exists($sqlFile)) {
            return false;
        }
        $sql = file_get_contents($sqlFile);
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        foreach (preg_split("/;\s*[\r\n]+/", $sql) as $query) {
            $query = trim($query);
            if ($query && !Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    private function uninstallSql()
    {
        $sqlFile = dirname(__FILE__) . '/sql/uninstall.sql';
        if (!file_exists($sqlFile)) {
            return false;
        }
        $sql = file_get_contents($sqlFile);
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        foreach (preg_split("/;\s*[\r\n]+/", $sql) as $query) {
            $query = trim($query);
            if ($query && !Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminMbiSupplierSync';
        $tab->name = [];

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Supplier Sync';
        }

        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->module = $this->name;

        return (bool) $tab->add();
    }

    private function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminMbiSupplierSync');
        if ($idTab) {
            $tab = new Tab($idTab);
            return (bool) $tab->delete();
        }
        return true;
    }
}
