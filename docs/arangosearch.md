# ArangoSearch

Aranguent extends the query builder with the ability to search in ArangoSearch views.

## Creating a view
You can create a view by setting up a corresponding [migration](migrations.md).

## The QueryBuilder searchView() method   
Search() is an Aranguent addition to the regular QueryBuilder. It accepts one or more fields, the search text
and an optional ArangoSearch analyzer.

```php 
    searchView(
        array|Expression|string $fields,
        string $searchText,
        string $analyzer = null
    ): IlluminateQueryBuilder
```
Note that the search text is split in words and each word is stemmed, matched in lower case and accented characters
are reverted to their base character ('á' becomes 'a').


### Example:
```php
    $houses = \DB::table('house_view')
        ->searchView('en.description', 'dragon lannister')
        ->get();
```
This will return all indexed house documents with any of the words 'dragon' and 'lannister' in the english description.

<a name="analyzer"></a>
## Analyzer
The analyzer defaults to ArangoSearch text analyzer for the current locale; if that locale is supported.
Otherwise, it defaults to 'text_en'.

<a name="sorting"></a>
## Sorting
ArangoSearch results can be sorted by the best match or by occurrence of search tokens.
This is done by using either `orderByBestMatching` or `orderByFrequency`.
Both take the description as an argument (default: `DESC`).

### Example:
```php
    $houses = \DB::table('house_view')
        ->searchView('en.description', 'dragon lannister')
        ->orderByBestMatching()
        ->get();
```

```php
    $houses = \DB::table('house_view')
        ->searchView('en.description', 'dragon lannister')
        ->orderByFrequency('asc')
        ->get();
```
