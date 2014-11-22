# Request
This package aims to facilitate the parse of the querystring and get some information about what data the user want to obtain.

> (!) This package just parses the querystring and returns a formatted array. It doesn't interact with the database or anything like it. The data that will be returned and the application of the filters, orderings, etc, depends of the implementation of the application.

## Installation:
Add the repository and declare the dependency in your `composer.json`.

```json
{
    [...]
    "repositories": [
        [...]
        {
            "type": "git",
            "url": "git@github.com:Cohros/Request.git"
        }
    ],
    "require": {
        [...]
        "sigep/request": "*"
    },
}
```

Run the following command:

```
composer update
```

## Usage
The Request supports pagination, ordinations, filters and searches.


### Pagination
To pagination, Request uses the `page` and `offset` parameters.

Examples:

```
GET /cities
GET /cities?page=2
GET /cities?offset=100
GET /cities?offset=100&page=2
```

- The first request will obtain all cities;
- The second will return the second page of the list. By default, the offset is set to 15, so only 15 cities will be returned;
- The third will return the first 100 cities. The request doesn't specify the page, but defines a offset, so Request assumes that is the first page;
- The fourth request will return the second page of the list, with offset of 100.

To determine if the request wants pagination or not, you use:

```php
$Request = new \Sigep\Request\Request;
$Request->paginate(); // returns a boolean
```

To get the page requested, use:

```php
$Request->page(); // returns a integer
```

To get the offset, use:
```php
$Request->offset(); // returns a integer
```

To set a default offset (the package default is 15), use:
```php
$Request->setDefaultOffset(100); // set the default to 100
```

### Embed
Sometimes the resources have relationships with other resources and you want to be able to provide those relationships if the user want them. The `embed` parameter is used in those situations.

Examples:
```
GET /cities?embed=country
GET /cities?embed=country,state
```

- The first request will return all cities and each one will have it's country;
- The second will return all cities with country and state.

To get the the list of embedded relationships, use:
```php
$Request->embed(); // return a array
```

### Ordination
You can sort the list of objects by any of its properties.

Examples:
```
GET /cities?sort=name
GET /cities?sort=-name
GET /cities?sort=state,name
```

- The first request will return the list of cities ordered by name A-Z;
- The second will return the list of cities ordered by name Z-A;
- The third will return the list of cities ordered by state A-Z and name A-Z;

To get the parameters to ordination, use:
```php
// GET /cities?sort=state,name
$sort = $Request->sort();

/**
$sort will be similar to:
array (
    'state' => 'ASC',
    'name' => 'ASC',
)
**/
```

### Search

Examples:

```
GET /cars?q=peugeot
```

- The above request will search for cars with `peugeot`. The query can be performed checking the name, description, brand or anything that the resource has.

To get the parameter, use:
```php
$Request->search(); // return a string
```

> It's up to the application define how the search will be performed and the operators that will be used (equals, like etc).

### Filters
Filters allow you to filter the results based on its properties.

Examples:
```
GET /user?gender=female
GET /user?age=>20&gender=female
GET /user?age=>20,<30
GET /users?age=20>,30<
GET /users?age=20;30
```

- The first request will return a list of all female users;
- The second will return a list of female users that are older then 20 years;
- The third will return a list of users that are older then 20 and younger then 30 years (between 20 and 30 exclusive);
- The third will return a list of users that have 20 years or more and 30 years or less (between 20 an 30 inclusive);
- The fourth will return a list of users that have 20 or 30 years;

To get the filters parameters, use:
```php
$filters = $Request->filter();
/**
GET /user?gender=female
$filters = array (
    'gender' => array (
        '=' => 'female',
    )
);

GET /user?age=+20&gender=female
$filters = array (
    'age' => array ('>' => array ('20')),
    'female' => array ('=' => array (0 => 'female')),
);

GET /users?age=20;30
$filters = array (
    'age' => array (
        '=' => array('20', '30'),
    )
);
```

> Note that the filters are parameters on the querystring. Some words are reserved by other methods, like *page*, *offset* and *sort*.

For more information, explore the source :)

> Written with [StackEdit](https://stackedit.io/).