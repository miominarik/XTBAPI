<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class DownloadCurrencyTest extends TestCase
{

    /**
     * Function test if the token is correct
     *
     * @return void
     */
    public function testRightToken()
    {
        $this->get('/api/forexcurrency/564e1971233e098c26d412f2d4e652742355e616fed8ba88fc9750f869aac1c29cb944175c374a7b6769989aa7a4216198ee12f53bf7827850dfe28540587a97');

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            true,
            $response->status,
            'The data was successfully imported into the database.',
            $response->message
        );
    }

    /**
     * Function test if the token is incorrect
     *
     * @return void
     */
    public function testWrongToken()
    {
        $this->get('/api/forexcurrency/564e1971233e098c26d412f2d4e652742355e616fed8df88fc9750f869aac1c29cb944175c374a7b6769989aa7a4216198ee12f53bf7827850dfe28540587a97');

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            false,
            $response->status,
            'Incorrect login information',
            $response->message
        );
    }

    /**
     * Function test if token is missing
     *
     * @return void
     */
    public function testMissingToken()
    {
        $this->get('/api/forexcurrency');

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            false,
            $response->status,
            'Missing Token',
            $response->message
        );
    }

    /**
     * Function test if token is missing in API
     *
     * @return void
     */
    public function testMissingTokenApi()
    {
        $this->get('/api');

        $response = json_decode($this->response->getContent());

        $this->assertEquals(
            false,
            $response->status,
            'Missing Token',
            $response->message
        );
    }
}
