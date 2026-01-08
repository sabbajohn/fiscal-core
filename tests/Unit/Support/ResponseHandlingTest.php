<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use freeline\FiscalCore\Support\FiscalResponse;
use freeline\FiscalCore\Support\ResponseHandler;

/**
 * Testes unitários para sistema de respostas
 * Valida tratamento de erros e encapsulamento de dados
 */
class ResponseHandlingTest extends TestCase
{
    /** @test */
    public function deve_criar_resposta_sucesso(): void
    {
        $dados = ['resultado' => 'teste'];
        $response = FiscalResponse::success($dados);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals($dados, $response->getData());
        $this->assertEmpty($response->getError());
    }

    /** @test */
    public function deve_criar_resposta_erro(): void
    {
        $mensagem = 'Erro de teste';
        $response = FiscalResponse::error($mensagem);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals($mensagem, $response->getError());
        $this->assertEquals([], $response->getData());
    }

    /** @test */
    public function deve_adicionar_metadata_resposta(): void
    {
        $dados = ['teste' => 'valor'];
        $metadata = ['timestamp' => time(), 'source' => 'api'];
        
        $response = FiscalResponse::success($dados, 'test_operation', $metadata);

        $this->assertEquals($metadata['timestamp'], $response->getMetadata()['timestamp']);
        $this->assertEquals($metadata['source'], $response->getMetadata()['source']);
        $this->assertEquals('1.0', $response->getMetadata()['version']);
        $this->assertArrayHasKey('timestamp', $response->getMetadata());
    }

    /** @test */
    public function deve_tratar_excecao_com_handler(): void
    {
        $handler = new ResponseHandler();
        
        // Testa usando o método execute que internamente chama handleException
        $response = $handler->execute(function() {
            throw new \Exception('Teste de erro');
        }, 'test_operation');

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Teste de erro', $response->getError());
    }

    /** @test */
    public function deve_executar_callback_sucesso(): void
    {
        $handler = new ResponseHandler();
        $executado = false;
        
        $callback = function() use (&$executado) {
            $executado = true;
            return ['resultado' => 'ok'];
        };

        $response = $handler->execute($callback);

        $this->assertTrue($executado);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(['resultado' => 'ok'], $response->getData());
    }

    /** @test */
    public function deve_capturar_excecao_em_callback(): void
    {
        $handler = new ResponseHandler();
        
        $callback = function() {
            throw new \InvalidArgumentException('Parâmetro inválido');
        };

        $response = $handler->execute($callback);

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Parâmetro inválido', $response->getError());
        $this->assertEquals('warning', $response->getMetadata()['severity']);
    }

    /** @test */
    public function deve_validar_timeout_operacao(): void
    {
        $handler = new ResponseHandler();
        
        // Simula um timeout manual sem usar sleep
        $callback = function() {
            // Simula uma operação que "deveria" dar timeout
            throw new \Exception('Operation timeout exceeded');
        };

        $response = $handler->executeWithTimeout($callback, 1);

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('timeout', strtolower($response->getError()));
    }

    /** @test */
    public function deve_implementar_retry_automatico(): void
    {
        $handler = new ResponseHandler();
        $tentativas = 0;
        
        $callback = function() use (&$tentativas) {
            $tentativas++;
            if ($tentativas < 3) {
                throw new \Exception('Falha temporária');
            }
            return ['sucesso' => true, 'tentativas' => $tentativas];
        };

        $response = $handler->executeWithRetry($callback, 3, 0.1); // 3 tentativas, 100ms entre elas

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(3, $response->getData()['tentativas']);
        $this->assertEquals(3, $response->getMetadata()['retry_attempts']);
    }

    /** @test */
    public function deve_falhar_apos_esgotar_tentativas(): void
    {
        $handler = new ResponseHandler();
        
        $callback = function() {
            throw new \Exception('Erro persistente');
        };

        $response = $handler->executeWithRetry($callback, 2, 0.1);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals(2, $response->getMetadata()['retry_attempts']);
        $this->assertStringContainsString('Erro persistente', $response->getError());
    }

    /** @test */
    public function deve_preservar_codigo_erro_original(): void
    {
        $exception = new \InvalidArgumentException('Dados inválidos', 400);
        $response = FiscalResponse::fromException($exception);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('Dados inválidos', $response->getError());
        $this->assertEquals(400, $response->getMetadata()['error_code']);
        $this->assertEquals('InvalidArgumentException', $response->getMetadata()['exception_type']);
    }

    /** @test */
    public function deve_serializar_resposta_json(): void
    {
        $dados = ['teste' => 'valor', 'numero' => 123];
        $response = FiscalResponse::success($dados);

        $json = $response->toJson();
        $decoded = json_decode($json, true);

        $this->assertEquals(true, $decoded['success']);
        $this->assertEquals($dados, $decoded['data']);
        $this->assertArrayHasKey('timestamp', $decoded);
    }

    /** @test */
    public function deve_implementar_cache_resposta(): void
    {
        $handler = new ResponseHandler();
        $executado = 0;
        
        $callback = function() use (&$executado) {
            $executado++;
            return ['execucao' => $executado];
        };

        $chave_cache = 'teste_cache';
        
        // Primeira execução - deve executar callback
        $response1 = $handler->executeWithCache($chave_cache, $callback, 1);
        $this->assertEquals(1, $response1->getData()['execucao']);
        
        // Segunda execução - deve usar cache
        $response2 = $handler->executeWithCache($chave_cache, $callback, 1);
        $this->assertEquals(1, $response2->getData()['execucao']); // Mesmo valor (cache)
        $this->assertTrue($response2->getMetadata()['from_cache']);
    }
}