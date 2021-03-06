<?php
use Slim\Environment;

class Controller_RunTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        Environment::mock(array(
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/'
        ));
        $di = Xhgui_ServiceContainer::instance();
        unset($di['app']);

        $di['app'] = $di->share(function ($c) {
            return $this->getMock(
                'Slim\Slim',
                array('redirect', 'render', 'urlFor'),
                array($c['config'])
            );
        });
        $this->runs = $di['runController'];
        $this->app = $di['app'];
        $this->profiles = $di['profiles'];
        $this->profiles->truncate();
    }

    public function testIndexEmpty()
    {
        $this->runs->index();
        $result = $this->runs->templateVars();

        $this->assertEquals('Recent runs', $result['title']);
        $this->assertFalse($result['has_search'], 'No search being done.');
        $expected = array(
            'total_pages' => 1,
            'page' => 1,
            'sort' => null,
            'direction' => 'desc',
        );
        $this->assertEquals($expected, $result['paging']);
    }

    public function testIndexSortedWallTime()
    {
        Environment::mock(array(
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=wt',
        ));

        $this->runs->index();
        $result = $this->runs->templateVars();
        $this->assertEquals('Longest wall time', $result['title']);
        $this->assertEquals('wt', $result['paging']['sort']);
    }

    public function testIndexSortedCpu()
    {
        Environment::mock(array(
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=cpu&direction=desc',
        ));

        $this->runs->index();
        $result = $this->runs->templateVars();
        $this->assertEquals('Most CPU time', $result['title']);
        $this->assertEquals('cpu', $result['paging']['sort']);
        $this->assertEquals('desc', $result['paging']['direction']);
    }

    public function testIndexWithSearch()
    {
        Environment::mock(array(
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=mu&direction=asc&url=index.php',
        ));

        $this->runs->index();
        $result = $this->runs->templateVars();
        $this->assertEquals('Highest memory use', $result['title']);
        $this->assertEquals('mu', $result['paging']['sort']);
        $this->assertEquals('asc', $result['paging']['direction']);
        $this->assertEquals(array('url' => 'index.php'), $result['search']);
        $this->assertTrue($result['has_search']);
    }

}
