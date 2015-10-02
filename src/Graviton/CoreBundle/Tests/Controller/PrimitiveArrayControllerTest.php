<?php
/**
 * PrimitiveArrayControllerTest class file
 */

namespace Graviton\CoreBundle\Tests\Controller;

use Graviton\TestBundle\Test\RestTestCase;
use Symfony\Component\HttpFoundation\Response;
use GravitonDyn\TestCasePrimitiveArrayBundle\DataFixtures\MongoDB\LoadTestCasePrimitiveArrayData;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class PrimitiveArrayControllerTest extends RestTestCase
{
    const DATE_FORMAT = 'Y-m-d\\TH:i:sO';
    const DATE_TIMEZONE = 'UTC';

    /**
     * load fixtures
     *
     * @return void
     */
    public function setUp()
    {
        if (!class_exists(LoadTestCasePrimitiveArrayData::class)) {
            $this->markTestSkipped('TestCasePrimitiveArray definition is not loaded');
        }

        $this->loadFixtures(
            [LoadTestCasePrimitiveArrayData::class],
            null,
            'doctrine_mongodb'
        );
    }

    /**
     * Check fixture data
     *
     * @param object $data Fixture data
     * @return void
     */
    private function checkFixtureData($data)
    {
        foreach ([$data, $data->hash, $data->arrayhash[0]] as $data) {
            $this->assertInternalType('array', $data->intarray);
            foreach ($data->intarray as $value) {
                $this->assertInternalType('integer', $value);
            }

            $this->assertInternalType('array', $data->strarray);
            foreach ($data->strarray as $value) {
                $this->assertInternalType('string', $value);
            }

            $this->assertInternalType('array', $data->boolarray);
            foreach ($data->boolarray as $value) {
                $this->assertInternalType('boolean', $value);
            }

            $this->assertInternalType('array', $data->datearray);
            foreach ($data->datearray as $value) {
                $this->assertInternalType('string', $value);
                $this->assertInstanceOf(\DateTime::class, \DateTime::createFromFormat(self::DATE_FORMAT, $value));
            }

            $this->assertInternalType('array', $data->hasharray);
            foreach ($data->hasharray as $value) {
                $this->assertInternalType('object', $value);
            }
        }
    }

    /**
     * Test GET one method
     *
     * @return void
     */
    public function testCheckGetOne()
    {
        $client = static::createRestClient();
        $client->request('GET', '/testcase/primitivearray/testdata');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertNotEmpty($client->getResults());

        $this->checkFixtureData($client->getResults());
    }

    /**
     * Test GET all method
     *
     * @return void
     */
    public function testCheckGetAll()
    {
        $client = static::createRestClient();
        $client->request('GET', '/testcase/primitivearray/');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $client->getResults());

        $this->checkFixtureData($client->getResults()[0]);
    }

    /**
     * Test POST method
     *
     * @return void
     * @group abc
     */
    public function testPostMethod()
    {
        $data = (object) [
            'intarray'  => [10, 20],
            'strarray'  => ['a', 'b'],
            'boolarray' => [true, false],
            'hasharray' => [(object) ['x' => 'y'], (object) []],
            'datearray' => ['2015-09-30T23:59:59+0000', '2015-10-01T00:00:01+0300'],

            'hash'      => (object) [
                'intarray'  => [10, 20],
                'strarray'  => ['a', 'b'],
                'boolarray' => [true, false],
                'hasharray' => [(object) ['x' => 'y'], (object) []],
                'datearray' => ['2015-09-30T23:59:59+0000', '2015-10-01T00:00:01+0300'],
            ],

            'arrayhash' => [
                (object) [
                    'intarray'  => [10, 20],
                    'strarray'  => ['a', 'b'],
                    'boolarray' => [true, false],
                    'hasharray' => [(object) ['x' => 'y'], (object) []],
                    'datearray' => ['2015-09-30T23:59:59+0000', '2015-10-01T00:00:01+0300'],
                ]
            ],
        ];

        $client = static::createRestClient();
        $client->post('/testcase/primitivearray/', $data);
        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResults());

        $location = $client->getResponse()->headers->get('Location');

        $client = static::createRestClient();
        $client->request('GET', $location);
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $result = $client->getResults();
        $this->assertNotNull($result->id);
        unset($result->id);
        $this->assertEquals($this->fixDateTimezone($data), $result);
    }

    /**
     * Test PUT method
     *
     * @return void
     */
    public function testPutMethod()
    {
        $data = (object) [
            'id'        => 'testdata',

            'intarray'  => [10, 20],
            'strarray'  => ['a', 'b'],
            'boolarray' => [true, false],
            'hasharray' => [(object) ['x' => 'y'], (object) []],
            'datearray' => ['2015-09-30T23:59:59+0000', '2015-10-01T00:00:01+0300'],

            'hash'      => (object) [
                'intarray'  => [10, 20],
                'strarray'  => ['a', 'b'],
                'boolarray' => [true, false],
                'hasharray' => [(object) ['x' => 'y'], (object) []],
                'datearray' => ['2015-09-30T23:59:59+0000', '2015-10-01T00:00:01+0300'],
            ],

            'arrayhash' => [
                (object) [
                    'intarray'  => [10, 20],
                    'strarray'  => ['a', 'b'],
                    'boolarray' => [true, false],
                    'hasharray' => [(object) ['x' => 'y'], (object) []],
                    'datearray' => ['2015-09-30T23:59:59+0000', '2015-10-01T00:00:01+0300'],
                ]
            ],
        ];

        $client = static::createRestClient();
        $client->put('/testcase/primitivearray/testdata', $data);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResults());

        $client = static::createRestClient();
        $client->request('GET', '/testcase/primitivearray/testdata');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals($this->fixDateTimezone($data), $client->getResults());
    }

    /**
     * Fix date timezone
     *
     * @param object $data Request data
     * @return object
     */
    private function fixDateTimezone($data)
    {
        foreach ([
                     &$data->datearray,
                     &$data->hash->datearray,
                     &$data->arrayhash[0]->datearray,
                 ] as &$datearray) {
            foreach ($datearray as $i => $date) {
                $datearray[$i] = \DateTime::createFromFormat(self::DATE_FORMAT, $date)
                    ->setTimezone(new \DateTimeZone(self::DATE_TIMEZONE))
                    ->format(self::DATE_FORMAT);
            }
        }
        return $data;
    }

    /**
     * Test validation
     *
     * @return void
     */
    public function testValidation()
    {
        $data = (object) [
            'id'        => 'testdata',

            'intarray'  => [1, 'a'],
            'strarray'  => ['a', false],
            'boolarray' => [true, 'a'],
            'hasharray' => [(object) ['x' => 'y'], 1.5],

            'hash'      => (object) [
                'intarray'  => [1, 'a'],
                'strarray'  => ['a', false],
                'boolarray' => [true, 'a'],
                'hasharray' => [(object) ['x' => 'y'], 1.5],
            ],

            'arrayhash' => [
                (object) [
                    'intarray'  => [1, 'a'],
                    'strarray'  => ['a', false],
                    'boolarray' => [true, 'a'],
                    'hasharray' => [(object) ['x' => 'y'], 1.5],
                ]
            ],
        ];

        $client = static::createRestClient();
        $client->put('/testcase/primitivearray/testdata', $data);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertNotNull($client->getResults());

        // boolean and string values are always converted to correct type by symfony form.
        // we never get "This value is not valid" for such types
        $this->assertEquals(
            [
                (object) [
                    'propertyPath' => 'children[intarray].children[1]',
                    'message'      => 'This value is not valid.'
                ],
                (object) [
                    'propertyPath' => 'data.hasharray[1]',
                    'message'      => 'This value should be of type object.',
                ],

                (object) [
                    'propertyPath' => 'children[hash].children[intarray].children[1]',
                    'message'      => 'This value is not valid.',
                ],
                (object) [
                    'propertyPath' => 'data.hash.hasharray[1]',
                    'message'      => 'This value should be of type object.',
                ],

                (object) [
                    'propertyPath' => 'children[arrayhash].children[0].children[intarray].children[1]',
                    'message'      => 'This value is not valid.',
                ],
                (object) [
                    'propertyPath' => 'data.arrayhash[0].hasharray[1]',
                    'message'      => 'This value should be of type object.',
                ],
            ],
            $client->getResults()
        );
    }
}
