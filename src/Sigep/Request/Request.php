<?php

namespace Sigep\Request;

class Request
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
     * The default value when $_GET['offset'] is not set
     * @var bool
     */
    protected $defaultOffset = 15;

    /**
     * Identify wich associations must be returned
     * @var array
     */
    protected $embed = null;

    /**
     * @var array
     */
    protected $except = null;

    /**
     * @var array
     */
    protected $where = null;

    /**
     * @var array
     */
    protected $sort = null;

    /**
     * search query
     * @var string
     */
    protected $search = null;

    /**
     * filters
     * @var array
     */
    protected $filter = null;

    public function getDefaultOffset()
    {
        return $this->defaultOffset;
    }

    public function setDefaultOffset($offset)
    {
        if (!is_int($offset)) {
            throw new \InvalidArgumentException('offset MUST be int');
        }

        $this->defaultOffset = $offset;
    }

    /**
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
     *
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
     * @return mixed offset or null
     */
    public function offset()
    {
        if (is_null($this->offset)) {
            $this->offset = (isset($_GET['offset'])) ? (int) $_GET['offset'] : $this->defaultOffset;
        }

        return $this->offset;
    }

    /**
     *
     * @return
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
     * @return string
     */
    public function search()
    {
        if (is_null($this->search)) {
            $this->search = (isset($_GET['q'])) ? $_GET['q'] : '';
        }

        return $this->search;
    }

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
                '=>',
                '=<',
                'NOT',
            );

            $use = array_diff($get, $exclude);
            $this->filter = [];

            if ($use) {
                foreach ($use as $field) {
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

                    if (!$this->filter[$field]) {
                        unset($this->filter[$field]);
                    }
                }
            }
        }

        return $this->filter;
    }
}
