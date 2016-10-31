<?php
namespace Sigep\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $object = null;

    public function setUp()
    {
        $_GET = [];
        $this->object = new Request;
    }

    public function testCleanRequest()
    {
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
    
    public function testEmbedAsArray()
    {
        $_GET['embed'] = ['author'];
        $this->assertEquals($_GET['embed'], $this->object->embed());
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
    
    public function testReplaceEmbedString()
    {
        $_GET = array (
            'embed' => 'user',
        );
        
        $this->object->set('replace', array (
            'embed' => 'city,state',
        ));
        
        $this->assertEquals(
            array('city', 'state'),
            $this->object->embed()
        );
    }
    
    public function testReplaceEmbedStringUsingArray()
    {
        $_GET = array (
            'embed' => 'user',
        );
        
        $this->object->set('replace', array (
            'embed' => array('city', 'state')
        ));
        
        $this->assertEquals(
            array('city', 'state'),
            $this->object->embed()
        );
    }
    
    public function testReplaceEmbedStringMultiple()
    {
        $_GET = array (
            'embed' => 'user,mother',
        );
        
        $this->object->set('replace', array (
            'embed' => 'city,state',
        ));
        
        $this->assertEquals(
            array('city', 'state'),
            $this->object->embed()
        );
    }
    
    public function testReplaceEmbedStringMultipleUsingArray()
    {
        $_GET = array (
            'embed' => 'user,mother',
        );
        
        $this->object->set('replace', array (
            'embed' => array('city', 'state')
        ));
        
        $this->assertEquals(
            array('city', 'state'),
            $this->object->embed()
        );
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

    public function testShouldShouldConvertColonToDot()
    {
        $_GET = array (
            'embed' => 'city,city:state',
            'city:city_geoname_id' => '333',
            'sort' => 'name,-city:state',
        );

        $this->assertEquals(array (
            'city', 'city.state',
        ), $this->object->embed());

        $this->assertEquals(array (
            'city.city_geoname_id' => ['=' => ['333']]
        ), $this->object->filter());
        $this->assertEquals(array(
            'name' => 'ASC',
            'city.state' => 'DESC',
        ), $this->object->sort());
    }

    public function testEverythingShouldWorksWhenQuerystringEnvolvsArrays()
    {
        $_GET = array (
            'embed' => ['modela', 'modelb'],
            'sort' => ['columna', '-columnb'],
            'color' => ['red', 'green', 'blue'],
        );

        $this->assertEquals(['modela', 'modelb'], $this->object->embed());
        $this->assertEquals(['columna' => 'ASC', 'columnb' => 'DESC'], $this->object->sort());
        $this->assertEquals(['color' => array (
            '=' => array (
                'red',
                'green',
                'blue',
            ),
        )], $this->object->filter());
    }

    public function testShouldGetOriginalGETData()
    {
        $_GET = ['city' => 'city a', 'page' => 1];
        $this->object->set('add', ['city' => 'city b']);
        $this->assertEquals(['city' => ['=' => ['city a', 'city b']]], $this->object->filter());
        $this->assertTrue($this->object->paginate());

        $this->object->refresh();
        $this->assertEquals(['city' => ['=' => ['city a']]], $this->object->filter());

        $this->object->refresh(['city' => 'city a;city b']);
        $this->assertEquals(['city' => ['=' => ['city a', 'city b']]], $this->object->filter());
        $this->assertFalse($this->object->paginate());
    }

    public function testShoudGetParams() {
        $_GET = ['city' => 'city A'];
        $this->assertEquals([
            'paginate' => false,
            'page' => 1,
            'offset' => $this->object->getDefaultOffset(),
            'filter' => [
                'city' => ['=' => ['city A']]
            ],
            'embed' => [],
            'sort' => [],
            'search' => ''
        ], $this->object->params());

        $this->object->set('add', ['city' => 'city B']);
        $this->assertEquals([
            'paginate' => false,
            'page' => 1,
            'offset' => $this->object->getDefaultOffset(),
            'filter' => [
                'city' => ['=' => ['city A', 'city B']]
            ],
            'embed' => [],
            'sort' => [],
            'search' => ''
        ], $this->object->params());

        // passing true should discart changes made with 'set'
        $this->assertEquals([
            'paginate' => false,
            'page' => 1,
            'offset' => $this->object->getDefaultOffset(),
            'filter' => [
                'city' => ['=' => ['city A']]
            ],
            'embed' => [],
            'sort' => [],
            'search' => ''
        ], $this->object->params(true));
    }
}
