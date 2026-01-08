<?php
namespace freeline\Examples;
require_once __DIR__ . '/../vendor/autoload.php';

use freeline\FiscalCore\Facade\NFeFacade;
use freeline\FiscalCore\Support\FiscalResponse;

function consultaDispSefaz(): FiscalResponse
{
    
        $nfeFacade = new NFeFacade();
        
        return $nfeFacade->verificarStatusSefaz('SC', 1);
}


$response = consultaDispSefaz();
$response->toJson();
if ($response->isSuccess()) {
    echo "Consulta realizada com sucesso!\n";
    print_r($response->getData());
} else {
    echo "Erro na consulta: " . $response->getError() . "\n";
}