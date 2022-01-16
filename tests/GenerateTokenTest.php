<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class GenerateTokenTest extends TestCase
{

    /**
     * Function test to generate new token
     *
     * @return void
     */
    public function testGenerateToken()
    {
        $this->get('/generatetoken');

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            true,$response->status,
            'API key added successfully', $response->message,
        );

        $this->assertNotEmpty($response->api_key);
    }

}
