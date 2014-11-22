<?php

namespace Sigep\Request;

use InvalidArgumentException;

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
     * @var int
     */
    protected $offset = null;

    /**
     * The default value when offset is not set
     * @var int
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
     * raw $_GET
     * @var array
     */
    protected $get = null;
    
    /**
     * Determines if sort is ASC or DESC
     * @param $value string
     * @return array
     */
    private function defineSortDirection($value)
    {
        $value = (string) $value;
        $direction = 'ASC';

        if ($value[0] === '-') {
            $value = substr($value, 1);
            $direction = 'DESC';
        }

        return array(
            'value'     => $value,
            'direction' => $direction,
        );
    }

    /**
     * filter $_GET
     * @return array
     */
    protected function parseQueryString()
    {
        if (is_null($this->get)) {
            $this->get = $_GET;
        }

        return $this->get;
    }

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
            throw new InvalidArgumentException('offset MUST be int and bigger than 0');
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
            $this->parseQueryString();
            $this->paginate = (isset($this->get['page']));
        }

        return $this->paginate;
    }

    /**
     * Get requested page number
     * ?page=X
     * @return mixed page number or null
     */
    public function page()
    {
        if (is_null($this->page)) {
            $this->parseQueryString();
            $this->page = (isset($this->get['page'])) ? (int) $this->get['page'] : 1;
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
            $this->parseQueryString();
            $this->embed = (isset($this->get['embed'])) ? $this->get['embed'] : [];

            if (is_string($this->embed)) {
                $this->embed = explode(',', $this->embed);
            }

            $this->embed = array_map(function ($element) {
                return strtr($element, ':', '.');
            }, $this->embed);
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
            $this->parseQueryString();
            $this->offset = (isset($this->get['offset'])) ? (int) $this->get['offset'] : $this->defaultOffset;
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
            $this->parseQueryString();
            $this->sort = [];

            $sort = [];
            if (!empty($this->get['sort'])) {
                $sort = $this->get['sort'];
            }

            if (is_string($sort)) {
                $sort = explode(',', $sort);
            }

            foreach ($sort as $field) {
                $field = $this->defineSortDirection($field);
                $field['value'] = strtr($field['value'], ':', '.');

                $this->sort[$field['value']] = $field['direction'];
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
            $this->parseQueryString();
            $this->search = (isset($this->get['q'])) ? $this->get['q'] : '';
        }

        return $this->search;
    }

    /**
     * Get filter rules
     * ?field=value
     * The default operator is '=' and it can be changed preceding the value
     * with a special character. Supported are '-', '+', and '!'.
     * @return array
     */
    public function filter()
    {
        $exclude = array_flip(['page', 'offset', 'sort', 'q', 'embed']);
        $get = array_diff_key($this->parseQueryString(), $exclude);
        $response = [];

        foreach ($get as $field => $rules) {
            $field = strtr($field, ':', '.');
            $response[$field] = $this->getFilters($rules);
            if (empty($response[$field])) {
                unset($response[$field]);
            }
        }

        return $response;
    }

    /**
     * Get filters config
     * @param $rules mixed
     * @return array
     */
    private function getFilters($rules)
    {
        if (!is_array($rules)) {
            $rules = explode(';', $rules);
        }

        $response = array(
            'and' => array(),
        );

        foreach ($rules as $rule) {
            $rule = explode(',', $rule);
            
            if (count($rule) === 1) {
                $pointer = &$response;
            } else {
                $pointer = &$response['and'];
            }
            
            $this->extractFilterConfig($pointer, $rule);
            unset($pointer);
        }

        if (empty($response['and'])) {
            unset($response['and']);
        }

        return $response;
    }
    
    /**
     * Extract configs of filter
     * @param array $pointer
     * @param array $rules
     */
    private function extractFilterConfig(array &$pointer, array $rules)
    {    
        foreach ($rules as $rule) {
            if ($rule === '') {
                continue;
            }

            if ($rule === 'NULL') {
                $pointer['='] = null;
                continue;
            }

            $operator = $this->extractFilterOperator($rule);
            if (!isset($pointer[$operator['operator']])) {
                $pointer[$operator['operator']] = array();
            }
            $pointer[$operator['operator']][] = $operator['value'];
        }
    }

    /**
     * Add or replace request parameters
     * @param $type string type of operation. Can be add or replace
     * @param $array array associative array with parameter name and value
     * @param $operator string
     */
    public function set($type, $array, $operator = 'OR')
    {
        $this->parseQueryString();
        $operatorSeparator = ($operator === 'AND') ? ',' : ';';

        if ($type === 'replace') {
            $this->get = array_replace($this->get, $array);
        } elseif ($type === 'add') {
            foreach ($array as $key => $value) {
                if (isset($this->get[$key])) {
                    $this->get[$key] .= $operatorSeparator . $value;
                } else {
                    $this->get[$key] = $value;
                }
            }
        }
    }

    /**
     * Search for operators in $value (>, <, +, etc)
     * @param $value string
     * @return array associative array with operator and cleaned value
     */
    private function extractFilterOperator($value)
    {
        $tests = array(
            'testFilterBiggerThan'          => '>',
            'testFilterBiggerOrEqualsThan'  => '>=',
            'testFilterSmallerThan'         => '<',
            'testFilterSmallerOrEqualsThan' => '<=',
            'testFilterNotEqualThan'        => 'NOT',
        );
        
        $response = array (
            'operator' => '=',
            'value' => $value,
        );

        foreach ($tests as $test => $operator) {
            $result = $this->{$test}($value);
            if (!is_null($result)) {
                $response['operator'] = $operator;
                $response['value'] = $result;
                break;
            }
        }

        return $response;
    }

    /**
     * test operator >
     * @param  string $value
     * @return mixed  clean $value if the filter operator was found or null
     */
    private function testFilterBiggerThan($value)
    {
        if ($value[0] === '>') {
            return substr($value, 1);
        }
    }

    /**
     * test operator >=
     * @param  string $value
     * @return mixed  clean $value if the filter operator was found or null
     */
    private function testFilterBiggerOrEqualsThan($value)
    {
        $lastChar = $value[strlen($value) - 1];
        if ($lastChar === '+' || $lastChar === '>') {
            return substr($value, 0, strlen($value) - 1);
        }
    }

    /**
     * test operator <
     * @param  string $value
     * @return mixed  clean $value if the filter operator was found or null
     */
    private function testFilterSmallerThan($value)
    {
        if ($value[0] === '<') {
            return substr($value, 1);
        }
    }

    /**
     * test operator <=
     * @param  string $value
     * @return mixed  clean $value if the filter operator was found or null
     */
    private function testFilterSmallerOrEqualsThan($value)
    {
        $lastChar = $value[strlen($value) - 1];
        if ($lastChar === '-' || $lastChar === '<') {
            return substr($value, 0, strlen($value) - 1);
        }
    }

    /**
     * test operator !
     * @param  string $value
     * @return mixed  clean value if the operator was found or null
     */
    private function testFilterNotEqualThan($value)
    {
        if ($value[0] === '!') {
            return substr($value, 1);
        }
    }

    public function refresh($newData = null)
    {
        $this->paginate = null;
        $this->page = null;
        $this->offset = null;
        $this->embed = null;
        $this->sort = null;
        $this->search = null;
        $this->filter = null;
        $this->get = $newData;
    }
}
