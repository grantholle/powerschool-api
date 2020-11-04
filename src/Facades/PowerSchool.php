<?php

namespace GrantHolle\PowerSchool\Api\Facades;

use GrantHolle\PowerSchool\Api\Request;
use Illuminate\Support\Facades\Facade;
use GrantHolle\PowerSchool\Api\RequestBuilder;

/**
 * @method static Request getRequest()
 * @method static void freshen()
 * @method static RequestBuilder setTable(string $table)
 * @method static RequestBuilder table(string $table)
 * @method static RequestBuilder forTable(string $table)
 * @method static RequestBuilder againstTable(string $table)
 * @method static RequestBuilder setId($id)
 * @method static RequestBuilder id($id)
 * @method static RequestBuilder forId($id)
 * @method static mixed resource(string $endpoint, string $method = null, array $data = [])
 * @method static RequestBuilder excludeProjection()
 * @method static RequestBuilder withoutProjection()
 * @method static RequestBuilder setEndpoint(string $endpoint)
 * @method static RequestBuilder toEndpoint(string $endpoint)
 * @method static RequestBuilder to(string $endpoint)
 * @method static RequestBuilder endpoint(string $endpoint)
 * @method static RequestBuilder setNamedQuery(string $query, array $data = [])
 * @method static RequestBuilder namedQuery(string $query, array $data = [])
 * @method static RequestBuilder powerQuery(string $query, array $data = [])
 * @method static RequestBuilder pq(string $query, array $data = [])
 * @method static RequestBuilder setData(array $data)
 * @method static RequestBuilder withData(array $data)
 * @method static RequestBuilder with(array $data)
 * @method static RequestBuilder withQueryString($queryString)
 * @method static RequestBuilder query($queryString)
 * @method static RequestBuilder addQueryVar(string $key, $val)
 * @method static bool hasQueryVar(string $key)
 * @method static RequestBuilder q(string $query)
 * @method static RequestBuilder projection($projection)
 * @method static RequestBuilder pageSize(int $pageSize)
 * @method static RequestBuilder dataVersion(int $version, string $applicationName)
 * @method static RequestBuilder withDataVersion(int $version, string $applicationName)
 * @method static RequestBuilder expansions(array|string $expansions)
 * @method static RequestBuilder withExpansions(array|string $expansions)
 * @method static RequestBuilder extensions(array|string $extensions)
 * @method static RequestBuilder withExtensions(array|string $extensions)
 * @method static RequestBuilder count()
 * @method static RequestBuilder raw()
 * @method static mixed asResponse()
 * @method static mixed send()
 * @method static RequestBuilder setMethod(string $method)
 * @method static RequestBuilder method(string $method)
 * @method static mixed get(string $endpoint = null)
 * @method static mixed post()
 * @method static mixed put()
 * @method static mixed patch()
 * @method static mixed delete()
 * @method static mixed getDataSubscriptionChanges(string $applicationName, int $version)
 * @method static RequestBuilder includeCount()
 * @method static RequestBuilder page(int $page)
 * @method static RequestBuilder sort($columns, bool $descending = false)
 * @method static RequestBuilder adHocOrder(string $expression)
 * @method static RequestBuilder order(string $expression)
 * @method static RequestBuilder queryExpression(string $expression)
 * @method static RequestBuilder adHocFilter(string $expression)
 * @method static RequestBuilder filter(string $expression)
 * @method static void buildRequestJson()
 * @method static void buildRequestQuery()
 */
class PowerSchool extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RequestBuilder::class;
    }
}
