<?php

namespace GrantHolle\PowerSchool\Api\Facades;

use GrantHolle\PowerSchool\Api\Request;
use GrantHolle\PowerSchool\Api\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;
use GrantHolle\PowerSchool\Api\RequestBuilder;

/**
 * @var string GET
 * @var string POST
 * @var string PUT
 * @var string PATCH
 * @var string DELETE
 * @method static Request getRequest()
 * @method static void freshen()
 * @method static RequestBuilder setTable(string $table)
 * @method static RequestBuilder table(string $table)
 * @method static RequestBuilder forTable(string $table)
 * @method static RequestBuilder againstTable(string $table)
 * @method static RequestBuilder setId($id)
 * @method static RequestBuilder id($id)
 * @method static RequestBuilder forId($id)
 * @method static null|Response|static resource(string $endpoint, string $method = null, array $data = [])
 * @method static RequestBuilder excludeProjection()
 * @method static RequestBuilder withoutProjection()
 * @method static RequestBuilder setEndpoint(string $endpoint)
 * @method static RequestBuilder toEndpoint(string $endpoint)
 * @method static RequestBuilder to(string $endpoint)
 * @method static RequestBuilder endpoint(string $endpoint)
 * @method static RequestBuilder setNamedQuery(string $query, array $data = [])
 * @method static RequestBuilder namedQuery(string $query, array $data = [])
 * @method static RequestBuilder powerQuery(string $query, array $data = [])
 * @method static RequestBuilder|Response pq(string $query, array $data = [])
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
 * @method static RequestBuilder asResponse()
 * @method static Response|JsonResponse|null send()
 * @method static RequestBuilder setMethod(string $method)
 * @method static RequestBuilder method(string $method)
 * @method static Response|null get(string $endpoint = null)
 * @method static Response|null post()
 * @method static Response|null put()
 * @method static Response|null patch()
 * @method static Response|null delete()
 * @method static Response|null getDataSubscriptionChanges(string $applicationName, int $version)
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
