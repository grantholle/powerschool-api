<?php

namespace GrantHolle\PowerSchool\Api;

class RequestBuilder {

    const GET = 'get';
    const POST = 'post';
    const PUT = 'put';
    const PATCH = 'patch';
    const DELETE = 'delete';

    /* @var Request */
    protected $request;

    /* @var string */
    protected $endpoint;

    /* @var string */
    protected $method;

    /* @var array */
    protected $options = [];

    /* @var array */
    protected $data;

    /* @var string */
    protected $table;

    /* @var string */
    protected $queryString = [];

    /* @var string */
    protected $id;

    /* @var bool */
    protected $includeProjection = false;

    /* @var bool */
    protected $asResponse = false;

    /** @var Paginator */
    protected $paginator;

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
        $this->includeProjection = false;
        $this->asResponse = false;
    }

    /**
     * Sets the table for a request against a custom table
     *
     * @param string $table
     * @return RequestBuilder
     */
    public function setTable(string $table)
    {
        $this->table = $table;
        $this->endpoint = '/ws/schema/table/' . $table;
        $this->includeProjection = true;

        return $this;
    }

    /**
     * Alias for setTable()
     *
     * @param string $table
     * @return RequestBuilder
     */
    public function table(string $table)
    {
        return $this->setTable($table);
    }

    /**
     * Alias for setTable()
     *
     * @param string $table
     * @return RequestBuilder
     */
    public function forTable(string $table)
    {
        return $this->setTable($table);
    }

    /**
     * Alias for setTable()
     *
     * @param string $table
     * @return RequestBuilder
     */
    public function againstTable(string $table)
    {
        return $this->setTable($table);
    }

    /**
     * Sets the id of the resource we're interacting with
     *
     * @param mixed $id
     * @return RequestBuilder
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
     * @return RequestBuilder
     */
    public function id($id)
    {
        return $this->setId($id);
    }

    /**
     * Alias for setId()
     *
     * @param mixed $id
     * @return RequestBuilder
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
     * @param string|null $method
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
     * @return RequestBuilder
     */
    public function excludeProjection()
    {
        $this->includeProjection = false;

        return $this;
    }

    /**
     * Alias of excludeProjection()
     *
     * @return $this
     */
    public function withoutProjection()
    {
        return $this->excludeProjection();
    }

    /**
     * Sets the endpoint for the request
     *
     * @param string $endpoint
     * @return RequestBuilder
     */
    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;

        return $this->excludeProjection();
    }

    /**
     * Alias for setEndpoint()
     *
     * @param string $endpoint
     * @return RequestBuilder
     */
    public function toEndpoint(string $endpoint)
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * Alias for setEndpoint()
     *
     * @param string $endpoint
     * @return RequestBuilder
     */
    public function to(string $endpoint)
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * Alias for setEndpoint()
     *
     * @param string $endpoint
     * @return RequestBuilder
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
     * @return RequestBuilder|mixed
     */
    public function setNamedQuery(string $query, array $data = [])
    {
        $this->endpoint = '/ws/schema/query/' . $query;

        // If there's data along with it,
        // it's short hand for sending the request
        if (!empty($data)) {
            return $this->withData($data)->post();
        }

        // By default don't include the projection unless
        // it gets added later explicitly
        $this->includeProjection = false;

        return $this->setMethod(static::POST);
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
     * Sets the data for the post/put/patch requests
     * Also performs basic sanitation for PS, such
     * as bool translation
     *
     * @param array $data
     * @return RequestBuilder
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
     * @return RequestBuilder
     */
    public function withData(array $data)
    {
        return $this->setData($data);
    }

    /**
     * Alias for setData()
     *
     * @param array $data
     * @return RequestBuilder
     */
    public function with(array $data)
    {
        return $this->setData($data);
    }

    /**
     * Sets an item to be included in the post request
     *
     * @param string $key
     * @param $value
     * @return $this
     */
    public function setDataItem(string $key, $value)
    {
        $this->data[$key] = $this->castToValuesString($value);

        return $this;
    }

    /**
     * Sets the query string for get requests
     *
     * @param mixed $queryString
     * @return RequestBuilder
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
     * @return RequestBuilder
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
     * @return RequestBuilder
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
     * @return bool
     */
    public function hasQueryVar(string $key)
    {
        return isset($this->queryString[$key]) &&
            !empty($this->queryString[$key]);
    }

    /**
     * Syntactic sugar for the q query string var
     *
     * @param string $query
     * @return RequestBuilder
     */
    public function q(string $query)
    {
        return $this->addQueryVar('q', $query);
    }

    /**
     * Sugar for q()
     *
     * @param string $expression
     * @return RequestBuilder
     */
    public function queryExpression(string $expression)
    {
        return $this->q($expression);
    }

    /**
     * Adds an ad-hoc filter expression, meant to be used for PowerQueries
     *
     * @param string $expression
     * @return $this
     */
    public function adHocFilter(string $expression)
    {
        return $this->addQueryVar('$q', $expression);
    }

    /**
     * Sugar for adHocFilter()
     *
     * @param string $expression
     * @return $this
     */
    public function filter(string $expression)
    {
        return $this->adHocFilter($expression);
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
        $this->includeProjection = true;

        return $this->addQueryVar('projection', $projection);
    }

    /**
     * Syntactic sugar for the pagesize query string var
     *
     * @param int $pageSize
     * @return RequestBuilder
     */
    public function pageSize(int $pageSize)
    {
        return $this->addQueryVar('pagesize', $pageSize);
    }

    /**
     * Sets the page query variable
     *
     * @param int $page
     * @return RequestBuilder
     */
    public function page(int $page)
    {
        return $this->addQueryVar('page', $page);
    }

    /**
     * Sets the sorting columns and direction for the request
     *
     * @param string|array $columns
     * @param bool $descending
     * @return RequestBuilder
     */
    public function sort($columns, bool $descending = false)
    {
        $sort = is_array($columns)
            ? implode(',', $columns)
            : $columns;

        $this->addQueryVar('sort', $sort);
        $this->addQueryVar('sortdescending', $descending ? 'true' : 'false');

        return $this;
    }

    /**
     * Adds an order query string variable
     *
     * @param string $expression
     * @return $this
     */
    public function adHocOrder(string $expression)
    {
        return $this->addQueryVar('order', $expression);
    }

    /**
     * Sugar for adHocOrder()
     *
     * @param string $expression
     * @return $this
     * @see RequestBuilder::adHocOrder()
     */
    public function order(string $expression)
    {
        return $this->adHocOrder($expression);
    }

    /**
     * Adds the count query variable for
     *
     * @return $this
     */
    public function includeCount()
    {
        return $this->addQueryVar('count', 'true');
    }

    /**
     * Configures the data version for the PowerQuery
     *
     * @param int $version
     * @param string $applicationName
     * @return $this
     */
    public function dataVersion(int $version, string $applicationName)
    {
        $this->setDataItem('$dataversion', $version);
        $this->setDataItem('$dataversion_applicationname', $applicationName);

        return $this;
    }

    /**
     * Alias of dataVersion()
     *
     * @param int $version
     * @param string $applicationName
     * @return $this
     */
    public function withDataVersion(int $version, string $applicationName)
    {
        return $this->dataVersion($version, $applicationName);
    }

    /**
     * Adds `expansions` query variable
     *
     * @param string|array $expansions
     * @return $this
     */
    public function expansions($expansions)
    {
        $expansions = is_array($expansions)
            ? implode(',', $expansions)
            : $expansions;

        $this->addQueryVar('expansions', $expansions);

        return $this;
    }

    /**
     * Alias of expansions()
     *
     * @param string|array $expansions
     * @return $this
     */
    public function withExpansions($expansions)
    {
        return $this->expansions($expansions);
    }

    /**
     * Adds `expansions` query variable
     *
     * @param string|array $extensions
     * @return $this
     */
    public function extensions($extensions)
    {
        $extensions = is_array($extensions)
            ? implode(',', $extensions)
            : $extensions;

        $this->addQueryVar('extensions', $extensions);

        return $this;
    }

    /**
     * Alias of expansions()
     *
     * @param string|array $extensions
     * @return $this
     */
    public function withExtensions($extensions)
    {
        return $this->expansions($extensions);
    }

    /**
     * Gets the data changes based on the data version subscription
     *
     * @param string $applicationName
     * @param int $version
     * @return array
     * @throws Exception\MissingClientCredentialsException
     */
    public function getDataSubscriptionChanges(string $applicationName, int $version)
    {
        return $this->endpoint("/ws/dataversion/{$applicationName}/{$version}")
            ->get();
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
     * @return RequestBuilder
     */
    public function raw()
    {
        $this->asResponse = false;

        return $this;
    }

    /**
     * Sets the flag to return a response
     *
     * @return RequestBuilder
     */
    public function asResponse()
    {
        $this->asResponse = true;

        return $this;
    }

    /**
     * Casts all the values recursively as a string
     *
     * @param array $data
     * @return array
     */
    protected function castToValuesString(array $data) {
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

            // If the type is a bool, set it to the
            // integer type that PS uses, 1 or 0
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            // Cast everything as a string, otherwise PS
            // with throw a typecast error or something
            $data[$key] = (string) $value;
        }

        return $data;
    }

    /**
     * Builds the dumb request structure for PowerSchool
     *
     * @return RequestBuilder
     */
    public function buildRequestJson()
    {
        if ($this->method === static::GET || $this->method === 'delete') {
            return $this;
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
        if (empty($this->options['json'])) {
            unset($this->options['json']);
        }

        return $this;
    }

    /**
     * Builds the query string for the request
     *
     * @return RequestBuilder
     */
    public function buildRequestQuery()
    {
        // Build the query by hand
        if ($this->method !== static::GET && $this->method !== static::POST) {
            return $this;
        }

        $this->options['query'] = '';$this->options['query'] = '';
        $qs = [];

        // Build the query string
        foreach ($this->queryString as $var => $val) {
            $qs[] = $var . '=' . $val;
        }

        // Get requests are required to have a projection parameter
        if (
            !$this->hasQueryVar('projection') &&
            $this->includeProjection
        ) {
            $qs[] = 'projection=*';
        }

        if (!empty($qs)) {
            $this->options['query'] = implode('&', $qs);
        }

        return $this;
    }

    /**
     * Sets the request method
     *
     * @param string $method
     * @return RequestBuilder
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
     * @return RequestBuilder
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get()
    {
        return $this->setMethod(static::GET)->send();
    }

    /**
     * Sets method to post, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post()
    {
        return $this->setMethod(static::POST)->send();
    }

    /**
     * Sets method to put, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function put()
    {
        return $this->setMethod(static::PUT)->send();
    }

    /**
     * Sets method to patch, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function patch()
    {
        return $this->setMethod(static::PATCH)->send();
    }

    /**
     * Sets method to delete, sugar around setMethod(), then sends the request
     *
     * @return array
     * @throws \GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete()
    {
        return $this->setMethod(static::DELETE)->send();
    }

    /**
     * Sends the request to PowerSchool
     *
     * @param bool $reset
     * @return \stdClass
     * @throws Exception\MissingClientCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(bool $reset = true)
    {
        $this->buildRequestJson()
            ->buildRequestQuery();

        $response = $this->getRequest()
            ->makeRequest(
                $this->method,
                $this->endpoint,
                $this->options,
                $this->asResponse
            );

        if ($reset) {
            $this->freshen();
        }

        return $response;
    }

    /**
     * This will return a chunk of data from PS
     * NOTE: this is currently only supported by PowerQueries
     *
     * @param int $pageSize
     * @return array|false
     */
    public function paginate(int $pageSize = 100)
    {
        if (!isset($this->paginator)) {
            $this->paginator = new Paginator($this, 1, $pageSize);
        }

        $results = $this->paginator->page();

        if ($results === false) {
            unset($this->paginator);
        }

        return $results;
    }
}
