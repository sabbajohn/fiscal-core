<?php

namespace freeline\FiscalCore\Adapters\NF\Nodes;

use freeline\FiscalCore\Adapters\NF\Core\NotaNodeInterface;
use freeline\FiscalCore\Adapters\NF\DTO\EmitenteDTO;
use NFePHP\NFe\Make;

/**
 * Node para tag <emit> (Emitente)
 */
class EmitenteNode implements NotaNodeInterface
{
    public function __construct(private EmitenteDTO $dto) {}
    
    public function addToMake(Make $make): void
    {
        $make->tagemit($this->dto->toStdClass());
    }
    
    public function validate(): bool
    {
        // Validação CNPJ (14 dígitos)
        if (!preg_match('/^\d{14}$/', $this->dto->cnpj)) {
            throw new \InvalidArgumentException('CNPJ inválido');
        }
        
        if (empty($this->dto->razaoSocial)) {
            throw new \InvalidArgumentException('Razão social é obrigatória');
        }
        
        if (empty($this->dto->inscricaoEstadual)) {
            throw new \InvalidArgumentException('Inscrição estadual é obrigatória');
        }
        
        if (!in_array($this->dto->crt, [1, 2, 3])) {
            throw new \InvalidArgumentException('CRT inválido (deve ser 1, 2 ou 3)');
        }
        
        return true;
    }
    
    public function getNodeType(): string
    {
        return 'emitente';
    }
}
