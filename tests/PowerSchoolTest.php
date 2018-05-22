<?php

namespace GrantHolle\PowerSchool\Tests;

use GrantHolle\PowerSchool\Api\RequestBuilder;
use PHPUnit\Framework\TestCase;

class PowerSchoolTest extends TestCase
{
    protected $builder;

    public function setUp()
    {
        parent::setUp();

        $this->builder = new RequestBuilder('https://pstest.intlschools.net/', 'd374d27f-97ff-485a-b54d-a8b3acdda38c', 'd26b9711-d892-4129-881a-5f6b86d6ccbb');
    }

    /** @test */
    public function test_can_get_table_entries()
    {
        $response = $this->builder->table('u_isc_passport_entries')->get();

        dd($response);
    }
}
