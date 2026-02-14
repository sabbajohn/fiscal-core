<?php

namespace freeline\FiscalCore\Providers\NFSe;

/**
 * Mapeamento de Municípios para Providers NFSe
 * 
 * Este arquivo centraliza o mapeamento entre códigos IBGE de municípios
 * e os providers/padrões NFSe correspondentes.
 * 
 * Baseado em: Schemas/NFse/WSDL/Webservice.xml
 */
class MunicipioProviderMap
{
    /**
     * Mapeamento de código IBGE => Provider Class
     * 
     * @var array<string, string>
     */
    private static array $municipioMap = [
        // Santa Catarina
        '4209102' => PublicaProvider::class,  // Joinville - SC
        '4205407' => AbrasfV2Provider::class, // Florianópolis - SC (SOFTPLAN)
        '4208203' => PublicaProvider::class,  // Itajaí - SC
        '4202909' => AbrasfV2Provider::class, // Brusque - SC (IPM)
        
        // Paraná  
        '4106902' => EgoverneProvider::class, // Curitiba - PR
        '4108304' => AbrasfV2Provider::class, // Foz do Iguaçu - PR (WEBISS)
        '4119152' => AbrasfV2Provider::class, // Pinhais - PR (IPM)
        
        // Pará
        '1501402' => DsfProvider::class,      // Belém - PA
        
        // São Paulo
        '3550308' => AbrasfV2Provider::class, // São Paulo - SP (PAULISTANA)
        '3534401' => AbrasfV2Provider::class, // Osasco - SP (EGOVERNEISS)
        '3515004' => AbrasfV2Provider::class, // Embu das Artes - SP (GIAP)
        
        // Rio de Janeiro
        '3304557' => AbrasfV2Provider::class, // Rio de Janeiro - RJ (CARIOCA)
        '3303302' => AbrasfV2Provider::class, // Niterói - RJ (TIPLAN)
        
        // Minas Gerais
        '3106200' => AbrasfV2Provider::class, // Belo Horizonte - MG (BHISS)
        '3143302' => AbrasfV2Provider::class, // Montes Claros - MG (PRONIN)
        
        // Rio Grande do Sul  
        '4314902' => AbrasfV2Provider::class, // Porto Alegre - RS (BHISS)
        '4314100' => AbrasfV2Provider::class, // Passo Fundo - RS (THEMA)
        
        // Bahia
        '2927408' => AbrasfV2Provider::class, // Salvador - BA
        
        // Distrito Federal
        '5300108' => AbrasfV2Provider::class, // Brasília - DF
    ];
    
    /**
     * Mapeamento de padrões para providers
     * 
     * @var array<string, string>
     */
    private static array $padraoMap = [
        'PUBLICA'          => PublicaProvider::class,
        'DSF'              => DsfProvider::class,
        'EGOVERNE'         => EgoverneProvider::class,
        'EGOVERNEISS'      => EgoverneProvider::class,
        'ABRASF'           => AbrasfV2Provider::class,
        'BETHA'            => AbrasfV2Provider::class,
        'IPM'              => AbrasfV2Provider::class,
        'WEBISS'           => AbrasfV2Provider::class,
        'BHISS'            => AbrasfV2Provider::class,
        'TIPLAN'           => AbrasfV2Provider::class,
        'THEMA'            => AbrasfV2Provider::class,
        'PRONIN'           => AbrasfV2Provider::class,
        'SOFTPLAN'         => AbrasfV2Provider::class,
        'SIMPLISS'         => AbrasfV2Provider::class,
        'ISSNET'           => AbrasfV2Provider::class,
        'GINFES'           => AbrasfV2Provider::class,
        'NACIONAL'         => NacionalProvider::class,
    ];
    
    /**
     * Resolve o provider para um município específico
     * 
     * @param string $codigoMunicipio Código IBGE do município (7 dígitos)
     * @return string Nome da classe do provider
     * @throws \InvalidArgumentException Se município não encontrado
     */
    public static function resolveProvider(string $codigoMunicipio): string
    {
        // Remove zeros à esquerda e reaplica padding
        $codigo = str_pad(ltrim($codigoMunicipio, '0'), 7, '0', STR_PAD_LEFT);
        
        if (isset(self::$municipioMap[$codigo])) {
            return self::$municipioMap[$codigo];
        }
        
        // Se não encontrado, tenta usar provider nacional
        return NacionalProvider::class;
    }
    
    /**
     * Resolve provider por padrão (quando não há mapeamento específico)
     * 
     * @param string $padrao Nome do padrão (ex: 'PUBLICA', 'DSF', etc)
     * @return string Nome da classe do provider
     */
    public static function resolveProviderByPadrao(string $padrao): string
    {
        $padraoUpper = strtoupper($padrao);
        
        if (isset(self::$padraoMap[$padraoUpper])) {
            return self::$padraoMap[$padraoUpper];
        }
        
        // Fallback para ABRASF v2 (padrão mais comum)
        return AbrasfV2Provider::class;
    }
    
    /**
     * Obtém informações do município
     * 
     * @param string $codigoMunicipio Código IBGE
     * @return array{codigo: string, provider: string, padrao: string}|null
     */
    public static function getMunicipioInfo(string $codigoMunicipio): ?array
    {
        $codigo = str_pad(ltrim($codigoMunicipio, '0'), 7, '0', STR_PAD_LEFT);
        
        if (!isset(self::$municipioMap[$codigo])) {
            return null;
        }
        
        $providerClass = self::$municipioMap[$codigo];
        $padrao = array_search($providerClass, self::$padraoMap, true);
        
        return [
            'codigo' => $codigo,
            'provider' => $providerClass,
            'padrao' => $padrao ?: 'CUSTOM',
        ];
    }
    
    /**
     * Lista todos os municípios suportados
     * 
     * @return array<string, string>
     */
    public static function getMunicipiosSuportados(): array
    {
        return self::$municipioMap;
    }
    
    /**
     * Verifica se um município é suportado
     * 
     * @param string $codigoMunicipio
     * @return bool
     */
    public static function isMunicipioSuportado(string $codigoMunicipio): bool
    {
        $codigo = str_pad(ltrim($codigoMunicipio, '0'), 7, '0', STR_PAD_LEFT);
        return isset(self::$municipioMap[$codigo]);
    }
}
