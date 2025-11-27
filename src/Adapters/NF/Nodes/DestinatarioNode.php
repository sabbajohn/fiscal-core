<?php

namespace freeline\FiscalCore\Adapters\NF\Nodes;

use freeline\FiscalCore\Adapters\NF\Core\NotaNodeInterface;
use freeline\FiscalCore\Adapters\NF\DTO\DestinatarioDTO;
use NFePHP\NFe\Make;

/**
 * Node para tag <dest> (Destinatário)
 */
class DestinatarioNode implements NotaNodeInterface
{
    public function __construct(private DestinatarioDTO $dto) {}
    
    public function addToMake(Make $make): void
    {
        $data = [
            'xNome' => $this->dto->nome,
            'indIEDest' => $this->dto->indIEDest,
        ];
        
        // CPF ou CNPJ
        if (strlen($this->dto->cpfCnpj) === 11) {
            $data['CPF'] = $this->dto->cpfCnpj;
        } else {
            $data['CNPJ'] = $this->dto->cpfCnpj;
        }
        
        // Endereço (opcional para consumidor final)
        if ($this->dto->logradouro) {
            $data['xLgr'] = $this->dto->logradouro;
            $data['nro'] = $this->dto->numero;
            $data['xCpl'] = $this->dto->complemento;
            $data['xBairro'] = $this->dto->bairro;
            $data['cMun'] = $this->dto->codigoMunicipio;
            $data['xMun'] = $this->dto->nomeMunicipio;
            $data['UF'] = $this->dto->uf;
            $data['CEP'] = $this->dto->cep;
            $data['cPais'] = $this->dto->codigoPais;
            $data['xPais'] = $this->dto->nomePais;
        }
        
        if ($this->dto->telefone) {
            $data['fone'] = $this->dto->telefone;
        }
        
        if ($this->dto->email) {
            $data['email'] = $this->dto->email;
        }
        
        $make->tagdest((object)$data);
    }
    
    public function validate(): bool
    {
        // Validação CPF (11) ou CNPJ (14)
        $len = strlen($this->dto->cpfCnpj);
        if (!in_array($len, [11, 14])) {
            throw new \InvalidArgumentException('CPF/CNPJ inválido');
        }
        
        if (empty($this->dto->nome)) {
            throw new \InvalidArgumentException('Nome do destinatário é obrigatório');
        }
        
        // indIEDest: 1=Contribuinte, 2=Isento, 9=Não contribuinte
        if (!in_array($this->dto->indIEDest, [1, 2, 9])) {
            throw new \InvalidArgumentException('indIEDest inválido');
        }
        
        return true;
    }
    
    public function getNodeType(): string
    {
        return 'destinatario';
    }
}
