<?php

namespace WeBirr\Tests;

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WeBirr\Bill;
use WeBirr\Payment;
use WeBirr\SupportedBank;
use WeBirr\WeBirrClient;

class WeBirrTest extends TestCase
{
    public function testCreateBillUsesClientMerchantIdBeforeSending()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('merchant-from-client', 'x', true);

        $api->createBill($bill);

        $this->assertSame('merchant-from-client', $bill->merchantID);
    }

    public function testPrepareBillDoesNotOverwriteExistingMerchantIdWhenClientMerchantIdIsEmpty()
    {
        $bill = $this->sampleBill();
        $bill->merchantID = 'merchant-on-bill';
        $api = new WeBirrClient('', 'x', true);

        $this->prepareBill($api, $bill);

        $this->assertSame('merchant-on-bill', $bill->merchantID);
    }

    public function testQueryIncludesMerchantIdForAllEndpointParameterShapesWhenConfigured()
    {
        $api = new WeBirrClient('merchant-from-client', 'x', true);

        foreach ($this->endpointQueryParams() as $endpoint => $params) {
            parse_str($this->queryString($api, $params), $query);

            $this->assertSame('merchant-from-client', $query['merchant_id'] ?? null, $endpoint);
        }
    }

    public function testQueryOmitsMerchantIdForAllEndpointParameterShapesWhenClientMerchantIdIsEmpty()
    {
        $api = new WeBirrClient('', 'x', true);

        foreach ($this->endpointQueryParams() as $endpoint => $params) {
            parse_str($this->queryString($api, $params), $query);

            $this->assertArrayNotHasKey('merchant_id', $query, $endpoint);
        }
    }

    public function testConstructorCanUseInjectedGuzzleClientForRequests()
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], '{"error":null,"res":"OK"}')
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));
        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.webirr.net/'
        ]);
        $api = new WeBirrClient('merchant-from-client', 'x', true, $client);

        $res = $api->deleteBill('123 456 789');

        $this->assertEmpty($res->error);
        $this->assertSame('OK', $res->res);
        $this->assertCount(1, $history);
        $this->assertSame('DELETE', $history[0]['request']->getMethod());
        $uri = (string)$history[0]['request']->getUri();
        $this->assertStringContainsString('merchant_id=merchant-from-client', $uri);
        $this->assertStringContainsString('wbc_code=123%20456%20789', $uri);
    }

    public function testTestEnvDefaultsToDevGatewayAndIgnoresInjectedBaseUri()
    {
        $history = [];
        $api = new WeBirrClient(
            'merchant-from-client',
            'x',
            true,
            $this->mockClient($history, 'https://should-not-be-used.example/')
        );

        $api->deleteBill('123 456 789');

        $uri = (string)$history[0]['request']->getUri();
        $this->assertStringStartsWith('https://api.webirr.dev/einvoice/api/bill?', $uri);
    }

    public function testTestEnvCanUseInternalGatewayUrlOverride()
    {
        $this->withGatewayUrl('https://local-gateway.example/', function () {
            $history = [];
            $api = new WeBirrClient(
                'merchant-from-client',
                'x',
                true,
                $this->mockClient($history, 'https://should-not-be-used.example/')
            );

            $api->deleteBill('123 456 789');

            $uri = (string)$history[0]['request']->getUri();
            $this->assertStringStartsWith('https://local-gateway.example/einvoice/api/bill?', $uri);
        });
    }

    public function testProductionIgnoresInternalGatewayUrlOverride()
    {
        $this->withGatewayUrl('https://local-gateway.example/', function () {
            $history = [];
            $api = new WeBirrClient(
                'merchant-from-client',
                'x',
                false,
                $this->mockClient($history, 'https://should-not-be-used.example/')
            );

            $api->deleteBill('123 456 789');

            $uri = (string)$history[0]['request']->getUri();
            $this->assertStringStartsWith('https://api.webirr.net:8080/einvoice/api/bill?', $uri);
        });
    }

    public function testGetSupportedBanksUsesCanonicalEndpoint()
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], '{"error":null,"res":[{"bankID":"cbe_mobile","name":"CBE Mobile Banking"}]}')
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));
        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://api.webirr.net/'
        ]);
        $api = new WeBirrClient('merchant-from-client', 'x', true, $client);

        $res = $api->getSupportedBanks();
        $bank = new SupportedBank($res->res[0]);

        $this->assertEmpty($res->error);
        $this->assertSame('cbe_mobile', $bank->bankID);
        $this->assertSame('CBE Mobile Banking', $bank->name);
        $this->assertCount(1, $history);
        $this->assertSame('GET', $history[0]['request']->getMethod());
        $uri = (string)$history[0]['request']->getUri();
        $this->assertStringContainsString('einvoice/api/banks', $uri);
        $this->assertStringContainsString('merchant_id=merchant-from-client', $uri);
        $this->assertStringContainsString('api_key=x', $uri);
    }

    public function testSupportedBankMapsGatewayFields()
    {
        $bank = new SupportedBank((object)[
            'bankID' => 'telebirr',
            'name' => 'Telebirr'
        ]);

        $this->assertSame('telebirr', $bank->bankID);
        $this->assertSame('Telebirr', $bank->name);
    }

    public function testBillSerializesCustomerPhone()
    {
        $bill = $this->sampleBill();

        $this->assertSame('0911000000', $bill->toArray()['customerPhone']);
    }

    public function testBillSerializesWithoutCustomerPhone()
    {
        $bill = $this->sampleBill(false);

        $this->assertSame('', $bill->toArray()['customerPhone']);
    }

    public function testBillSerializesEmptyExtrasAsJsonObject()
    {
        $bill = $this->sampleBill();

        $this->assertSame('{"extras":{}}', json_encode(['extras' => $bill->toArray()['extras']]));
    }

    public function testBillSerializesPopulatedExtrasAsJsonObject()
    {
        $bill = $this->sampleBill();
        $bill->extras = ['source' => 'unit-test'];

        $this->assertSame('{"extras":{"source":"unit-test"}}', json_encode(['extras' => $bill->toArray()['extras']]));
    }

    public function testPaymentUsesPaymentDateAsTimeAlias()
    {
        $payment = new Payment((object)[
            'paymentDate' => '2026-06-12 10:11:12'
        ]);

        $this->assertSame('2026-06-12 10:11:12', $payment->paymentDate);
        $this->assertSame($payment->paymentDate, $payment->time);
    }

    public function testPaymentKeepsLegacyTimeAsPaymentDateAlias()
    {
        $payment = new Payment((object)[
            'time' => '2026-06-12 10:11:12'
        ]);

        $this->assertSame('2026-06-12 10:11:12', $payment->time);
        $this->assertSame($payment->time, $payment->paymentDate);
    }

    public function testCreateBillShouldGetErrorFromWebServiceOnInvalidApiKeyTestEnv()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->createBill($bill);

        $this->assertApiError($res);
    }

    public function testUpdateBillShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->updateBill($bill);

        $this->assertApiError($res);
    }

    public function testDeleteBillShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->deleteBill('xxxx');

        $this->assertApiError($res);
    }

    public function testGetPaymentStatusShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getPaymentStatus('xxxx');

        $this->assertApiError($res);
    }

    public function testGetBillByReferenceShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getBillByReference('missing-reference');

        $this->assertApiError($res);
    }

    public function testGetBillByPaymentCodeShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getBillByPaymentCode('xxxx');

        $this->assertApiError($res);
    }

    public function testGetBillsShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getBills(-1, '', 10);

        $this->assertApiError($res);
    }

    public function testGetPaymentsShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getPayments('20251231', 10);

        $this->assertApiError($res);
    }

    public function testGetStatShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getStat('2025-01-01', '2025-01-02');

        $this->assertApiError($res);
    }

    public function testGetSupportedBanksShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getSupportedBanks();

        $this->assertApiError($res);
    }

    private function sampleBill(bool $withCustomerPhone = true)
    {
        $bill = new Bill();
        $bill->amount = '270.90';
        $bill->customerCode = 'sdk-test-customer';
        $bill->customerName = 'SDK Test Customer';
        if ($withCustomerPhone) {
            $bill->customerPhone = '0911000000';
        }
        $bill->time = date('Y-m-d H:i');
        $bill->description = 'SDK test bill';
        $bill->billReference = 'php/unit/' . time();

        return $bill;
    }

    private function assertApiError($res)
    {
        $this->assertIsObject($res);
        $this->assertTrue(
            !empty($res->error) || !empty($res->errorCode),
            'Expected API error response.'
        );
    }

    private function mockClient(array &$history, string $baseUri): Client
    {
        $mock = new MockHandler([
            new Response(200, [], '{"error":null,"res":"OK"}')
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));

        return new Client([
            'handler' => $handlerStack,
            'base_uri' => $baseUri
        ]);
    }

    private function withGatewayUrl(string $value, callable $callback): void
    {
        $previous = getenv('GATEWAY_URL');
        putenv('GATEWAY_URL=' . $value);

        try {
            $callback();
        } finally {
            if ($previous === false) {
                putenv('GATEWAY_URL');
            } else {
                putenv('GATEWAY_URL=' . $previous);
            }
        }
    }

    private function endpointQueryParams(): array
    {
        return [
            'createBill' => [],
            'updateBill' => [],
            'deleteBill' => ['wbc_code' => '123 456 789'],
            'getPaymentStatus' => ['wbc_code' => '123 456 789'],
            'getBillByReference' => ['bill_reference' => 'php/unit/1'],
            'getBillByPaymentCode' => ['wbc_code' => '123 456 789'],
            'getBills' => ['payment_status' => -1, 'last_timestamp' => '20251231', 'limit' => 10],
            'getPayments' => ['last_timestamp' => '20251231', 'limit' => 10],
            'getSupportedBanks' => [],
            'getStat' => ['date_from' => '2025-01-01', 'date_to' => '2025-01-02'],
        ];
    }

    private function queryString(WeBirrClient $api, array $params = []): string
    {
        $method = new \ReflectionMethod(WeBirrClient::class, 'query');

        return $method->invoke($api, $params);
    }

    private function prepareBill(WeBirrClient $api, Bill $bill): Bill
    {
        $method = new \ReflectionMethod(WeBirrClient::class, 'prepareBill');

        return $method->invoke($api, $bill);
    }
}
