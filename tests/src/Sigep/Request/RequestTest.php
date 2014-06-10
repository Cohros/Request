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

    public function testSetDefaultOffset()
    {
        $_GET = [];
        $this->object->setDefaultOffset(13);
        $this->assertEquals(13, $this->object->offset());
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

    public function testSimpleFilter()
    {
        $_GET = array (
            'color' => 'red',
        );

        $this->assertEquals(array (
            'color' => array (
                '=' => ['red'],
            )
        ), $this->object->filter());
    }

    public function testMultipleFilter()
    {
        $_GET = array (
            'color' => 'red',
            'size' => '16',
        );

        $this->assertEquals(array (
            'color' => array (
                '=' => ['red'],
            ),
            'size' => array (
                '=' => ['16']
            ),
        ), $this->object->filter());
    }

    public function testFilterOr()
    {
        $_GET = array (
            'color' => 'red;green;blue',
        );

        $this->assertEquals(array (
            'color' => array (
                '=' => array (
                    'red',
                    'green',
                    'blue',
                ),
            ),
        ), $this->object->filter());
    }

    public function testFilterAND()
    {
        $_GET = array (
            'size' => '!16,!18,!19'
        );

        $this->assertEquals(array (
            'size' => array (
                'and' => array (
                    'NOT' => ['16', '18', '19'],
                ),
            ),
        ), $this->object->filter());
    }

    public function testBetweenFilter()
    {
        $_GET = array (
            'size' => '>16,<25',
        );

        $this->assertEquals(array (
            'size' => array (
                'and' => array (
                    '>' => ['16'],
                    '<' => ['25'],
                ),
            ),
        ), $this->object->filter());
    }

    public function testBetweenFilterInclusive()
    {
        $_GET = array (
            'size' => '16>,25<',
        );

        $this->assertEquals(array (
            'size' => array (
                'and' => array (
                    '>=' => ['16'],
                    '<=' => ['25'],
                ),
            ),
        ), $this->object->filter());
    }

    public function testFilterEmpty()
    {
        $_GET = array (
            'size' => '',
        );

        $this->assertEquals(array (
        ), $this->object->filter());
    }

    public function testFilterNull()
    {
        $_GET = array (
            'size' => 'NULL',
        );

        $this->assertEquals(array (
            'size' => array (
                '=' => null,
            )
        ), $this->object->filter());
    }

    public function testExclusions()
    {
        $_GET = array (
            'size' => '>10,<20',
            'q' => 'sherpa',
            'sort' => 'name',
            'page' => 1,
            'offset' => 20,
            'embed' => 40,
        );

        $this->assertEquals(array (
            'size' => array (
                'and' => array (
                    '>' => array(10),
                    '<' => array(20),
                ),
            ),
        ), $this->object->filter());
    }

    public function testReplaceFilter()
    {
        $_GET = array (
            'name' => 'luis'
        );

        $this->object->set('replace', array ('name' => ''));

        $this->assertEquals(array (
        ), $this->object->filter());
    }

    public function testAddFilterAND()
    {
        $_GET = array(
            'color' => 'blue',
        );

        $this->object->set('add', array('color' => 'white'), 'AND');
        $this->assertEquals(array (
            'color' => array (
                'and' => array(
                    '=' => ['blue', 'white']
                )
            )
        ), $this->object->filter());
    }

    public function testAddFilterOR()
    {
        $_GET = array(
            'color' => 'green',
        );

        $this->object->set('add', array('color' => 'purple'));
        $this->assertEquals(array (
            'color' => array (
                '=' => ['green', 'purple']
            )
        ), $this->object->filter());
    }

    public function testAddDiffKey ()
    {
        $_GET = array(
            'color' => 'green',
        );

        $this->object->set('add', array('id' => 15));
        $this->assertEquals(array (
            'color' => array (
                '=' => ['green']
            ),
            'id'    => array(
                '=' => [15]
            )
        ), $this->object->filter());
    }

    public function testSetTypeEmpty ()
    {
        $_GET = array(
            'color' => 'green',
        );

        $this->object->set('', array('id' => 15));
        $this->assertEquals(array (
            'color' => array (
                '=' => ['green']
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
