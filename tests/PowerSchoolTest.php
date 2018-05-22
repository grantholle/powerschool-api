<?php

namespace GrantHolle\PowerSchool\Tests;

use GrantHolle\PowerSchool\Api\RequestBuilder;
use PHPUnit\Framework\TestCase;

class PowerSchoolTest extends TestCase
{
    protected $builder;

    public function setUp()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__);
        $dotenv->load();

        $this->builder = new RequestBuilder(getenv('SERVER'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));
    }

    /** @test */
    public function test_can_get_table_entries()
    {
        $response = $this->builder->table('u_isc_passport_entries')->get();

        dd($response);
    }
}
