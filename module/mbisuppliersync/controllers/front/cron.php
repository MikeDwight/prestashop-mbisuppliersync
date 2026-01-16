<?php

class MbisuppliersyncCronModuleFrontController extends ModuleFrontController
{
    public $ssl = false;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        $token = (string) Tools::getValue('token');
        $expected = (string) Configuration::get(Mbisuppliersync::CONF_CRON_TOKEN);

        if (!$token || !$expected || !hash_equals($expected, $token)) {
            http_response_code(403);
            die(json_encode(['ok' => false, 'error' => 'forbidden']));
        }

        // Étape 1 : pas de synchro, juste ping
        die(json_encode(['ok' => true, 'message' => 'cron endpoint is ready (step 1)']));
    }
}
