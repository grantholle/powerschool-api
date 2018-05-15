<?php

namespace PowerSchool\Api;

use PowerSchool\Request;

class RequestBuilder {

    /* @var PowerSchool\Request */
    private $request;

    /* @var string */
    private $endpoint;

    /* @var string */
    private $method;

    /* @var Array */
    private $options = [];

    /* @var Array */
    private $data;

    /* @var string */
    private $table;

    /* @var string */
    private $queryString = [];

    /* @var string */
    private $id;

    /**
     * Constructor
     *
     * @param string $serverAddress
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct(string $serverAddress = null, string $clientId = null, string $clientSecret = null)
    {
        $this->request = new Request($serverAddress, $clientId, $clientSecret);
    }

    /**
     * Sets the table for a request against a custom table
     *
     * @param string $table
     * @return $this
     */
    public function setTable(string $table)
    {
        $this->table = $table;
        $this->endpoint = '/ws/schema/table/' . $table;

        return $this;
    }

    /**
     * Alias for setTable()
     *
     * @param string $table
     * @return $this
     */
    public function table(string $table)
    {
        return $this->setTable($table);
    }

    /**
     * Alias for setTable()
     *
     * @param string $table
     * @return $this
     */
    public function forTable(string $table)
    {
        return $this->setTable($table);
    }

    /**
     * Alias for setTable()
     *
     * @param string $table
     * @return $this
     */
    public function againstTable(string $table)
    {
        return $this->setTable($table);
    }

    /**
     * Sets the id of the resource we're interacting with
     *
     * @param Mixed $id
     * @return $this
     */
    public function setId($id)
    {
        $this->endpoint .= '/' . $id;
        $this->id = $id;

        return $this;
    }

    /**
     * Alias for setId()
     *
     * @param Mixed $id
     * @return $this
     */
    public function id($id)
    {
        return $this->setId($id);
    }

    /**
     * Alias for setId()
     *
     * @param Mixed $id
     * @return $this
     */
    public function forId($id)
    {
        return $this->setId($id);
    }

    /**
     * Sets the endpoint to the named query
     *
     * @param string $query
     * @param Array $data
     * @return Mixed
     */
    public function setNamedQuery(string $query, Array $data = [])
    {
        $this->endpoint = '/ws/schema/query/' . $query;

        // If there's data along with it,
        // it's short hand for sending the request
        if (!empty($data)) {
            return $this->withData($data)->post();
        }

        return $this;
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query
     * @param Array $data
     * @return Mixed
     */
    public function namedQuery(string $query, Array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query
     * @param Array $data
     * @return Mixed
     */
    public function powerQuery(string $query, Array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query
     * @param Array $data
     * @return Mixed
     */
    public function pq(string $query, Array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Sets the data for the post/put/patch requests
     *
     * @param Array $data
     * @return $this
     */
    public function withData(Array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets the query string for get requests
     *
     * @param Mixed $queryString
     * @return $this
     */
    public function withQueryString($queryString)
    {
        if (is_array($queryString)) {
            $this->queryString = $queryString;
        } else {
            parse_str($queryString, $this->queryString);
        }

        $this->queryString = $queryString;

        return $this;
    }

    /**
     * Alias of withQueryString()
     *
     * @param Mixed $queryString
     * @return $this
     */
    public function query($queryString)
    {
        return $this->withQueryString($queryString);
    }

    /**
     * Adds a variable to the query string array
     *
     * @param string $key
     * @param string $val
     * @return $this
     */
    public function addQueryVar(string $key, string $val)
    {
        $this->queryString[$key] = $val;

        return $this;
    }

    /**
     * Syntactic sugar for the q query string var
     *
     * @param string $query
     * @return $this
     */
    public function q(string $query)
    {
        return $this->addQueryVar('q', $query);
    }

    /**
     * Syntactic sugar for the projection query string var
     *
     * @param string $projection
     * @return void
     */
    public function projection(string $projection)
    {
        return $this->addQueryVar('projection', $projection);
    }

    /**
     * Syntactic sugar for the pagesize query string var
     *
     * @param integer $pagesize
     * @return $this
     */
    public function pagesize(int $pagesize)
    {
        return $this->addQueryVar('pagesize', $pagesize);
    }

    /**
     * Builds the dumb request structure for PowerSchool table queries
     *
     * @return void
     */
    protected function buildRequestJson()
    {
        if ($this->method === 'get' || $this->method === 'delete') {
            return;
        }

        if (!isset($this->options['json'])) {
            $this->options['json'] = [];
        }

        if ($this->table) {
            $this->options['json']['tables'] = [$this->table => $this->data];
        }

        if ($this->id) {
            $this->options['json']['id'] = $this->id;
            $this->options['json']['name'] = $this->table;
        }

        if ($this->data && !$this->table) {
            $this->options['json'] = $this->data;
        }
    }

    /**
     * Sends the request to PowerSchool
     *
     * @return Array
     */
    public function send()
    {
        $this->buildRequestJson();

        // Build the query by hand
        if ($this->method === 'get' && !is_empty($this->queryString)) {
            $this->options['query'] = '';

            foreach ($this->queryString as $var => $val) {
                $this->options['query'] .= $var . '=' . $val . '&';
            }

            $this->options['query'] = substr($this->options['query'], 0, -1);
        }

        return $this->request->makeRequest($this->method, $this->endpoint, $this->options);
    }

    /**
     * Sets the request method
     *
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Alias for setMethod()
     *
     * @param string $method
     * @return $this
     */
    public function method(string $method)
    {
        return $this->setMethod($method);
    }

    /**
     * Sets method to get, sugar around setMethod(), then sends the request
     *
     * @return Array
     */
    public function get()
    {
        return $this->setMethod('get')->send();
    }

    /**
     * Sets method to post, sugar around setMethod(), then sends the request
     *
     * @return Array
     */
    public function post()
    {
        return $this->setMethod('post')->send();
    }

    /**
     * Sets method to put, sugar around setMethod(), then sends the request
     *
     * @return Array
     */
    public function put()
    {
        return $this->setMethod('put')->send();
    }

    /**
     * Sets method to patch, sugar around setMethod(), then sends the request
     *
     * @return Array
     */
    public function patch()
    {
        return $this->setMethod('patch')->send();
    }

    /**
     * Sets method to delete, sugar around setMethod(), then sends the request
     *
     * @return Array
     */
    public function delete()
    {
        return $this->setMethod('delete')->send();
    }
}
