# Selecting data to be retrieved
It's a good practice to only return what you need. More so, when working with JSON documents as the embedded data
can become quite large quickly.

Aranguent allows you to select fields just like vanilla Laravel and in addition you can make a selection of embedded
data. 

## Regular data selection
Like normal, you can just select one or more fields:

```php
DB::table('characters')->select('name')->get();
```

### Multiple fields:
```php
DB::table('characters')->select(['name', 'surname', 'age'])->get();
```

## Nested data
You can select nested data by supplying a path to it
```php
DB::table('houses')->select([
        'name',
        'en.description',
])->get();
```

### Multiple nested fields
When you include multiple nested fields that partially overlap the results will be combined in one data structure 
```php
DB::table('houses')->select([
        'name',
        'en.description',
        'en.words',
])->get();
```

will return a data structure like:

```json
[
  {
    "name": "Targaryen",
    "en": {
      "description": "House Targaryen of Dragonstone is...Velaryon, and House Martell.",
      "words": "Fire and Blood"
    }
  }
]
```

## Aliasing fields
You can alias any field in one of two interchangeable ways.

### SQL style aliasing
This is the regular method as is commonly used in Laravel. It is constructed as `{field} as {alias}` 
```php
DB::table('houses')->select([
    'name as first_name',
    'en as english',
])->get();
```

```php
DB::table('houses')->select([
        'name',
        'en.description as description',
])->get();
```

### Key aliasing
You may also alias a field by supplying a string-key to the specific field in the array of fields.
```php
DB::table('houses')->select([
        'name',
        'description' => 'en.description',
])->get();
```