# PowerSchool API

Taking inspiration from Laravel's database and Eloquent `Builder` class, this allows you to make api requests against PowerSchool very fluently and naturally. It handles token authentication automatically, so you can just worry about writing the requests and not the boilerplate.

This package is to be used with alongside a PowerSchool plugin that has enabled `<oauth></oauth>` in the `plugin.xml`. This guide assumes you have PowerSchool API and plugin knowledge and does not cover the details of a plugin or its API.

- [API](#api)
- [SSO](#sso)

## Installation

```
$ composer require grantholle/powerschool-api
```

The package will be automatically discovered by Laravel, so there's no reason to add it to `config/app.php` unless you want to.

## Configuration

A config file needs to be created to store the server address, client ID, and secret to interact with PowerSchool.

```
$ php artisan vendor:publish --provider=GrantHolle\PowerSchool\PowerSchoolServiceProvider
```

This will generate `config/powerschool.php`. We then need to set some variables in `.env`.

```
POWERSCHOOL_ADDRESS=
POWERSCHOOL_CLIENT_ID=
POWERSCHOOL_CLIENT_SECRET=
```

It also generates a migration file. If you're not using SSO, you can ignore this feature. If you're not, you'll need to add the applicable user model.

## Commands

```
# Removes existing authorization token cache
$ php artisan powerschool:clear

# Fetches authorization token and caches it
$ php artisan powerschool:auth
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

_Aliases: toEndpoint(), to(), endpoint()_

Sets the endpoint for core PS resources.

```php
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

$response = PS::toEndpoint('/ws/v1/student')->with($requestData)->post();
```

## SSO

This package provides some SSO helpers so you can use PowerSchool as an OpenID provider for authentication. It will authenticate against PowerSchool and perform an attribute exchange requesting all the fields that PowerSchool supports. If there are some attributes that are missing please open an issue.

After following all of the below steps, your application will be able to authenticate

### Migration

There is a migration that can be generated by `php artisan vendor:publish --tag=migrations`. This will add an `openid_identifier` column in your users table. This is what is used to retrieve the user that is getting authenticated.

### Controller

In a fresh Laravel installation, you will have an `Auth\LoginController`. Replace the default `AuthenticatesUsers` trait with the new `AuthenticatesPowerSchool` trait.

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use GrantHolle\PowerSchool\Traits\AuthenticatesPowerSchoolWithOpenId;

class LoginController extends Controller
{
    use AuthenticatesPowerSchoolWithOpenId;
    
    // ...
}
```

There is also a "hook" after authentication to handle the data requested during the attribute exchange. In your `LoginController`, you can define the `authenticated()` function to do some other processing with the data received, presumably to add/update attributes for the user.

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use GrantHolle\PowerSchool\Traits\AuthenticatesPowerSchoolWithOpenId;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LoginController extends Controller
{
    use AuthenticatesPowerSchoolWithOpenId;

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @param  \Illuminate\Support\Collection  $data
     * @return mixed
     */
    protected function authenticated(Request $request, $user, Collection $data)
    {
        // Do something
        $user->email = $data->get('openid_ext1_value_email');

        // Do something else...

        // Return a Response,
        // or nothing and be automatically redirected to `$redirectTo`
    }
}
```

### Routes

You will now need to define your routes in `web.php`.

```php
Route::get('powerschool/registration', 'Auth\LoginController@authenticate');
Route::get('powerschool/verify', 'Auth\LoginController@login')->name('sso.verify');
```

The paths can be changed to whatever suits your fancy. You **must** register these two routes to use the `authenticate` (receives the auth request and requests attributes) and `login` (completes the authentication cycle) methods on your `LoginController`. The route that gets handled by the `login` function **must** be named `sso.verify` so the trait knows the route.

There is also a `logout` method that mimicks Laravel's own function. It also calls `loggedOut()` after the user is logged out.

```php
Route::get('/logout', 'Auth\LoginController@logout')->name('logout');
```

### PowerSchool Plugin

To utilize this feature, you need to add a plugin with the following configuration (at a minimum). The `path` attribute on your `link` must match the path you set in `web.php` for the `authenticate` function in your controller.

```xml
<openid host="example.com">
  <links>
    <link title="My SSO App" display-text="My App" path="/powerschool/registration">
      <ui_contexts>
        <ui_context id="admin.header"/>
      </ui_contexts>
    </link>
  </links>
</openid>
```
