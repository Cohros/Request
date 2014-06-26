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
     * $_GET
     * @var array
     */
    protected $get = null;

    /**
     * filter $_GET
     * @return [type] [description]
     */
    protected function _get()
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
            $this->_get();
            $this->paginate = (isset($this->get['page']));
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
            $this->_get();
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
            $this->_get();
            $this->embed = (isset($this->get['embed'])) ? explode(',', $this->get['embed']) : [];

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
            $this->_get();
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
            $this->_get();
            $this->sort = [];

            $sort = [];
            if (!empty($this->get['sort'])) {
                $sort = explode(',', $this->get['sort']);
            }
            
            foreach ($sort as $field) {
                $field = $this->defineSortDirection($field);
                $field['value'] = strtr($field['value'], ':', '.');

                $this->sort[$field['value']] = $field['direction'];
            }
        }

        return $this->sort;
    }

    private function defineSortDirection($value)
    {
        $value = (string) $value;
        $direction = 'ASC';

        if ($value[0] === '-') {
            $value = substr($value, 1);
            $direction = 'DESC';
        }

        return array (
            'value' => $value,
            'direction' => $direction,
        );
    }

    /**
     * Get search query
     * ?q=hello word
     * @return string
     */
    public function search()
    {
        if (is_null($this->search)) {
            $this->_get();
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
        $get = array_diff_key($this->_get(), $exclude);
        $response = [];
        foreach ($get as $field => $rules) {
            $field = strtr($field, ':', '.');
            $response[$field] = $this->filterOrganize($rules);
            if (empty($response[$field])) {
                unset($response[$field]);
            }
        }

        return $response;
    }

    private function filterOrganize($rules)
    {
        $rules = explode(';', $rules);
        $response = array (
            'and' => array(),
        );

        foreach ($rules as $rule) {
            $rule = explode(',', $rule);

            if (count($rule) == 1) {
                if ($rule[0] === 'NULL') {
                    $response['='] = null;
                } elseif ($rule[0] !== '') {
                    $operator = $this->extractFilterOperator($rule[0]);
                    if (!isset($response[$operator['operator']])) {
                        $response[$operator['operator']] = array();
                    }
                    $response[$operator['operator']][] = $operator['value'];
                }
            } else {
                foreach ($rule as $piece) {
                    $operator = $this->extractFilterOperator($piece);
                    if (!isset($response['and'][$operator['operator']])) {
                        $response['and'][$operator['operator']] = array();
                    }
                    $response['and'][$operator['operator']][] = $operator['value'];
                }
            }
        }

        if (empty($response['and'])) {
            unset($response['and']);
        }

        return $response;
    }

    /**
     * set function
     * Method to set an array in GET.
     * @param $type string (with the type)
     * @param $array array (With the values)
     * @param $operator string (with the operator)
     * @return void
     * @author Juarez Turrini <juarez.turrini@gmail.com>
     */
    public function set($type, $array, $operator = 'OR')
    {
        $this->_get();

        // Checking the operator type
        $operator = ($operator == 'OR') ? ';' : (($operator == 'AND') ? ',' : ';' );

        switch($type) {
            case 'replace': // Replace in array
                $this->get = array_replace($this->get, $array);
                break;
            case 'add': // Add on array
                foreach ($array as $key => $value) {
                    // If exists
                    if (array_key_exists($key, $this->get)) {
                        // Old value in original array.
                        $old = $this->get[$key];
                        // Push in original array.
                        $this->get[$key] = $old . $operator . $value;
                    } else {
                        $this->get[$key] = $value;
                    }
                }
                break;
            default:
                // Do nothing...
                break;
        }
    }

    /**
     * Search for operators in $value (>, <, +, etc)
     * @param  string $value
     * @return array  associative array with operator and cleaned value
     */
    private function extractFilterOperator($value)
    {
        $tests = array (
            'testFilterBiggerThan' => '>',
            'testFilterBiggerOrEqualsThan' => '>=',
            'testFilterSmallerThan' => '<',
            'testFilterSmallerOrEqualsThan' => '<=',
            'testFilterNotEqualThan' => 'NOT',
        );

        foreach ($tests as $test => $operator) {
            $result = $this->{$test}($value);
            if (!is_null($result)) {
                return array (
                    'operator' => $operator,
                    'value' => $result,
                );
            }
        }

        return array (
            'operator' => '=',
            'value' => $value
        );
    }

    /**
     * test operator >
     * @param  string $value
     * @return mixed  clean $value if the filter operator was found or null
     */
    private function testFilterBiggerThan($value)
    {
        if ($value[0] == '>') {
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
        if ($lastChar == '+' || $lastChar== '>') {
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
        if ($value[0] == '<') {
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
        if ($lastChar == '-' || $lastChar== '<') {
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
        if ($value[0] == '!') {
            return substr($value, 1);
        }
    }
}
