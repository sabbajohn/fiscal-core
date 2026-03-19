<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/common.php';

$projectRoot = dirname(__DIR__, 2);

exit(nfseMunicipalRunScript('joinville', $argv, [
    'FISCAL_ENVIRONMENT' => 'homologacao',
    'FISCAL_CERT_PATH' => $projectRoot . '/certs/cert2026-senha-free2026.pfx',
    'FISCAL_CERT_PASSWORD' => 'free2026',
    'FISCAL_CNPJ' => '83188342000104',
    'FISCAL_RAZAO_SOCIAL' => 'FREELINE INFORMATICA LTDA',
    'FISCAL_UF' => 'SC',
]));
