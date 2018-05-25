<?php

namespace GrantHolle\PowerSchool\Api;

use GrantHolle\PowerSchool\Request;

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

    /* @var bool */
    private $includeProjection = true;

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
     * Configures the request to be a core resource with optional method and data that
     * will send the request automatically.
     *
     * @param string $endpoint
     * @param string $method
     * @param Array $data
     * @return $this
     */
    public function resource(string $endpoint, string $method = null, Array $data = [])
    {
        $this->endpoint = $endpoint;
        $this->includeProjection = false;

        if (!is_null($method)) {
            $this->method = $method;
        }

        if (!empty($data)) {
            $this->data = $data;
        }

        // If the method and data are set, automatically send the request
        if (!is_null($this->method) && !empty($this->data)) {
            return $this->send();
        }

        return $this;
    }

    /**
     * Does not force a projection parameter for GET requests
     *
     * @return $this
     */
    public function excludeProjection()
    {
        $this->includeProjection = false;

        return $this;
    }

    /**
     * Sets the endpoint for the request
     *
     * @param string $endpoint
     * @return $this
     */
    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Alias for setEndpoint()
     *
     * @param string $endpoint
     * @return $this
     */
    public function toEndpoint(string $endpoint)
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * Sets the endpoint to the named query
     *
     * @param string $query The named query name (com.organization.product.area.name)
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
     * @param string $query The named query name (com.organization.product.area.name)
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
     * @param string $query The named query name (com.organization.product.area.name)
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
     * @param string $query The named query name (com.organization.product.area.name)
     * @param Array $data
     * @return Mixed
     */
    public function pq(string $query, Array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Sets the data for the post/put/patch requests
     * Also performs basic sanitation for PS, such
     * as boolean translation
     *
     * @param Array $data
     * @return $this
     */
    public function setData(Array $data)
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // If it's null don't include it with the request
            if (is_null($value)) {
                continue;
            }

            // If the type is a boolean, set it to the
            // integer type that PS uses, 1 or 0
            if (gettype($value) === 'boolean') {
                $value = $value ? '1' : '0';
            }

            // Cast everything as a string, otherwise PS
            // with throw a typecast error or something
            $sanitized[$key] = (string)$value;
        }

        $this->data = $sanitized;

        return $this;
    }

    /**
     * Alias for setData()
     *
     * @param Array $data
     * @return $this
     */
    public function withData(Array $data)
    {
        return $this->setData($data);
    }

    /**
     * Alias for setData()
     *
     * @param Array $data
     * @return $this
     */
    public function with(Array $data)
    {
        return $this->setData($data);
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
     * Checks to see if a query variable has been set
     *
     * @param string $key
     * @return boolean
     */
    public function hasQueryVar(string $key)
    {
        return !empty($this->queryString[$key]);
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
        if ($this->method === 'get') {
            $this->options['query'] = '';

            // Build the query string
            foreach ($this->queryString as $var => $val) {
                $this->options['query'] .= $var . '=' . $val . '&';
            }

            // Get requests are required to have a projection parameter
            if (!$this->hasQueryVar('projection') && $this->includeProjection) {
                $this->options['query'] .= 'projection=*&';
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
