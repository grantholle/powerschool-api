# PowerSchool API

Taking inspiration from Laravel's database and Eloquent `Builder` class, this allows you to make api requests against PowerSchool very fluently and naturally. It handles token authentication automatically, so you can just worry about writing the requests and not the boilerplate.

This package is to be used with alongside a PowerSchool plugin that has enabled `<oauth></oauth>` in the `plugin.xml`. This guide assumes you have PowerSchool API and plugin knowledge and does not cover the details of a plugin or its API.

## Installation

```
$ composer require grantholle/powerschool-api
```

The package will be automatically discovered by Laravel, so there's no reason to add it to `config/app.php` unless you want to.

## Configuration

A config file needs to be created to store the server address, client ID, and secret to interact with PowerSchool.

```
$ php artisan vendor:publish --provider=GrantHolle\PowerSchool\PowerschoolServiceProvider
```

This will generate `config/powerschool.php`. We then need to set some variables in `.env`.

```
POWERSCHOOL_ADDRESS=
POWERSCHOOL_CLIENT_ID=
POWERSCHOOL_CLIENT_SECRET=
```

## Commands

```
# Removes existing authorization token cache
$ php artisan powerschool:clear
```

## API

Using the facade, `PS`, you can fluently build a request for PowerSchool. By also providing several aliases to key functions, you can write requests in a way that feels comfortable to you and is easy to read. Below are examples that build on each other. See examples below to put them all together.

### `setTable(string $table)`

_Aliases: table(), forTable(), againstTable()_

This "sets" the table with which you're interacting. Applies to database extensions.

```php
$request = PS::table('u_my_custom_table');
```

### `setId($id)`

_Aliases: id(), forId()_

Sets the id for a get, put, or delete request when interacting with a specific entry in the database.

```php
$request = PS::table('u_my_custom_table')->forId(100);
```

### `setMethod(string $method)`

_Aliases: method(), get(), post(), put(), patch(), delete()_

Sets the http verb for the request. When using the functions `get`, `post`, `put`, `patch`, or `delete`, the request is sent automatically.

```php
// This request is still not sent
$request = PS::table('u_my_custom_table')->setId(100)->method('get');

// This request is set to the get method and sent automatically
$response = PS::table('u_my_custom_table')->id(100)->get();
$response = PS::table('u_my_custom_table')->id(100)->get();
$response = PS::table('u_my_custom_table')->id(100)->delete();

// The above example could be rewritten like this...
$response = PS::table('u_my_custom_table')->id(100)->setMethod('get')->send();
```

### `setData(Array $data)`

_Aliases: withData(), with()_

Sets the data that gets sent with requests. If it's for a custom table, you can just send the fields and their values and the structure that is compatible with PowerSchool is build automatically. If it's a named query, it's just the `args` that have been configured with the query.

```php
$data = [
  'column_one' => 'value',
  'column_two' => 'value',
];

// Doing "table" requests, this not getting sent
$request = PS::table('u_my_custom_table')->with($data)->method('post');

// A PowerQuery (see below)
$response = PS::pq('com.organization.product.area.name')->withData($data);
```

### `setNamedQuery(string $query, Array $data = [])`

_Aliases: namedQuery(), powerQuery(), pq()_

The first parameter is the name of the query, following the required convention set forth by PowerSchool, `com.organization.product.area.name`. The second is the data that you may need to perform the query which has been configured in the plugin's named query xml file. If the data is included, the request is sent automatically.

```php
// Will not get sent
$request = PS::powerQuery('com.organization.product.area.name');

// Gets posted automatically
$response = PS::powerQuery('com.organization.product.area.name', ['schoolid' => '100']);
```

### `setEndpoint(string $query)`

_Aliases: toEndpoint()_

Sets the endpoint for core PS resources.

```php
// Will not get sent
$request = PS::powerQuery('com.organization.product.area.name');

// Gets posted automatically
$response = PS::powerQuery('com.organization.product.area.name', ['schoolid' => '100']);
```
