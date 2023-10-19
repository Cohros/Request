<?php

namespace Sigep\Request;

use InvalidArgumentException;

trait TraitRequest
{
    /**
     * Indicates if request is paginated
     */
    protected ?bool $paginate = null;

    /**
     * The current page
     */
    protected ?int $page = null;

    /**
     * Number of items per page
     */
    protected ?int $offset = null;

    /**
     * The default value when offset is not set
     */
    protected int $defaultOffset = 15;

    /**
     * Identify which associations should be returned
     * @var array<string>
     */
    protected ?array $embed = null;

    /**
     * How results must be ordered
     * @var array<string>
     */
    protected ?array $sort = null;

    /**
     * Search query (textual)
     */
    protected ?string $search = null;

    /**
     * Filters: has fieldName, an operator and values to compare
     * @var array<mixed>|null
     */
    protected ?array $filter = null;

    /**
     * raw $_GET
     * @var array|null
     */
    protected ?array $get = null;

    /**
     * Determines if sort is ASC or DESC
     * @return array<string, string> associative array with value and direction
     */
    private function defineSortDirection(string $value): array
    {
        $direction = 'ASC';

        if ($value[0] === '-') {
            $value = substr($value, 1);
            $direction = 'DESC';
        }

        return compact('value', 'direction');
    }

    /**
     * filter $_GET
     * @returns array<string, string | array<string>>
     * @SuppressWarnings(PHPMD.Superglobals) intentionally using $_GET
     */
    protected function parseQueryString(): array
    {
        if (is_null($this->get)) {
            $this->get = $_GET;
        }

        return $this->get;
    }

    /**
     * return default offset
     */
    public function getDefaultOffset(): int
    {
        return $this->defaultOffset;
    }

    /**
     * set default offset
     * @throws InvalidArgumentException If $offset <= 0
     */
    public function setDefaultOffset(int $offset): void
    {
        if (!$offset) {
            throw new InvalidArgumentException('offset MUST be int and bigger than 0');
        }

        $this->defaultOffset = $offset;
    }

    /**
     * Return all params from querystring
     * @returns array<string, string | number | array>
     */
    public function params(): array
    {
        $data = array();

        $data['paginate']   = $this->paginate();
        $data['page']       = $this->page();
        $data['offset']     = $this->offset();
        $data['filter']     = $this->filter();
        $data['embed']      = $this->embed();
        $data['sort']       = $this->sort();
        $data['search']     = $this->search();

        return $data;
    }

    /**
     * Determine if request wants paginated data
     */
    public function paginate(): bool
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
     */
    public function page(): ?int
    {
        if (is_null($this->page)) {
            $this->parseQueryString();
            $this->page = (isset($this->get['page'])) ? (int) $this->get['page'] : 1;
        }

        return $this->page;
    }

    /**
     * Get name of associated data that should be returned
     * @returns array<string>
     * ?embed=a,b,c
     */
    public function embed(): array
    {
        if (is_null($this->embed)) {
            $this->parseQueryString();
            $embedRaw = (isset($this->get['embed'])) ? $this->get['embed'] : [];

            if (is_string($embedRaw)) {
                $embedRaw = explode(',', $embedRaw);
            }

            $this->embed = array_unique(array_map(function ($element) {
                return strtr($element, ':', '.');
            }, $embedRaw));
        }

        return $this->embed;
    }

    /**
     * Get offset of data to pagination of the request
     * If is not present in request, use the default value
     * ?offset=9
     */
    public function offset(): int
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
     * @returns array<string, array<string>>
     */
    public function sort(): array
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
     */
    public function search(): string
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
     */
    public function filter(): array
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
     */
    private function getFilters(mixed $rules): array
    {
        if (!is_array($rules)) {
            $rules = explode(';', $rules);
        }

        $response = array(
            'and' => array(),
        );

        foreach ($rules as $rule) {
            $rule = explode(',', $rule);
            $pointer = &$response;
            if (count($rule) > 1) {
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
     */
    private function extractFilterConfig(array &$pointer, array $rules): void
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
     * @param $array array associative array with parameter name and value, where value must be a string
     * @param $operator string
     */
    public function set(string $type, array $array, string $operator = 'OR'): void
    {
        $this->parseQueryString();
        $operatorSeparator = ($operator === 'AND') ? ',' : ';';

        if ($type === 'replace') {
            $this->get = array_replace($this->get, $array);
        } elseif ($type === 'add') {
            foreach ($array as $key => $value) {
                if (isset($this->get[$key])) {
                    $separator = $key === "embed" ? "," : $operatorSeparator;
                    $this->get[$key] .= $separator . $value;
                    break;
                }

                $this->get[$key] = $value;
            }
        }

        $this->refresh($this->get);
    }

    /**
     * Search for operators in $value (>, <, +, etc)
     * @return array associative array with operator and cleaned value
     */
    private function extractFilterOperator(string $value): array
    {
        $tests = array(
            'testFilterBiggerThan'          => '>',
            'testFilterBiggerOrEqualsThan'  => '>=',
            'testFilterSmallerThan'         => '<',
            'testFilterSmallerOrEqualsThan' => '<=',
            'testFilterNotEqualThan'        => 'NOT',
        );

        $response = array(
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
     * @return ?string  clean $value if the filter operator was found or null
     */
    private function testFilterBiggerThan(string $value): ?string
    {
        if ($value[0] === '>') {
            return substr($value, 1);
        }

        return null;
    }

    /**
     * test operator >=
     * @param string $value
     * @return string|null clean $value if the filter operator was found or null
     */
    private function testFilterBiggerOrEqualsThan(string $value): ?string
    {
        $lastChar = $value[strlen($value) - 1];
        if ($lastChar === '+' || $lastChar === '>') {
            return substr($value, 0, strlen($value) - 1);
        }

        return null;
    }

    /**
     * test operator <
     * @return string|null clean $value if the filter operator was found or null
     */
    private function testFilterSmallerThan(string $value): ?string
    {
        if ($value[0] === '<') {
            return substr($value, 1);
        }
        return null;
    }

    /**
     * test operator <=
     * @param string $value
     * @return string|null clean $value if the filter operator was found or null
     */
    private function testFilterSmallerOrEqualsThan($value): ?string
    {
        $lastChar = $value[strlen($value) - 1];
        if ($lastChar === '-' || $lastChar === '<') {
            return substr($value, 0, strlen($value) - 1);
        }
        return null;
    }

    /**
     * test operator !
     * @param string $value
     * @return string|null clean value if the operator was found or null
     */
    private function testFilterNotEqualThan(string $value): ?string
    {
        if ($value[0] === '!') {
            return substr($value, 1);
        }

        return null;
    }

    public function refresh(array $newData = null): void
    {
        $this->paginate = null;
        $this->page = null;
        $this->offset = null;
        $this->embed = null;
        $this->sort = null;
        $this->search = null;
        $this->filter = null;
        $this->get = null;
        $this->get = $newData ?: $this->parseQueryString();
    }
}
