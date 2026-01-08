<?php

namespace freeline\FiscalCore\Facade;

use freeline\FiscalCore\Adapters\NF\NFSeAdapter;
use freeline\FiscalCore\Support\ResponseHandler;
use freeline\FiscalCore\Support\FiscalResponse;
use freeline\FiscalCore\Support\ProviderRegistry;

/**
 * Facade para NFSe - Interface simplificada e com tratamento de erros
 * Evita que aplicações recebam erros 500 fornecendo responses padronizados
 */
class NFSeFacade
{
    private ?NFSeAdapter $nfse = null;
    private ResponseHandler $responseHandler;
    private ?FiscalResponse $initializationError = null;
    private string $municipio;

    public function __construct(string $municipio = 'curitiba', ?NFSeAdapter $nfse = null)
    {
        $this->municipio = $municipio;
        $this->responseHandler = new ResponseHandler();
        
        if ($nfse !== null) {
            $this->nfse = $nfse;
        } else {
            try {
                // Verifica se o município está configurado
                $registry = ProviderRegistry::getInstance();
                if (!$registry->has($municipio)) {
                    $this->initializationError = FiscalResponse::error(
                        'PROVIDER_NOT_FOUND',
                        "Provider para município '{$municipio}' não encontrado",
                        'nfse_initialization',
                        [
                            'available_municipios' => $registry->listMunicipios(),
                            'suggestions' => [
                                "Verifique se o município '{$municipio}' está configurado",
                                'Use um dos municípios disponíveis listados',
                                'Configure o município em config/nfse-municipios.json'
                            ]
                        ]
                    );
                    return;
                }

                $this->nfse = new NFSeAdapter($municipio);
            } catch (\Exception $e) {
                $this->initializationError = $this->responseHandler->handle($e, 'nfse_initialization');
            }
        }
    }

    /**
     * Verifica se o NFSe está inicializado corretamente
     */
    private function checkNFSeInitialization(): ?FiscalResponse
    {
        if ($this->initializationError !== null) {
            return $this->initializationError;
        }
        return null;
    }

    /**
     * Emite uma NFSe
     */
    public function emitir(array $dados): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->emitir($dados);
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_xml',
                'municipio' => $this->municipio
            ], 'nfse_emission', [
                'municipio' => $this->municipio,
                'provider_info' => $this->nfse->getProviderInfo()
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_emission');
        }
    }

    /**
     * Consulta uma NFSe
     */
    public function consultar(string $chave): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->consultar($chave);
            return FiscalResponse::success([
                'resultado' => $resultado,
                'type' => 'nfse_consulta',
                'chave' => $chave,
                'municipio' => $this->municipio
            ], 'nfse_query', [
                'chave' => $chave,
                'municipio' => $this->municipio
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_query');
        }
    }

    /**
     * Cancela uma NFSe
     */
    public function cancelar(string $chave, string $motivo, string $protocolo = ''): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $resultado = $this->nfse->cancelar($chave, $motivo, $protocolo);
            return FiscalResponse::success([
                'canceled' => $resultado,
                'type' => 'nfse_cancelamento',
                'chave' => $chave,
                'motivo' => $motivo,
                'municipio' => $this->municipio
            ], 'nfse_cancellation', [
                'chave' => $chave,
                'motivo' => $motivo,
                'municipio' => $this->municipio
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_cancellation');
        }
    }

    /**
     * Lista municípios disponíveis
     */
    public function listarMunicipios(): FiscalResponse
    {
        try {
            $registry = ProviderRegistry::getInstance();
            $municipios = $registry->listMunicipios();
            
            return FiscalResponse::success(['municipios' => $municipios], 'nfse_list_municipios', [
                'total' => count($municipios),
                'current_municipio' => $this->municipio
            ]);
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_list_municipios');
        }
    }

    /**
     * Obtém informações do provider atual
     */
    public function getProviderInfo(): FiscalResponse
    {
        if ($check = $this->checkNFSeInitialization()) {
            return $check;
        }

        try {
            $info = $this->nfse->getProviderInfo();
            return FiscalResponse::success($info, 'nfse_provider_info');
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_provider_info');
        }
    }

    /**
     * Valida XML NFSe
     */
    public function validarXML(string $xml): FiscalResponse
    {
        return $this->responseHandler->execute(function() use ($xml) {
            if (empty($xml)) {
                throw new \InvalidArgumentException("XML não pode estar vazio");
            }
            
            // Validação básica de XML
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            
            if (!$dom->loadXML($xml)) {
                $errors = libxml_get_errors();
                $errorMsg = "XML inválido: " . $errors[0]->message ?? 'Erro desconhecido';
                libxml_clear_errors();
                throw new \InvalidArgumentException($errorMsg);
            }
            
            // Tenta identificar tipo de NFSe
            $tiposNFSe = ['CompNfse', 'Nfse', 'InfNfse', 'GerarNfseEnvio'];
            $tipoDetectado = null;
            
            foreach ($tiposNFSe as $tipo) {
                if ($dom->getElementsByTagName($tipo)->length > 0) {
                    $tipoDetectado = $tipo;
                    break;
                }
            }
            
            if (!$tipoDetectado) {
                throw new \InvalidArgumentException("XML não é uma NFSe válida");
            }
            
            return [
                'xml_valido' => true,
                'tipo_nfse' => $tipoDetectado,
                'municipio_esperado' => $this->municipio,
                'tamanho_xml' => strlen($xml)
            ];
        }, 'validacao_xml_nfse');
    }
    
    /**
     * Valida dados do prestador NFSe
     */
    public function validarPrestador(array $prestador): FiscalResponse
    {
        return $this->responseHandler->execute(function() use ($prestador) {
            $erros = [];
            
            // Valida campos obrigatórios
            if (empty($prestador['cnpj'])) {
                $erros[] = 'CNPJ do prestador é obrigatório';
            } else {
                // Valida formato CNPJ
                $cnpj = preg_replace('/\D/', '', $prestador['cnpj']);
                if (strlen($cnpj) !== 14) {
                    $erros[] = 'CNPJ deve ter 14 dígitos';
                }
            }
            
            if (empty($prestador['inscricaoMunicipal'])) {
                $erros[] = 'Inscrição Municipal é obrigatória';
            }
            
            if (empty($prestador['razaoSocial'])) {
                $erros[] = 'Razão Social é obrigatória';
            }
            
            if (!empty($erros)) {
                throw new \InvalidArgumentException("Prestador inválido: " . implode(', ', $erros));
            }
            
            return [
                'prestador_valido' => true,
                'cnpj_formatado' => isset($prestador['cnpj']) ? preg_replace('/\D/', '', $prestador['cnpj']) : null,
                'validacoes' => [
                    'cnpj' => !empty($prestador['cnpj']),
                    'inscricao_municipal' => !empty($prestador['inscricaoMunicipal']),
                    'razao_social' => !empty($prestador['razaoSocial'])
                ]
            ];
        }, 'validacao_prestador_nfse');
    }

    /**
     * Valida configuração do município
     */
    public function validarMunicipio(?string $municipio = null): FiscalResponse
    {
        $municipioToValidate = $municipio ?? $this->municipio;
        
        try {
            $registry = ProviderRegistry::getInstance();
            
            if (!$registry->has($municipioToValidate)) {
                return FiscalResponse::error(
                    'MUNICIPALITY_NOT_CONFIGURED',
                    "Município '{$municipioToValidate}' não está configurado",
                    'nfse_municipality_validation',
                    [
                        'available_municipios' => $registry->listMunicipios(),
                        'suggestions' => [
                            'Configure o município em config/nfse-municipios.json',
                            'Verifique o nome do município',
                            'Use um dos municípios disponíveis'
                        ]
                    ]
                );
            }

            $config = $registry->getConfig($municipioToValidate);
            return FiscalResponse::success([
                'municipio' => $municipioToValidate,
                'configured' => true,
                'config_keys' => array_keys($config)
            ], 'nfse_municipality_validation');
            
        } catch (\Exception $e) {
            return $this->responseHandler->handle($e, 'nfse_municipality_validation');
        }
    }
}
