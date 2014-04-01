<?php
namespace Sigep\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    protected $object = null;

    public function setUp()
    {
        $this->object = new Request;
    }

    public function testCleanRequest()
    {
        $_GET = [];
        $this->assertEquals(false, $this->object->paginate());
        $this->assertEquals(1, $this->object->page());
        $this->assertEquals($this->object->getDefaultOffset(), $this->object->offset());
        $this->assertEquals([], $this->object->embed());
        $this->assertEquals([], $this->object->sort());
        $this->assertEquals('', $this->object->search());
        $this->assertEquals([], $this->object->filter());
    }

    public function testPaginateWhenPageIsSet()
    {
        $_GET['page'] = 7;
        $this->assertEquals(true, $this->object->paginate());
        $this->assertEquals(7, $this->object->page());
    }

    public function testOffset()
    {
        $_GET['offset'] = 100;
        $this->assertEquals($_GET['offset'], $this->object->offset());
    }

    public function testUniqueEmbed()
    {
        $_GET['embed'] = 'author';
        $this->assertEquals(array('author'), $this->object->embed());
    }

    public function testMultipleEmbed()
    {
        $_GET['embed'] = 'author,comment';
        $this->assertEquals(array('author', 'comment'), $this->object->embed());
    }

    public function testUniqueSortAsc()
    {
        $_GET['sort'] = 'name';
        $this->assertEquals(['name' => 'ASC'], $this->object->sort());
    }

    public function testUniqueSortDesc()
    {
        $_GET['sort'] = '-name';
        $this->assertEquals(['name' => 'DESC'], $this->object->sort());
    }

    public function testMultipleSort()
    {
        $_GET['sort'] = '-name,color';
        $this->assertEquals(['name' => 'DESC', 'color' => 'ASC'], $this->object->sort());
    }

    public function testSearch()
    {
        $_GET['q'] = 'Asdrubal';
        $this->assertEquals('Asdrubal', $this->object->search());
    }

    public function testFilters()
    {
        $_GET = array (
            'genre' => 'rock',
            'year' => '2000,2001,2002',
            'tracks' => '>6,<10,!8',
        );
        $this->assertEquals(array(
            'genre' => array(
                array('=', 'rock')
            ),
            'year' => array(
                array('=', '2000'),
                array('=', '2001'),
                array('=', '2002'),
            ),
            'tracks' => array (
                array ('>', '6'),
                array ('<', '10'),
                array ('NOT', '8'),
            )
        ), $this->object->filter());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionSettingDefaultOffsetWithZero()
    {
        $this->object->setDefaultOffset(0);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionSettingDefaultOffsetWithLetter()
    {
        $this->object->setDefaultOffset('a');
    }
}
