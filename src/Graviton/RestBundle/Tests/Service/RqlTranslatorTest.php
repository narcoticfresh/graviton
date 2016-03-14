<?php
/**
 * translator tests
 */

namespace Graviton\RestBundle\Service;

use Graviton\Rql\Node\SearchNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\Query;

/**
 * Class RqlTranslatorTest
 * @package Graviton\RestBundle\Service
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class RqlTranslatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  RqlTranslator */
    protected $sut;

    /**
     * PHPUnit set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->sut = new RqlTranslator();
    }

    /**
     * Test for correct node translation with search terms
     *
     * @return void
     */
    public function testSearchNodeTranslation()
    {
        $searchNode = new SearchNode(array('searchTerm1', 'searchTerm2'));

        $resultingOrNode = $this->sut->translateSearchNode($searchNode, array('testField'));

        $this->assertTrue($resultingOrNode instanceof OrNode);
        $this->assertEquals(2, sizeof($resultingOrNode->getQueries()));
    }

    /**
     * test for correct not translation without searches
     *
     * @return void
     */
    public function testEmptySearch()
    {
        $searchNode = new SearchNode();

        $resultingOrNode = $this->sut->translateSearchNode($searchNode, array('testField'));

        $this->assertTrue($resultingOrNode instanceof SearchNode);
        $this->assertEquals(0, sizeof($resultingOrNode->getSearchTerms()));
    }

    /**
     * Test correct translation with already existing queries
     *
     * @return void
     */
    public function testQueryTranslation()
    {
        // Construct scenario:
        $query = new Query();
        $andQuery = new AndNode();
        $andQuery->addQuery(new LikeNode("firstName", "TestFirstName"));
        $andQuery->addQuery(new LikeNode("lastName", "TestLastName"));
        $andQuery->addQuery(new SearchNode(array("searchTerm1", "searchTerm2")));
        $query->setQuery($andQuery);

        $searchFields = array('field1', 'field2');

        /** @var Query $resultQuery */
        $resultQuery = $this->sut->translateSearchQuery($query, $searchFields);

        /** @var AndNode $resultInnerQuery */
        $resultInnerQuery = $resultQuery->getQuery();

        $this->assertTrue($resultInnerQuery instanceof AndNode);
        $this->assertEquals(3, sizeof($resultInnerQuery->getQueries()));

        $subNodes = $resultInnerQuery->getQueries();

        $this->assertTrue($subNodes[0] instanceof LikeNode);
        $this->assertTrue($subNodes[1] instanceof LikeNode);
        $this->assertTrue($subNodes[2] instanceof OrNode);

    }
}
