# ArangoSearch

Aranguent extends the query builder with the ability to search in ArangoSearch views.

## Creating a view
You can create a view by setting up a corresponding [migration](migrations.md).

## The QueryBuilder search() method   
Search() is an Aranguent addition to the regular QueryBuilder. It accepts predicates and option parameters.

```php 
    search(mixed $predicates, array $options = null): Builder
```

### Predicates
$predicates consists of a single conditional array `['userViewDoc.age', '==', 20]`
or an array of multiple predicates:
```php
[
    ['userViewDoc.age', '<=', 20],
    ['userViewDoc.age', '>=', 30, 'OR'],
]
```
Similar to how you are  normally used for conditional statements in Laravel.

[Alternatively you can use a closure to unlock the full power of ArangoSearch](#advanced-searches).

None-array predicates will be wrapped in an array.

### Using columns/attributes
**_Note:_** You need to prefix any 'column' with the view alias. 
The alias consists of the camel-cased view name postfixed with 'Doc'. The alias for `page_view` is `pageViewDoc`. 

### Options
Check out the available options from 
[ArangoDB's documentation](https://www.arangodb.com/docs/stable/aql/operations-search.html#search-options)

### Example:
```php
    \DB::table('user_view')
        ->search(['userViewDoc.age', '==', 20])
        ->get();
```
This will return all indexed user documents with an age attribute set to 20.

##<a name="advanced-searches"></a>Advanced searches using FluentAQL
The search method allows you to use a closure with it's FluentAQL Query Builder.
With it, you can use ArangoDB's powerful functions such as PHRASE() or NGRAM_MATCH().

The following example will search the house_view for any relevant results and sort it by relevance.
```php 
    $searchText = 'house Stark war with house Lannister';
    $locale = 'en';
    $query = \DB::table('house_view');
    
    $results = $query->search(
        function ($aqb) use ($locale, $searchText) {
            return [
                [$aqb->phrase('houseViewDoc.' . $locale . '.name', $searchText, 'text_' . $locale)],
                [$aqb->phrase('houseViewDoc.' . $locale . '.description', $searchText, 'text_' . $locale), null, null, 'OR']
            ];
        }
    )
    ->orderBy($query->aqb->bm25('houseViewDoc'), 'desc')
    ->get();
```

## Model instance results
You may search directly from eloquent but for this to work you need to reset the table that is searched.
```php
        $results = House::from('house_view')->search(
            function ($aqb) {
                return $aqb->analyzer('houseViewDoc.en.description', '==', 'war', 'text_en');
            }
        )
        ->paginate();
```

### Views with multiple linked collections
A view may consist of multiple linked collections. Thus, searches may result in documents from any of those.
If you search from a model through such a view you want to restrict the collections used in the search by adding the
collections option.

```php
        $results = House::from('house_view')->search(
            function ($aqb) {
                return $aqb->analyzer('houseViewDoc.en.description', '==', 'war', 'text_en');
            },
            [
                'collections' => [
                    House::getTable();
                ]
            ]
        )
        ->paginate();
```

