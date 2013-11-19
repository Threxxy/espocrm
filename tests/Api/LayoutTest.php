<?php

namespace tests\Api;

require_once('tests/testBootstrap.php');


class LayoutTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
    	require_once('tests/Api/RestTesterClass.php');
        $this->fixture = new RestTesterClass();

		/****************************************/
        $this->fixture->setUrl('/layout');
		/****************************************/
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testPut()
	{
		$this->fixture->setType('PUT');

		$this->fixture->setUrl('/custom-test/layout/test-put');
		$data= '["amount","account","closeDate","leadSource","stage","probability","assignedUser"]';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		//check if file exists
		$this->assertTrue(file_exists('application/Espo/Resources/layouts/CustomTest/testPut.json'));
	}

	function testPatch()
	{
		$this->fixture->setType('PATCH');

		$this->fixture->setUrl('/custom-test/layout/test-patch');
		$data= '[{"label":"MyLabel"}]';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		//check if file exists
		$this->assertTrue(file_exists('application/Espo/Resources/layouts/CustomTest/testPatch.json'));
	}

	function testGet()
	{
		$this->fixture->setType('GET');

		$this->fixture->setUrl('/custom-test/layout/detail');
		$this->assertTrue($this->fixture->isSuccess( ));

		$this->fixture->setUrl('/need-to-be-not-real/layout/not-real');
		$response= $this->fixture->getResponse();
		$this->assertEquals(404, $response['code']);
	}   


}

?>