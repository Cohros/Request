<?php

namespace Sigep\Request;

trait TraitRequest
{
    /**
     * Indicates if request is paginated
     * @var bool
     */
    protected $paginate = null;

    /**
     * The current page
     * @var int
     */
    protected $page = null;

    /**
     * Number of items per page
     * a default value
     * @var int
     */
    protected $offset = null;

    /**
     * The default value when offset is not set
     * @var bool
     */
    protected $defaultOffset = 15;

    /**
     * Identify which associations should be returned
     * @var array
     */
    protected $embed = null;

    /**
     * How results must be ordered
     * @var array
     */
    protected $sort = null;

    /**
     * Search query (textual)
     * @var string
     */
    protected $search = null;

    /**
     * Filters: has fieldName, an operator and values to compare
     * @var array
     */
    protected $filter = null;

    /**
     * return default offset
     * @return int
     */
    public function getDefaultOffset()
    {
        return $this->defaultOffset;
    }

    /**
     * set default offset
     * @param int $offset
     * @throws InvalidArgumentException If $offset is not int or != 0
     */
    public function setDefaultOffset($offset)
    {
        if (!is_int($offset) || !$offset) {
            throw new \InvalidArgumentException('offset MUST be int');
        }

        $this->defaultOffset = $offset;
    }

    /**
     * Determine if request wants paginated data
     * @return bool
     */
    public function paginate()
    {
        if (is_null($this->paginate)) {
            $this->paginate = (isset($_GET['page']));
        }

        return $this->paginate;
    }

    /**
     * Get page requested
     * ?page=X
     * @return mixed page number or null
     */
    public function page()
    {
        if (is_null($this->page)) {
            $this->page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;
        }

        return $this->page;
    }

    /**
     * Get name of associated data that should be returned
     * ?embed=a,b,c
     * @return array
     */
    public function embed()
    {
        if (is_null($this->embed)) {
            $this->embed = (isset($_GET['embed'])) ? explode(',', $_GET['embed']) : [];
        }

        return $this->embed;
    }

    /**
     * Get offset of data to pagination of the request
     * If is not present in request, use the default value
     * ?offset=9
     * @return int
     */
    public function offset()
    {
        if (is_null($this->offset)) {
            $this->offset = (isset($_GET['offset'])) ? (int) $_GET['offset'] : $this->defaultOffset;
        }

        return $this->offset;
    }

    /**
     * Get rules to ordenate results
     * ?sort=field,field2
     * If the field name is preceded by a '-', the sort will be descending, otherwise ascending
     * @return array
     */
    public function sort()
    {
        if (is_null($this->sort)) {
            $this->sort = [];

            $sort = (isset($_GET['sort'])) ? explode(',', $_GET['sort']) : [];
            if ($sort) {
                foreach ($sort as $field) {
                    $field = $field;
                    $direction = 'ASC';

                    if ($field[0] === '-') {
                        $field = substr($field, 1);
                        $direction = 'DESC';
                    }

                    $this->sort[$field] = $direction;
                }
            }
        }

        return $this->sort;
    }

    /**
     * Get search query
     * ?q=hello word
     * @return string
     */
    public function search()
    {
        if (is_null($this->search)) {
            $this->search = (isset($_GET['q'])) ? $_GET['q'] : '';
        }

        return $this->search;
    }

    /**
     * Get filter rules
     * ?field=value
     * The default operator is '=' and it can be changed preceding the value
     * with a special character. Supported are '>', '<', and '!'.
     * @return array
     */
    public function filter()
    {
        if (is_null($this->filter)) {
            $get = array_keys($_GET);
            $exclude = array (
                'page',
                'offset',
                'sort',
                'q',
                'embed',
            );

            $howCompare = array (
                '=',
                '>',
                '<',
                'NOT',
            );

            $use = array_diff($get, $exclude);
            $this->filter = [];

            foreach ($use as $field) {
                if (empty($_GET[$field])) {
                    continue;
                }
                $value = explode(',', $_GET[$field]);

                $this->filter[$field] = [];

                foreach ($value as $_value) {
                    $compOperator = $_value[0];
                    if ($compOperator === '!') {
                        $compOperator = 'NOT';
                    }

                    if (!in_array($compOperator, $howCompare)) {
                        $compOperator = '=';
                    } else {
                        $_value = substr($_value, 1);
                    }

                    $this->filter[$field][] = array($compOperator, $_value);
                }
            }
        }

        return $this->filter;
    }
}
