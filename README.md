# PowerSchool API

Taking inspiration from Laravel's database and Eloquent `Builder` class, this allows you to make api requests against PowerSchool very fluently and naturally. It handles token authentication automatically, so you can just worry about writing the requests and not the boilerplate.

This package is to be used with alongside a PowerSchool plugin that has enabled `<oauth/>` in the `plugin.xml`. This guide assumes you have PowerSchool API and plugin knowledge and does not cover the details of a plugin or its API.

## Breaking changes for v3

- Requires PHP ^8.0
- Requests return a new `Response` instead of `stdClass`, [see below](#responses) for details

## Breaking changes for v2

- SSO functionality has been abstracted to a new package, [`grantholle/laravel-powerschool-auth`](https://github.com/grantholle/laravel-powerschool-auth) 
- The namespace is now `GrantHolle\PowerSchool\Api`

More functionality was added in v2, along with tests for peace of mind. 

## Installation

```bash
composer require grantholle/powerschool-api
```

The package will be automatically discovered by Laravel, so there's no reason to add it to `config/app.php` unless you want to.

## Configuration

You need to set some variables in `.env`.

```
POWERSCHOOL_ADDRESS=
POWERSCHOOL_CLIENT_ID=
POWERSCHOOL_CLIENT_SECRET=
```

Optionally, you can publish the config file to store the server address, client ID, and secret to interact with PowerSchool. This will generate `config/powerschool.php`, but is not necessary.

```bash
php artisan vendor:publish --provider="GrantHolle\PowerSchool\Api\PowerSchoolApiServiceProvider"
```

## Debugging

You can enable debugging with [Ray](https://myray.app/) that will display the raw and transformed responses for each request. This is helpful in viewing the response from PowerSchool and the `GrantHolle\PowerSchool\Api\Response` object's data. You will need to install the [Laravel package](https://spatie.be/docs/ray/v1/installation-in-your-project/laravel) and enable debugging:

```
# App debug needs to be enabled also
APP_DEBUG=true

POWERSCHOOL_DEBUG=true
```

## Commands

```bash
# Removes existing authorization token cache
php artisan powerschool:clear

# Fetches authorization token and caches it
php artisan powerschool:auth
```

## API

Using the facade, `GrantHolle\PowerSchool\Api\Facades\PowerSchool`, you can fluently build a request for PowerSchool. By also providing several aliases to key functions, you can write requests in a way that feels comfortable to you and is easy to read. Below are examples that build on each other. See examples below to put them all together.

#### `setTable(string $table)`

_Aliases: table(), forTable(), againstTable()_

This "sets" the table with which you're interacting. Applies to database extensions.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

$request = PowerSchool::table('u_my_custom_table');
```

#### `setId($id)`

_Aliases: id(), forId()_

Sets the id for a get, put, or delete request when interacting with a specific entry in the database.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

$request = PowerSchool::table('u_my_custom_table')->forId(100);
```

#### `setMethod(string $method)`

_Aliases: method(), get(), post(), put(), patch(), delete()_

Sets the HTTP verb for the request. When using the functions `get()`, `post()`, `put()`, `patch()`, or `delete()`, the request is sent automatically.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

// This request is still not sent
$request = PowerSchool::table('u_my_custom_table')->setId(100)->method('get');

// This request is set to the get method and sent automatically
$response = PowerSchool::table('u_my_custom_table')->id(100)->get();
$response = PowerSchool::table('u_my_custom_table')->id(100)->get();
$response = PowerSchool::table('u_my_custom_table')->id(100)->delete();

// The above example could be rewritten like this...
$response = PowerSchool::table('u_my_custom_table')->id(100)->setMethod('get')->send();
```

#### `setData(Array $data)`

_Aliases: withData(), with()_

Sets the data that gets sent with requests. If it's for a custom table, you can just send the fields and their values and the structure that is compatible with PowerSchool is build automatically. If it's a named query, it's just the `args` that have been configured with the query.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

$data = [
  'column_one' => 'value',
  'column_two' => 'value',
];

// Doing "table" requests, this not getting sent
$request = PowerSchool::table('u_my_custom_table')->with($data)->method('post');

// A PowerQuery (see below)
$response = PowerSchool::pq('com.organization.product.area.name')->withData($data);
```

#### `setNamedQuery(string $query, Array $data = [])`

_Aliases: namedQuery(), powerQuery(), pq()_

The first parameter is the name of the query, following the required convention set forth by PowerSchool, `com.organization.product.area.name`. The second is the data that you may need to perform the query which has been configured in the plugin's named query xml file. If the data is included, the request is sent automatically.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

// Will not get sent
$request = PowerSchool::powerQuery('com.organization.product.area.name');

// Gets posted automatically
$response = PowerSchool::powerQuery('com.organization.product.area.name', ['schoolid' => '100']);
```

#### `setEndpoint(string $query)`

_Aliases: toEndpoint(), to(), endpoint()_

Sets the endpoint for core PS resources.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

$requestData = [
  'students' => [
    'student' => [
      'client_uid' => 100,
      'action' => 'INSERT',
      'local_id' => 100,
      'name' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
      ],
      'demographics' => [
        'gender' => 'M',
        'birth_date' => '2002-08-01',
      ],
      'school_enrollment' => [
        'entry_date' => now()->format('Y-m-d'),
        'exit_date' => now()->subDays(1)->format('Y-m-d'),
        'grade_level' => 10,
        'school_number' => 100,
      ],
    ],
  ],
];

$response = PowerSchool::toEndpoint('/ws/v1/student')->with($requestData)->post();
```

#### `q(string $expression)`

_Aliases: queryExpression()_

Sets the `q` variable to the given FIQL expression.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

PowerSchool::endpoint('/ws/v1/school/3/student')
    ->q('name.last_name==Ada*')
    ->get();
```

#### `adHocFilter(string $expression)`

_Aliases: filter()_

Sets the `$q` query variable for adding ad-hoc filtering to PowerQueries.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

PowerSchool::pq('com.organization.plugin_name.entity.query_name')
    ->filter('number_column=lt=100')
    ->post();
```

#### `adHocOrder(string $expression)`

_Aliases: order()_

Sets the `order` query variable for adding [ad-hoc ordering](https://support.powerschool.com/developer/#/page/powerqueries#adhoc_ordering) to PowerQueries.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

PowerSchool::pq('com.organization.plugin_name.entity.query_name')
    ->order('students.last_name,students.first_name,students.entrydate;desc')
    ->post();
```

#### `pageSize(int $pageSize)`

Sets the `pagesize` query variable.

#### `page(int $page)`

Sets the `page` query variable for pagination.

#### `sort(string|array $columns, bool $descending = false)`

Sets the `sort` and `sortdescending` variables.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

PowerSchool::pq('com.organization.plugin_name.entity.query_name')
    ->sort('column1');

// ?sort=column1&sortdescending=false
```

#### `includeCount()`

Includes the count of all the records in the results.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

PowerSchool::pq('com.pearson.core.guardian.student_guardian_detail')
    ->includeCount()
    ->post();

// {
//    "name":"Students",
//    "count":707625,
//    "record":[
//       {
//          "id":3328,
//          "name":"Students",
//          ...
//       },
//       ... Only first page of actual results returned
//    ],
//    "@extensions":"activities,u_dentistry,studentcorefields,c_studentlocator"
// }
```

#### `withQueryString(string|array $queryString)`

_Aliases: query()_

This will set the query string en masse rather than using the convenience methods.

#### `projection(string|array $projection)`

Sets the `projection` query variable for the request.

#### `excludeProjection()`

_Aliases: withoutProjection()_

Prevents the `projection` query variable from being included in the request.

#### `dataVersion(int $version, string $applicationName)`

_Aliases: withDataVersion()_

Sets the `$dataversion` and `$dataversion_applicationname` data items.

#### `expansions(string|array $expansions)`

_Aliases: withExpansions()_

Adds the `expansions` query variable.

#### `extensions(string|array $expansions)`

_Aliases: withExtensions()_

Adds the `extensions` query variable.

## Performing Requests

There are many ways to perform the request after building queries. At the end of the day, each one sets the method/HTTP verb before calling `send()`. If you'd like to call `send()`, make sure you set the method by calling `method(string $verb)`. There are also helpers to set methods using constants.

```php
use GrantHolle\PowerSchool\Api\RequestBuilder;

RequestBuilder::GET;
RequestBuilder::POST;
RequestBuilder::PUT;
RequestBuilder::PATCH;
RequestBuilder::DELETE;
```

#### `send()`

Sends the request using the verb set. By default will return the results from the query. You can also call `asJsonResponse()` prior to sending to get an instance of Laravel's `JsonResponse` class which could be returned directly to the client.

#### `count()`

Calling `count()` on the builder will perform a count query by appending `/count` to the end of the endpoint and perform the `get` request automatically.

#### `get(string $endpoint = null)` 

Sets the verb to be `get` and sends the request. You can also pass the endpoint directly to set the endpoint and perform the request automatically.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

PowerSchool::get('/ws/v1/staff/111');
```

#### `post()` 

Sets the verb to be `post` and sends the request.

#### `put()` 

Sets the verb to be `put` and sends the request.

#### `path()` 

Sets the verb to be `path` and sends the request.

#### `delete()` 

Sets the verb to be `delete` and sends the request.

#### `getDataSubscriptionChanges(string $applicationName, int $version)`

Performs the "delta pull" for a data version subscription.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

PowerSchool::getDataSubscriptionChanges('myapp', 12345);

// {
//     "$dataversion": "16323283",
//     "tables": {
//         "userscorefields": [
//             802
//         ],
//         "users": [
//             851,
//             769,
//             802,
//             112
//         ]
//     }
// } 
```

## Pagination

When using PowerQueries, you can easily paginate results using the `$builder->paginate($pageSize)` function. You can use this inside of a `while` loop to process all the results in your query more efficiently than returning the full result. The default page size is 100.

```php
use GrantHolle\PowerSchool\Api\Facades\PowerSchool;

// PowerQuery
// You have to set data in a separate function call
// Otherwise the request will be sent automatically
$builder = PowerSchool::pq('com.organization.plugin_name.entity.query_name')
    ->with(['some_variable' => $value]);
    
// "Normal" endpoint
$builder = PowerSchool::to('/ws/v1/school/1/course')
    ->method(PowerSchool::GET);
    
// "Table" endpoints    
$builder = PowerSchool::table('u_my_table')
    ->method(PowerSchool::GET);    

while ($records = $builder->paginate(25)) {
    // Do something awesome
}
```

## Responses

Prior to `v3`, API requests returned a simple `stdClass` instance containing the raw response from PowerSchool. Since `v3`, there's a new `GrantHolle\PowerSchool\Api\Response` class that gets returned.

## Singular responses

Some responses are meant to return a single record, such as a response for `/ws/contacts/contact/{id}`. For these responses, the properties can be accessed just like before.

```php
$response = PowerSchool::to('/ws/contacts/contact/123')
    ->get();

$response->contactId; // 123
```

The `@extensions` and `@expansions` fields will be parsed into `$extensions` and `$expansions` properties as arrays.

```php
$response->extensions;
//[
//    "personcorefields",
//]
```

## List responses

For the responses that return a listing of results, the `Response` can be iterated using `foreach`. You don't need to worry about the property nesting, as the response will be inferred from the type of response.

```php
$results = PowerSchool::to('/ws/v1/district/school')
    ->get();

 foreach ($results as $result) {
     // $result will be a school object
 }
```

For `get` table listings, the results are nested awkwardly. For example,

```php
PowerSchool::table('u_my_table')->get();

// This returns results like
[
    [
        'id' => 1,
        'tables' => [
            'u_my_table' => [
                'column' => '',
                'column' => '',
                // etc
            ]
        ]    
    ],
    // and on and on
]
```

We can reduce the awkwardness of the results by calling `squashTableResponse()` on the `Response` object.

```php
PowerSchool::table('u_my_table')
    ->get()
    ->squashTableResponse();

// Now the results will be simpler
[
    [
        'column' => '',
        'column' => '',
        // etc
    ],
    // and on and on
]
```

## License

[MIT](LICENSE)

## Contributing

Thanks for taking the time to submit an issue or pull request. If it's a new feature, do your best to add a test to cover the functionality. Then run:

```bash
./vendor/bin/phpunit
```
