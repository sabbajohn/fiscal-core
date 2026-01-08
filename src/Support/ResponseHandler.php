<?php

namespace freeline\FiscalCore\Support;

use freeline\FiscalCore\Support\FiscalResponse;
use freeline\FiscalCore\Exceptions\FiscalException;
use freeline\FiscalCore\Exceptions\CertificateException;
use freeline\FiscalCore\Exceptions\SefazException;
use freeline\FiscalCore\Exceptions\ValidationException;
use freeline\FiscalCore\Exceptions\XmlException;

/**
 * Handler centralizado para tratamento de exceções e responses
 * Evita que aplicações recebam erros 500 incontroláveis
 */
class ResponseHandler
{
    private array $errorMappings = [
        'RuntimeException' => 'RUNTIME_ERROR',
        'InvalidArgumentException' => 'INVALID_ARGUMENT',
        'LogicException' => 'LOGIC_ERROR',
        'Exception' => 'GENERAL_ERROR'
    ];

    /**
     * Executa função e retorna FiscalResponse tratado
     * 
     * @param callable $callback Função a ser executada
     * @param string $operation Nome da operação (para logs/debug)
     * @return FiscalResponse Response padronizado
     */
    public function handle(callable $callback, string $operation = 'unknown'): FiscalResponse
    {
        try {
            $result = $callback();
            
            // Se callback já retornou FiscalResponse, usa ele
            if ($result instanceof FiscalResponse) {
                return $result;
            }
            
            // Se retornou array ou dados, cria resposta de sucesso
            return FiscalResponse::success(
                is_array($result) ? $result : ['result' => $result],
                $operation,
                ['execution_time' => $this->getExecutionTime()]
            );
            
        } catch (FiscalException $e) {
            return $this->handleFiscalException($e, $operation);
        } catch (CertificateException $e) {
            return $this->handleCertificateException($e, $operation);
        } catch (SefazException $e) {
            return $this->handleSefazException($e, $operation);
        } catch (ValidationException $e) {
            return $this->handleValidationException($e, $operation);
        } catch (XmlException $e) {
            return $this->handleXmlException($e, $operation);
        } catch (\InvalidArgumentException $e) {
            return $this->handleValidationError($e, $operation);
        } catch (\RuntimeException $e) {
            return $this->handleRuntimeError($e, $operation);
        } catch (\LogicException $e) {
            return $this->handleLogicError($e, $operation);
        } catch (\Exception $e) {
            return $this->handleGenericError($e, $operation);
        } catch (\Throwable $e) {
            return $this->handleCriticalError($e, $operation);
        }
    }

    /**
     * Trata exceções fiscais específicas (classe base)
     */
    private function handleFiscalException(FiscalException $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            $e->getMessage(),
            $e->getErrorCode() ?: 'FISCAL_ERROR',
            $operation,
            array_merge([
                'severity' => 'error',
                'recoverable' => true,
                'exception_type' => get_class($e)
            ], $e->getContext())
        );
    }

    /**
     * Trata erros de certificado
     */
    private function handleCertificateException(CertificateException $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            $e->getMessage(),
            $e->getErrorCode(),
            $operation,
            array_merge([
                'severity' => 'critical',
                'recoverable' => true,
                'category' => 'certificate'
            ], $e->getContext())
        );
    }

    /**
     * Trata erros de SEFAZ
     */
    private function handleSefazException(SefazException $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            $e->getMessage(),
            $e->getErrorCode(),
            $operation,
            array_merge([
                'severity' => 'error',
                'recoverable' => true,
                'category' => 'sefaz'
            ], $e->getContext())
        );
    }

    /**
     * Trata erros de validação
     */
    private function handleValidationException(ValidationException $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            $e->getMessage(),
            $e->getErrorCode(),
            $operation,
            array_merge([
                'severity' => 'warning',
                'recoverable' => true,
                'category' => 'validation',
                'validation_errors' => $e->getValidationErrors()
            ], $e->getContext())
        );
    }

    /**
     * Trata erros de XML
     */
    private function handleXmlException(XmlException $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            $e->getMessage(),
            $e->getErrorCode(),
            $operation,
            array_merge([
                'severity' => 'error',
                'recoverable' => true,
                'category' => 'xml'
            ], $e->getContext())
        );
    }

    /**
     * Trata erros de validação (parâmetros inválidos)
     */
    private function handleValidationError(\InvalidArgumentException $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            'Dados inválidos: ' . $e->getMessage(),
            'VALIDATION_ERROR',
            $operation,
            [
                'severity' => 'warning',
                'recoverable' => true,
                'suggestions' => $this->getValidationSuggestions($e->getMessage())
            ]
        );
    }

    /**
     * Trata erros de runtime (certificado, conexão, etc.)
     */
    private function handleRuntimeError(\RuntimeException $e, string $operation): FiscalResponse
    {
        $errorCode = 'RUNTIME_ERROR';
        $metadata = ['severity' => 'error', 'recoverable' => false];

        // Mapeia erros específicos
        if (str_contains($e->getMessage(), 'certificado')) {
            $errorCode = 'CERTIFICATE_ERROR';
            $metadata['suggestions'] = [
                'Verifique se o certificado está carregado',
                'Confirme se o certificado não expirou',
                'Valide a senha do certificado'
            ];
        } elseif (str_contains($e->getMessage(), 'SEFAZ')) {
            $errorCode = 'SEFAZ_ERROR';
            $metadata['suggestions'] = [
                'Verifique conexão com internet',
                'Confirme se SEFAZ está operacional',
                'Tente novamente em alguns minutos'
            ];
        } elseif (str_contains($e->getMessage(), 'XML')) {
            $errorCode = 'XML_ERROR';
            $metadata['suggestions'] = [
                'Verifique estrutura dos dados',
                'Confirme campos obrigatórios',
                'Valide formato dos valores'
            ];
        }

        return FiscalResponse::error(
            $e->getMessage(),
            $errorCode,
            $operation,
            $metadata
        );
    }

    /**
     * Trata erros de lógica (fluxo incorreto)
     */
    private function handleLogicError(\LogicException $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            'Erro de fluxo: ' . $e->getMessage(),
            'LOGIC_ERROR',
            $operation,
            [
                'severity' => 'error',
                'recoverable' => true,
                'suggestions' => [
                    'Verifique sequência de operações',
                    'Confirme estado dos objetos',
                    'Revise lógica de negócio'
                ]
            ]
        );
    }

    /**
     * Trata erros genéricos
     */
    private function handleGenericError(\Exception $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            'Erro na operação: ' . $e->getMessage(),
            'GENERAL_ERROR',
            $operation,
            [
                'severity' => 'error',
                'recoverable' => false,
                'exception_type' => get_class($e),
                'trace_summary' => $this->getTraceSummary($e)
            ]
        );
    }

    /**
     * Trata erros críticos (Throwable)
     */
    private function handleCriticalError(\Throwable $e, string $operation): FiscalResponse
    {
        return FiscalResponse::error(
            'Erro crítico no sistema fiscal',
            'CRITICAL_ERROR',
            $operation,
            [
                'severity' => 'critical',
                'recoverable' => false,
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'trace_id' => uniqid('critical_'),
                'support_info' => 'Entre em contato com o suporte técnico'
            ]
        );
    }

    /**
     * Gera sugestões baseadas na mensagem de validação
     */
    private function getValidationSuggestions(string $message): array
    {
        $suggestions = [];

        if (str_contains($message, 'chave')) {
            $suggestions[] = 'Chave de acesso deve ter exatamente 44 dígitos';
        }
        
        if (str_contains($message, 'CNPJ')) {
            $suggestions[] = 'CNPJ deve ter 14 dígitos';
        }
        
        if (str_contains($message, 'motivo') || str_contains($message, 'justificativa')) {
            $suggestions[] = 'Motivo deve ter pelo menos 15 caracteres';
        }

        if (str_contains($message, 'valor')) {
            $suggestions[] = 'Verifique formato numérico dos valores';
        }

        return $suggestions ?: ['Verifique os dados informados'];
    }

    /**
     * Gera resumo do trace para debug
     */
    private function getTraceSummary(\Throwable $e): array
    {
        $trace = $e->getTrace();
        $summary = [];
        
        // Pega apenas os 3 primeiros níveis do trace
        for ($i = 0; $i < min(3, count($trace)); $i++) {
            $frame = $trace[$i];
            $summary[] = [
                'file' => basename($frame['file'] ?? 'unknown'),
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown'
            ];
        }
        
        return $summary;
    }

    /**
     * Calcula tempo de execução (mock para agora)
     */
    private function getExecutionTime(): float
    {
        return microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    }

    /**
     * Executa callback com timeout
     * 
     * @param callable $callback
     * @param int $timeoutSeconds
     * @param string $operation
     * @return FiscalResponse
     */
    public function handleWithTimeout(callable $callback, int $timeoutSeconds, string $operation): FiscalResponse
    {
        $startTime = time();
        
        try {
            // PHP não tem timeout nativo para operações, mas podemos simular
            $result = $callback();
            
            if (time() - $startTime > $timeoutSeconds) {
                return FiscalResponse::error(
                    'Operação excedeu tempo limite de ' . $timeoutSeconds . ' segundos',
                    'TIMEOUT_ERROR',
                    $operation
                );
            }
            
            return $this->handle(fn() => $result, $operation);
            
        } catch (\Throwable $e) {
            return $this->handle(fn() => throw $e, $operation);
        }
    }

    /**
     * Valida resposta de API externa (SEFAZ, etc.)
     */
    public function validateApiResponse(string $xmlResponse, string $operation): FiscalResponse
    {
        try {
            if (empty($xmlResponse)) {
                return FiscalResponse::error(
                    'Resposta vazia da API',
                    'EMPTY_RESPONSE',
                    $operation
                );
            }

            // Verifica se é XML válido
            $dom = new \DOMDocument();
            if (!$dom->loadXML($xmlResponse)) {
                return FiscalResponse::error(
                    'Resposta inválida da API (XML malformado)',
                    'INVALID_XML_RESPONSE',
                    $operation
                );
            }

            // Verifica se tem erro na resposta
            $xpath = new \DOMXPath($dom);
            $errorNodes = $xpath->query('//erro | //error | //fault');
            
            if ($errorNodes->length > 0) {
                $errorMessage = $errorNodes->item(0)->textContent;
                return FiscalResponse::error(
                    'Erro retornado pela API: ' . $errorMessage,
                    'API_ERROR',
                    $operation
                );
            }

            return FiscalResponse::success([
                'xml' => $xmlResponse,
                'validated' => true
            ], $operation);

        } catch (\Throwable $e) {
            return FiscalResponse::fromException($e, $operation);
        }
    }

    /**
     * Converte exceções comuns em exceções fiscais específicas
     */
    public function convertToFiscalException(\Exception $e): FiscalException
    {
        $message = $e->getMessage();
        
        if (str_contains($message, 'certificado') || str_contains($message, 'certificate')) {
            return CertificateException::notLoaded()->withContext(['original_exception' => get_class($e)]);
        }
        
        if (str_contains($message, 'SEFAZ') || str_contains($message, 'webservice')) {
            return SefazException::connectionFailed()->withContext(['original_exception' => get_class($e)]);
        }
        
        if (str_contains($message, 'XML') || str_contains($message, 'malformed')) {
            return XmlException::malformed($message)->withContext(['original_exception' => get_class($e)]);
        }
        
        if (str_contains($message, 'chave') || str_contains($message, 'CNPJ') || str_contains($message, 'CPF')) {
            return ValidationException::invalidValue('unknown', $message)->withContext(['original_exception' => get_class($e)]);
        }
        
        // Se não conseguir mapear, retorna FiscalException genérica
        return (new class($message, $e->getCode(), $e) extends FiscalException {})
            ->setErrorCode('UNKNOWN_ERROR')
            ->withContext(['original_exception' => get_class($e)]);
    }
}