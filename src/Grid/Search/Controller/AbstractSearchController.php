<?php

namespace Grid\Search\Controller;

use Grid\Search\Query;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * AbstractSearchController
 *
 * @author David Pozsar <david.pozsar@megaweb.hu>
 */
abstract class AbstractSearchController extends AbstractActionController
{

    /**
     * Get request param
     *
     * @param   string  $name
     * @param   mixed   $default
     * @return  mixed
     */
    protected function param( $name, $default = null )
    {
        $params = $this->params();
        return $params->fromPost( $name, $params->fromQuery( $name, $default ) );
    }

    /**
     * Get results for atom & rss actions
     */
    protected function getResults()
    {
        $locale = (string) $this->locale();
        $query  = $this->param( 'query', '' );
        $type   = $this->param( 'type', '%' ) ?: '%';
        $all    = (bool) $this->param( 'all', false );
        $page   = (int) abs( $this->param( 'page', 1 ) ) ?: 1;
        $items  = min( 25, (int) abs( $this->param( 'items', 10 ) ) ?: 10 );
        $cindex = ( $page - 1 ) * $items + 1;
        $index  = (int) abs( $this->param( 'index', $cindex ) ) ?: $cindex;
        $parsed = Query\Parser::parse( $query );
        $model  = $this->getServiceLocator()
                       ->get( 'Grid\Search\Model\Result\Model' );

        $where = array(
            'locale' => (string) $locale,
            'query'  => (string) $parsed->toQueryString(),
            'type'   => (string) $type,
            'all'    => (bool)   $all
        );

        return array(
            'all'       => $all,
            'type'      => $type,
            'page'      => $page,
            'items'     => $items,
            'index'     => $index,
            'query'     => $parsed->toRepresentation(),
            'count'     => $count = $model->searchCount( $where ),
            'results'   => $count < 1 ? array()
                        : $model->searchResults( $where, $items, $index - 1 ),
        );
    }

    /**
     * Get suggestions' data
     *
     * @return  array
     */
    protected function getSuggestions()
    {
        $locale = (string) $this->locale();
        $query  = $this->param( 'query', $this->param( 'term', '' ) );
        $type   = $this->param( 'type', '%' ) ?: '%';
        $all    = (bool) $this->param( 'all', false );
        $limit  = (int)  $this->param( 'limit', 10 ) ?: 10;
        $srvLoc = $this->getServiceLocator();
        $model  = $srvLoc->get( 'Grid\Search\Model\Result\Model' );

        $suggestions = $model->searchSuggestions( array(
            'locale'    => $locale,
            'query'     => $query,
            'type'      => $type,
            'all'       => $all,
        ), $limit );

        if ( $suggestions instanceof \Traversable )
        {
            $suggestions = iterator_to_array( $suggestions );
        }

        return array(
            $suggestions,
            $query,
            $type,
            $all
        );
    }

}
