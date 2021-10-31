<?php

namespace Tests;

use GrantHolle\PowerSchool\Api\PowerSchoolApiServiceProvider;
use GrantHolle\PowerSchool\Api\Request;
use GrantHolle\PowerSchool\Api\RequestBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class PowerSchoolTest extends TestCase
{
    protected $accessor;

    const CACHE_KEY = 'powerschool_token';
    const AUTH_TOKEN = '672d3201-c925-4e74-9418-985463e60654';
    const CONSTRUCTOR_ARGS = [
        'https://test.powerschool.com',
        '45d8d083-40e1-43da-904f-94c91968523d',
        '86954e3d-49cb-41ba-80e9-cd17d4772f89',
        'powerschool_token',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->accessor = new AccessorToPrivate();
        Cache::forget(static::CACHE_KEY);
    }

    protected function getApplicationProviders($application)
    {
        return [
            PowerSchoolApiServiceProvider::class,
            CacheServiceProvider::class,
            FilesystemServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($application)
    {
        $application['config']->set('powerschool.server_address', 'https://test.powerschool.com');
        $application['config']->set('powerschool.client_id', '45d8d083-40e1-43da-904f-94c91968523d');
        $application['config']->set('powerschool.client_secret', '86954e3d-49cb-41ba-80e9-cd17d4772f89');
    }

    protected function getGuzzleMock(MockHandler $handler): Client
    {
        $handlerStack = HandlerStack::create($handler);

        return new Client(['handler' => $handlerStack]);
    }

    /**
     * @param MockHandler $handler
     * @param array $methods
     * @return Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRequestMock(MockHandler $handler, array $methods = ['getClient', 'authenticate'])
    {
        $mock = $this->getMockBuilder(Request::class)
            ->onlyMethods($methods)
            ->setConstructorArgs(self::CONSTRUCTOR_ARGS)
            ->getMock();

        $mock->expects($this->any())
            ->method('getClient')
            ->willReturn($this->getGuzzleMock($handler));

        return $mock;
    }

    /**
     * @param MockHandler|null $handler
     * @param null $requestMock
     * @return RequestBuilder
     */
    protected function getRequestBuilderMock(MockHandler $handler = null, $requestMock = null)
    {
        $requestMock = $requestMock ?: $this->getRequestMock($handler);

        $mock = $this->getMockBuilder(RequestBuilder::class)
            ->onlyMethods(['getRequest'])
            ->setConstructorArgs(self::CONSTRUCTOR_ARGS)
            ->getMock();

        $mock->expects($this->any())
            ->method('getRequest')
            ->willReturn($requestMock);

        return $mock;
    }

    protected function getAuthResponse()
    {
        $response = '{"access_token":"' . self::AUTH_TOKEN .'","expires_in":3600}';

        return new Response(200, [], $response);
    }

    public function test_can_create_request_builder_with_app()
    {
        $builder = app()->make(RequestBuilder::class);
        $this->assertInstanceOf(RequestBuilder::class, $builder);
    }

    public function test_valid_request_structure_for_tables()
    {
        $builder = app()->make(RequestBuilder::class);
        $table = 'u_customtable';
        $q = 'column1==value;column2==value';

        $builder->table($table)
            ->method('get')
            ->projection(['id', 'column1', 'column2'])
            ->pageSize(5)
            ->q($q)
            ->sort('string,null', true)
            ->buildRequestQuery();

        $options = $this->accessor->get($builder, 'options')();

        $this->assertEquals($table, $this->accessor->get($builder, 'table')());
        $this->assertEquals("/ws/schema/table/{$table}", $this->accessor->get($builder, 'endpoint')());
        $this->assertStringContainsString('projection=id,column1,column2', $options['query']);
        $this->assertStringContainsString('&', $options['query']);
        $this->assertStringContainsString('pagesize=5', $options['query']);
        $this->assertStringContainsString('sort=string,null', $options['query']);
        $this->assertStringContainsString('sortdescending=true', $options['query']);
    }

    public function test_valid_request_structure_for_named_queries()
    {
        $builder = app()->make(RequestBuilder::class);
        $query = 'com.organization.plugin_name.entity.query_name';
        $expectedJson = [
            'string' => 'value1',
            'number' => '1',
            'boolean' => '0',
            'null' => '',
        ];

        $builder->namedQuery($query)
            ->with([
                'string' => 'value1',
                'number' => 1,
                'boolean' => false,
                'null' => null,
            ])
            ->pageSize(10)
            ->page(2)
            ->filter('othernumber=lt=100')
            ->includeCount()
            ->buildRequestJson()
            ->buildRequestQuery();

        $options = $this->accessor->get($builder, 'options')();

        $this->assertEquals("/ws/schema/query/{$query}", $this->accessor->get($builder, 'endpoint')());
        $this->assertEquals(RequestBuilder::POST, $this->accessor->get($builder, 'method')());
        $this->assertEquals($expectedJson, $options['json']);
        $this->assertStringContainsString('page=2', $options['query']);
        $this->assertStringContainsString('$q=othernumber=lt=100', $options['query']);
        $this->assertStringContainsString('count=true', $options['query']);
        $this->assertStringNotContainsString('projection', $options['query']);
    }

    public function test_can_set_auth_token_and_cache_it()
    {
        $handler = new MockHandler([
            $this->getAuthResponse(),
        ]);

        $request = $this->getRequestMock($handler, ['getClient']);

        $request->authenticate();

        $this->assertEquals(self::AUTH_TOKEN, $this->accessor->get($request, 'authToken')());
        $this->assertEquals(self::AUTH_TOKEN, Cache::get(static::CACHE_KEY));
    }

    public function test_performs_auth_request_before_query()
    {
        $handler = new MockHandler([
            new Response(200)
        ]);

        $request = $this->getRequestMock($handler);

        $request->expects($this->once())
            ->method('authenticate')
            ->willReturnSelf();

        $builder = $this->getRequestBuilderMock(null, $request);

        $builder->table('u_table')->id(1)->get();
    }

    public function test_performs_auth_request_after_token_expires()
    {
        $handler = new MockHandler([
            new Response(401, ['WWW-Authenticate' => 'Bearer error="invalid_token",realm="PowerSchool",error_description="The access token has expired"']),
            $this->getAuthResponse(),
            new Response(200),
        ]);

        $request = $this->getRequestMock($handler);

        $request->expects($this->exactly(3))
            ->method('authenticate')
            ->willReturnSelf();

        $builder = $this->getRequestBuilderMock(null, $request);
        $builder->table('u_table')->id(1)->get();
    }

    public function test_throws_exception_on_errors()
    {
        $this->expectException(GuzzleException::class);

        $handler = new MockHandler([
            new Response(500),
        ]);

        $request = $this->getRequestMock($handler);

        $request->expects($this->any())
            ->method('authenticate')
            ->willReturnSelf();

        $builder = $this->getRequestBuilderMock(null, $request);
        $builder->table('u_table')->id(1)->get();
    }
}
