<?php

namespace Tests\Unit\NFSe;

use freeline\FiscalCore\Providers\NFSe\NacionalProvider;
use PHPUnit\Framework\TestCase;

class NacionalProviderTest extends TestCase
{
    public function test_emitir_monta_xml_e_envia_para_endpoint_correto(): void
    {
        $calls = [];
        $provider = new NacionalProvider($this->buildConfig(function ($method, $path, $body, $headers = []) use (&$calls) {
            $calls[] = compact('method', 'path', 'body');
            return '<Resposta><Sucesso>true</Sucesso><NumeroNfse>123</NumeroNfse></Resposta>';
        }));

        $response = $provider->emitir($this->dadosValidos());

        $this->assertStringContainsString('<Sucesso>true</Sucesso>', $response);
        $this->assertSame('POST', $calls[0]['method']);
        $this->assertSame('/nfse/emitir', $calls[0]['path']);
        $this->assertStringContainsString('<GerarNfseEnvio', $calls[0]['body']);
        $this->assertStringContainsString('InfDeclaracaoPrestacaoServico', $calls[0]['body']);
    }

    public function test_cancelar_retorna_true_quando_resposta_indica_sucesso(): void
    {
        $provider = new NacionalProvider($this->buildConfig(
            fn ($method = null, $path = null, $body = null, $headers = []) => '<CancelarResposta><Sucesso>true</Sucesso></CancelarResposta>'
        ));

        $result = $provider->cancelar('NFSE123', 'Erro operacional');
        $this->assertTrue($result);
    }

    public function test_consultar_retorno_compnfse_com_xml_real(): void
    {
        $xmlReferencia = file_get_contents(__DIR__ . '/../../../ConsultaNfseExterno.xml');
        $this->assertNotFalse($xmlReferencia);

        $provider = new NacionalProvider($this->buildConfig(
            fn ($method = null, $path = null, $body = null, $headers = []) => (string) $xmlReferencia
        ));

        $result = $provider->cancelar('NFSE123', 'Erro operacional');
        $this->assertTrue($result);
    }

    public function test_consultar_por_rps_valida_campos_obrigatorios(): void
    {
        $provider = new NacionalProvider($this->buildConfig(fn ($method = null, $path = null, $body = null, $headers = []) => '<ok/>'));
        $this->expectException(\InvalidArgumentException::class);

        $provider->consultarPorRps(['numero' => 1]);
    }

    private function buildConfig(callable $httpClient): array
    {
        return [
            'codigo_municipio' => '3550308',
            'versao' => '1.00',
            'ambiente' => 'homologacao',
            'api_base_url' => 'https://api.local',
            'timeout' => 10,
            'auth' => ['token' => 'abc'],
            'endpoints' => [
                'emitir' => '/nfse/emitir',
                'consultar' => '/nfse/consultar',
                'cancelar' => '/nfse/cancelar',
                'substituir' => '/nfse/substituir',
                'consultar_rps' => '/nfse/consultar-rps',
                'consultar_lote' => '/nfse/consultar-lote',
                'baixar_xml' => '/nfse/download/xml',
                'baixar_danfse' => '/nfse/download/danfse',
            ],
            'http_client' => $httpClient,
            'cache_dir' => sys_get_temp_dir() . '/fiscal-core-provider-' . uniqid(),
        ];
    }

    private function dadosValidos(): array
    {
        return [
            'prestador' => [
                'cnpj' => '11.222.333/0001-81',
                'inscricaoMunicipal' => '12345',
            ],
            'tomador' => [
                'documento' => '12345678901',
                'razaoSocial' => 'Tomador Teste',
            ],
            'servico' => [
                'codigo' => '0107',
                'discriminacao' => 'Serviço de desenvolvimento',
                'aliquota' => 0.02,
            ],
            'valor_servicos' => 1000.00,
            'rps_numero' => '10',
            'rps_serie' => 'A1',
            'rps_tipo' => '1',
        ];
    }
}
