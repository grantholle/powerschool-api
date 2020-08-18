<?php

namespace GrantHolle\PowerSchool\Api;

class RequestBuilder {

    /* @var Request */
    private $request;

    /* @var string */
    private $endpoint;

    /* @var string */
    private $method;

    /* @var array */
    private $options = [];

    /* @var array */
    private $data;

    /* @var string */
    private $table;

    /* @var string */
    private $queryString = [];

    /* @var string */
    private $id;

    /* @var bool */
    private $includeProjection = true;

    /* @var bool */
    private $asResponse = false;

    /**
     * Constructor
     *
     * @param string|null $serverAddress
     * @param string|null $clientId
     * @param string|null $clientSecret
     */
    public function __construct(string $serverAddress = null, string $clientId = null, string $clientSecret = null)
    {
        $this->request = new Request($serverAddress, $clientId, $clientSecret);
    }

    /**
     * Gets the underlying request object
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Cleans all the variables for the next request
     *
     * @return void
     */
    public function freshen()
    {
        $this->endpoint = null;
        $this->method = null;
        $this->options = [];
        $this->data = null;
        $this->table = null;
        $this->queryString = [];
        $this->id = null;
        $this->includeProjection = true;
        $this->asResponse = false;
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
     * @param mixed $id
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
     * @param mixed $id
     * @return $this
     */
    public function id($id)
    {
        return $this->setId($id);
    }

    /**
     * Alias for setId()
     *
     * @param mixed $id
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
     * @param array $data
     * @return array|RequestBuilder
     */
    public function resource(string $endpoint, string $method = null, array $data = [])
    {
        $this->endpoint = $endpoint;
        $this->includeProjection = false;

        if (!is_null($method)) {
            $this->method = $method;
        }

        if (!empty($data)) {
            $this->setData($data);
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
     * Alias for setEndpoint()
     *
     * @param string $endpoint
     * @return $this
     */
    public function to(string $endpoint)
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * Alias for setEndpoint()
     *
     * @param string $endpoint
     * @return $this
     */
    public function endpoint(string $endpoint)
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * Sets the endpoint to the named query
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return mixed
     */
    public function setNamedQuery(string $query, array $data = [])
    {
        $this->endpoint = '/ws/schema/query/' . $query;

        // If there's data along with it,
        // it's short hand for sending the request
        if (!empty($data)) {
            return $this->withData($data)->post();
        }

        return $this->setMethod('post');
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return mixed
     */
    public function namedQuery(string $query, array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return mixed
     */
    public function powerQuery(string $query, array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return mixed
     */
    public function pq(string $query, array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Casts all the values recursively as a string
     *
     * @param array $data
     * @return array
     */
    function castToValuesString(array $data) {
        foreach ($data as $key => $value) {
            // Recursively set the nested array values
            if (is_array($value)) {
                $data[$key] = $this->castToValuesString($value);
                continue;
            }

            // If it's null set the value to an empty string
            if (is_null($value)) {
                $value = '';
            }

            // If the type is a boolean, set it to the
            // integer type that PS uses, 1 or 0
            if (gettype($value) === 'boolean') {
                $value = $value ? '1' : '0';
            }

            // Cast everything as a string, otherwise PS
            // with throw a typecast error or something
            $data[$key] = (string)$value;
        }

        return $data;
    }

    /**
     * Sets the data for the post/put/patch requests
     * Also performs basic sanitation for PS, such
     * as boolean translation
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $this->castToValuesString($data);

        return $this;
    }

    /**
     * Alias for setData()
     *
     * @param array $data
     * @return $this
     */
    public function withData(array $data)
    {
        return $this->setData($data);
    }

    /**
     * Alias for setData()
     *
     * @param array $data
     * @return $this
     */
    public function with(array $data)
    {
        return $this->setData($data);
    }

    /**
     * Sets the query string for get requests
     *
     * @param mixed $queryString
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
     * @param mixed $queryString
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
     * @param mixed $val
     * @return $this
     */
    public function addQueryVar(string $key, $val)
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
     * @param string|array $projection
     * @return RequestBuilder
     */
    public function projection($projection)
    {
        if (is_array($projection)) {
            $projection = implode(',', $projection);
        }

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
     * Sends a count request to the table api
     *
     * @return mixed
     */
    public function count()
    {
        $this->endpoint .= '/count';
        $this->includeProjection = false;

        return $this->get();
    }

    /**
     * Sets a flag to return as a decoded json rather than an Illuminate\Response
     *
     * @return $this
     */
    public function raw()
    {
        $this->asResponse = false;

        return $this;
    }

    /**
     * Sets the flag to return a response
     *
     * @return $this
     */
    public function asResponse()
    {
        $this->asResponse = true;

        return $this;
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

        // Reset the json object from previous requests
        $this->options['json'] = [];

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

        // Remove the json option if there is nothing there
        if (count($this->options['json']) === 0) {
            unset($this->options['json']);
        }
    }

    /**
     * Sends the request to PowerSchool
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     */
    public function send()
    {
        $this->buildRequestJson();

        // Build the query by hand
        if ($this->method === 'get' || $this->method === 'post') {
            $this->options['query'] = '';

            // Build the query string
            foreach ($this->queryString as $var => $val) {
                $this->options['query'] .= $var . '=' . $val . '&';
            }

            // Get requests are required to have a projection parameter
            if (!$this->hasQueryVar('projection') && $this->includeProjection && $this->method === 'get') {
                $this->options['query'] .= 'projection=*&';
            }

            if (!empty($this->options['query'])) {
                $this->options['query'] = substr($this->options['query'], 0, -1);
            }
        }

        $response = $this->request->makeRequest($this->method, $this->endpoint, $this->options, $this->asResponse);

        $this->freshen();

        return $response;
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
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     */
    public function get()
    {
        return $this->setMethod('get')->send();
    }

    /**
     * Sets method to post, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     */
    public function post()
    {
        return $this->setMethod('post')->send();
    }

    /**
     * Sets method to put, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     */
    public function put()
    {
        return $this->setMethod('put')->send();
    }

    /**
     * Sets method to patch, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     */
    public function patch()
    {
        return $this->setMethod('patch')->send();
    }

    /**
     * Sets method to delete, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     */
    public function delete()
    {
        return $this->setMethod('delete')->send();
    }
}