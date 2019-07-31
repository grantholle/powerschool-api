<?php

namespace GrantHolle\PowerSchool\Tests;

use GrantHolle\PowerSchool\Api\RequestBuilder;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class PowerSchoolTest extends TestCase
{
    protected $builder;

    // public function setUp()
    // {
    //     $dotenv = new Dotenv(__DIR__ . '/../');
    //     $dotenv->load();

    //     $this->builder = new RequestBuilder(getenv('SERVER'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));
    // }

    // /** @test */
    // public function test_can_get_table_entries()
    // {
    //     $response = $this->builder->table(getenv('TABLE'))->get();

    //     $this->assertObjectHasAttribute('record', $response);
    // }
}
