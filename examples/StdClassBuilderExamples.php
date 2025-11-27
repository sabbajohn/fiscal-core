<?php

/**
 * EXEMPLO COMPARATIVO: 3 Formas de Criar stdClass
 * 
 * Este arquivo demonstra as diferentes formas de usar StdClassBuilder
 * para criar objetos stdClass de maneira eficiente.
 */

namespace Examples;

use freeline\FiscalCore\Adapters\NF\Helpers\StdClassBuilder;

class StdClassBuilderExamples
{
    /**
     * FORMA 1: create() - Array associativo explícito
     * 
     * Vantagens:
     * - Mais explícito e fácil de entender
     * - Melhor para IDEs (autocomplete funciona melhor)
     * - Sem overhead de performance
     * 
     * Desvantagens:
     * - Mais verboso (repetir nome da propriedade)
     */
    public function exemplo1_create_explicito()
    {
        $totais = (object)[
            'vBC' => 100.0,
            'vICMS' => 18.0,
            'vProd' => 100.0,
            'vFrete' => 0,
            'vDesc' => 0,
            'vNF' => 100.0
        ];
        
        // ❌ Antes: criar stdClass manualmente
        $obj1 = new \stdClass();
        $obj1->vBC = $totais->vBC;
        $obj1->vICMS = $totais->vICMS;
        $obj1->vProd = $totais->vProd;
        $obj1->vFrete = $totais->vFrete;
        $obj1->vDesc = $totais->vDesc;
        $obj1->vNF = $totais->vNF;
        
        // ✅ Depois: usando create()
        $obj2 = StdClassBuilder::create([
            'vBC' => $totais->vBC,
            'vICMS' => $totais->vICMS,
            'vProd' => $totais->vProd,
            'vFrete' => $totais->vFrete,
            'vDesc' => $totais->vDesc,
            'vNF' => $totais->vNF
        ]);
        
        return $obj2;
    }
    
    /**
     * FORMA 2: fromVars() + compact() - Quando você tem variáveis locais
     * 
     * Vantagens:
     * - Evita repetição de nomes
     * - compact() é função nativa do PHP (rápido)
     * - Útil quando variáveis já existem no escopo
     * 
     * Desvantagens:
     * - Precisa listar os nomes como strings
     * - Só funciona com variáveis locais
     */
    public function exemplo2_fromvars_compact()
    {
        // Variáveis locais
        $vBC = 100.0;
        $vICMS = 18.0;
        $vProd = 100.0;
        $vFrete = 0;
        $vDesc = 0;
        $vNF = 100.0;
        
        // ✅ Usando compact() - evita repetir valores
        $obj = StdClassBuilder::fromVars(compact(
            'vBC',
            'vICMS',
            'vProd',
            'vFrete',
            'vDesc',
            'vNF'
        ));
        
        return $obj;
    }
    
    /**
     * FORMA 3: props() - Captura nomes AUTOMATICAMENTE! (MAIS CONVENIENTE)
     * 
     * Vantagens:
     * - ZERO repetição - passa apenas os valores
     * - Funciona com variáveis, propriedades de objetos, arrays
     * - Código mais limpo e DRY (Don't Repeat Yourself)
     * 
     * Desvantagens:
     * - Usa debug_backtrace() - pequeno overhead de performance
     * - Não use em loops intensivos (milhares de iterações)
     */
    public function exemplo3_props_automatico()
    {
        $totais = (object)[
            'vBC' => 100.0,
            'vICMS' => 18.0,
            'vProd' => 100.0,
            'vFrete' => 0,
            'vDesc' => 0,
            'vNF' => 100.0
        ];
        
        // 🚀 FORMA MAIS SIMPLES - Nomes capturados automaticamente!
        $obj = StdClassBuilder::props(
            $totais->vBC,
            $totais->vICMS,
            $totais->vProd,
            $totais->vFrete,
            $totais->vDesc,
            $totais->vNF
        );
        
        // Resultado: stdClass com propriedades vBC, vICMS, vProd, vFrete, vDesc, vNF
        return $obj;
    }
    
    /**
     * COMPARAÇÃO DIRETA: Mesmo resultado, diferentes formas
     */
    public function comparacao_completa()
    {
        $totais = (object)[
            'vRetPIS' => 1.65,
            'vRetCOFINS' => 7.60,
            'vRetCSLL' => 0,
            'vBCIRRF' => 0,
            'vIRRF' => 0,
            'vBCRetPrev' => 0,
            'vRetPrev' => 0
        ];
        
        // FORMA 1: create() - 7 linhas repetindo nomes
        $obj1 = StdClassBuilder::create([
            'vRetPIS' => $totais->vRetPIS,
            'vRetCOFINS' => $totais->vRetCOFINS,
            'vRetCSLL' => $totais->vRetCSLL,
            'vBCIRRF' => $totais->vBCIRRF,
            'vIRRF' => $totais->vIRRF,
            'vBCRetPrev' => $totais->vBCRetPrev,
            'vRetPrev' => $totais->vRetPrev
        ]);
        
        // FORMA 3: props() - 7 linhas SEM repetir nomes! 🎉
        $obj2 = StdClassBuilder::props(
            $totais->vRetPIS,
            $totais->vRetCOFINS,
            $totais->vRetCSLL,
            $totais->vBCIRRF,
            $totais->vIRRF,
            $totais->vBCRetPrev,
            $totais->vRetPrev
        );
        
        // Ambos produzem o mesmo resultado!
        assert($obj1 == $obj2);
        
        return $obj2;
    }
    
    /**
     * RECOMENDAÇÕES DE USO
     */
    public function recomendacoes()
    {
        return [
            'create()' => 'Use quando precisar de máxima clareza e performance',
            'fromVars() + compact()' => 'Use quando tem variáveis locais (não propriedades)',
            'props()' => 'Use para máxima conveniência - elimina repetição!',
            
            'Evite props() em:' => 'Loops com milhares de iterações (use create())',
            'Prefira props() em:' => 'Construção de objetos únicos (Nodes, DTOs)',
        ];
    }
    
    /**
     * EXEMPLO REAL: TotaisNode
     */
    public function exemplo_real_totais_node()
    {
        $totais = (object)[
            'vRetPIS' => 1.65,
            'vRetCOFINS' => 7.60,
            'vRetCSLL' => 0,
            'vBCIRRF' => 0,
            'vIRRF' => 0,
            'vBCRetPrev' => 0,
            'vRetPrev' => 0
        ];
        
        // No TotaisNode, dentro do método addToMake():
        
        // ❌ ANTES - muito verboso
        /*
        $retTrib = new \stdClass();
        $retTrib->vRetPIS = $this->totais->vRetPIS;
        $retTrib->vRetCOFINS = $this->totais->vRetCOFINS;
        $retTrib->vRetCSLL = $this->totais->vRetCSLL;
        $retTrib->vBCIRRF = $this->totais->vBCIRRF;
        $retTrib->vIRRF = $this->totais->vIRRF;
        $retTrib->vBCRetPrev = $this->totais->vBCRetPrev;
        $retTrib->vRetPrev = $this->totais->vRetPrev;
        $make->tagretTrib($retTrib);
        */
        
        // ✅ DEPOIS - limpo e direto
        $make = new \stdClass(); // Mock para exemplo
        $make->tagretTrib = StdClassBuilder::props(
            $totais->vRetPIS,
            $totais->vRetCOFINS,
            $totais->vRetCSLL,
            $totais->vBCIRRF,
            $totais->vIRRF,
            $totais->vBCRetPrev,
            $totais->vRetPrev
        );
        
        return $make->tagretTrib;
    }
}
