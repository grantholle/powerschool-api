<?php

namespace Tests;

use GrantHolle\PowerSchool\Api\PowerSchoolApiServiceProvider;
use GrantHolle\PowerSchool\Api\Request;
use GrantHolle\PowerSchool\Api\RequestBuilder;
use GrantHolle\PowerSchool\Api\Response as ApiResponse;
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

    public function test_response_can_infer_record_key_data()
    {
        $data = [
            "name" => "users",
            'record' => [
                [
                    "_name" => "users",
                    "last_name" => "x",
                    "teachernumber" => "x",
                    "dcid" => "x",
                    "schoolid" => "x",
                ],
                [
                    "_name" => "users",
                    "last_name" => "x",
                    "dcid" => "x",
                    "schoolid" => "x",
                ],
            ],
            "@extensions" => "erpfields,userscorefields"
        ];

        $response = new ApiResponse($data, 'record');

        $this->assertCount(2, $response);
        $this->assertEquals(['erpfields', 'userscorefields'], $response->extensions);
        $this->assertEquals(['name' => 'users'], $response->getMeta());

        foreach ($response as $item) {
            $this->assertArrayHasKey('dcid', $item);
        }
    }

    public function test_response_can_infer_endpoint_key_data()
    {
        $data = [
            'students' => [
                'student' => [
                    [
                        "id" => "1",
                        "local_id" => "1",
                        "student_username" => "x",
                    ],
                    [
                        "id" => "2",
                        "local_id" => "2",
                        "student_username" => "x",
                    ],
                ],
                "@expansions" => "demographics, addresses, alerts, phones, school_enrollment, ethnicity_race, contact, contact_info, initial_enrollment, schedule_setup, fees, lunch, global_id",
                "@extensions" => "u_prntrsvlunchwyis,u_docbox_extension,u_tienet_alerts,c_studentlocator,u_mba_report_cards,s_stu_crosslea_x,u_studentsuserfields,u_isc_passport_students,u_admissions_students_extension,u_powermenu_plus_extension,s_stu_crdc_x,s_stu_x,activities,u_re_enrollment_extension,u_students_extension,s_stu_ncea_x,s_stu_edfi_x,studentcorefields",
            ],
        ];

        $response = new ApiResponse($data, 'student');

        $this->assertCount(2, $response);
        $this->assertNotEmpty($response->extensions);
        $this->assertNotEmpty($response->expansions);

        foreach ($response as $item) {
            $this->assertArrayHasKey('local_id', $item);
        }
    }

    public function test_response_can_infer_single_endpoint_key_data()
    {
        $data = [
            "school" => [
                "@expansions" => "one, two, three",
                "@extensions" => "one,two,three",
                "id" => 10,
                "name" => "My school name",
                "school_number" => 100,
                "low_grade" => 0,
                "high_grade" => 12,
                "alternate_school_number" => 0,
                "addresses" => [],
                "phones" => [],
                "principal" => [],
            ],
        ];

        $response = new ApiResponse($data, '10');

        $this->assertCount(9, array_keys($response->toArray()));
        $this->assertCount(3, $response->extensions);
        $this->assertCount(3, $response->expansions);

        $this->assertEquals($data['school']['name'], $response['name']);
        $this->assertEquals($data['school']['school_number'], $response['school_number']);
        $this->assertEquals($data['school']['high_grade'], $response['high_grade']);
    }

    public function test_response_can_infer_students_contact_response()
    {
        $data = [
            [
                "contactId" => 1,
                "firstName" => 'John',
                "middleName" => null,
                "lastName" => 'Doe',
                "prefix" => null,
                "suffix" => null,
                "gender" => null,
                "employer" => null,
                "stateContactNumber" => null,
                "contactNumber" => null,
                "stateExcludeFromReporting" => false,
                "active" => true,
                "emails" => [],
                "phones" => [],
                "language" => null,
                "contactAccount" => [],
                "addresses" => [],
                "contactStudents" => [],
                "mergedIds" => [],
                "mergeAccountId" => null,
                "@extensions" => "personcorefields",
            ],
            [
                "contactId" => 2,
                "firstName" => 'Jane',
                "middleName" => null,
                "lastName" => 'Doe',
                "prefix" => null,
                "suffix" => null,
                "gender" => null,
                "employer" => null,
                "stateContactNumber" => null,
                "contactNumber" => null,
                "stateExcludeFromReporting" => false,
                "active" => true,
                "emails" => [],
                "phones" => [],
                "language" => null,
                "contactAccount" => null,
                "addresses" => [],
                "contactStudents" => [],
                "mergedIds" => [],
                "mergeAccountId" => null,
                "@extensions" => "personcorefields",
            ]
        ];

        $response = new ApiResponse($data, 123);

        $this->assertCount(2, array_keys($response->toArray()));
        $this->assertCount(0, $response->extensions);
        $this->assertCount(0, $response->expansions);

        foreach ($response as $item) {
            $this->assertArrayHasKey('contactId', $item);
        }
    }

    public function test_response_can_infer_pq_key_data()
    {
        $data = [
            "record" => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
            "@extensions" => "",
        ];

        $response = new ApiResponse($data, 'record');

        $this->assertCount(3, $response);
        $this->assertEmpty($response->extensions);
        $this->assertEmpty($response->expansions);

        foreach ($response as $item) {
            $this->assertArrayHasKey('id', $item);
        }
    }
}
