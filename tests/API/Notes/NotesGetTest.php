<?php
/**
 * Created by solohin.i@gmail.com.
 * http://data5.pro
 * https://www.upwork.com/freelancers/~0110e79b44736be7ab
 * Date: 07/11/16
 * Time: 18:29
 */

namespace Solohin\ToptalExam\Tests\API\Notes;

use Silex\WebTestCase;
use Solohin\ToptalExam\Services\UsersService;
use Symfony\Component\Debug\Debug;

class NotesGetTest extends NotesTestTemplate
{
    public function testGetAllAdmin()
    {
        $this->assertEquals(3100, $this->caloriesSumm500Notes());
    }

    public function testDailyNormal()
    {
        $notes = $this->get500Notes();
        $result = array_column($notes, 'daily_normal', 'id');

        $this->assertEquals(true, $result[1]);
        $this->assertEquals(false, $result[2]);
        $this->assertEquals(true, $result[3]);
        $this->assertEquals(true, $result[4]);
        $this->assertEquals(false, $result[5]);
        $this->assertEquals(false, $result[6]);
    }

    public function testGetAllUser()
    {
        $this->assertEquals(2900, $this->caloriesSumm500Notes('user'));
    }

    public function testGetAllManager()
    {
        $responseData = $this->makeJsonRequest('/v1/notes', 'GET', 'manager', [], false);
        $this->assertEquals('permission_denied', $responseData['error_type'], print_r($responseData, 1));
    }

    public function testGetAllPaging()
    {
        $startCount = count($this->makeJsonRequest('/v1/notes', 'GET', 'admin', [], true)['notes']);
        $toAddCount = 500;
        for ($i = 0; $i < $toAddCount; $i++) {
            $this->notesService->insert([
                'text' => 'Some note',
                'calories' => 100,
                'user_id' => 1,
                'time' => '12:00',
                'date' => '01.01.2017',
            ]);
        }

        $firstPageCount = count($this->makeJsonRequest('/v1/notes', 'GET', 'admin', [], true)['notes']);
        $secondPageCount = count($this->makeJsonRequest('/v1/notes', 'GET', 'admin', ['page' => 2], true)['notes']);
        $this->assertEquals($startCount + $toAddCount, $firstPageCount + $secondPageCount);
    }

    public function testGetAllAdminDateFilter()
    {
        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'admin', ['to_date' => '02.01.2017'], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(1900, $calories);

        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'admin', ['from_date' => '02.01.2017'], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(1800, $calories);

        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'admin', [
            'to_date' => '02.01.2017',
            'from_date' => '02.01.2017',
        ], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(600, $calories);
    }

    public function testGetAllAdminTimeFilter()
    {
        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'admin', ['from_time' => '17:40'], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(1200, $calories);

        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'admin', ['to_time' => '17:40'], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(2200, $calories);

        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'admin', [
            'to_time' => '19:00',
            'from_time' => '14:00',
        ], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(900, $calories);
    }

    public function testGetAllAdminUserFilter()
    {
        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'admin', ['user_id' => '1'], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(2900, $calories);
    }

    public function testGetAllUsernotOwnerFilter()
    {
        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'user', ['user_id' => '4'], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(2900, $calories);//user_id == 1
    }

    public function testGetAllUserDateTimeFilter()
    {
        $notes = $this->makeJsonRequest('/v1/notes', 'GET', 'user', [
            'to_time' => '23:59',
            'from_time' => '14:00',
            'to_date' => '03.01.2017',
            'from_date' => '02.01.2017',
        ], true)['notes'];
        $calories = array_sum(array_column($notes, 'calories'));
        $this->assertEquals(400, $calories);
    }

    public function testWrongDate()
    {
        $this->makeJsonRequest('/v1/notes', 'GET', 'user', [
            'from_date' => '02-01-2017',
        ], false);
    }

    public function testWrongTime()
    {
        $this->makeJsonRequest('/v1/notes', 'GET', 'user', [
            'from_time' => '02-17',
        ], false);
    }
}