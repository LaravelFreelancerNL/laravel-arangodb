# Query functions
Aranguent supports most query functions available in vanilla Laravel. 
[You'll find  a full list of compatible functions here.](compatibility-list.md)
In addition the following functions have been added:

## searchView && rawSearchView
These functions allow you to search ArangoViews.
[They are explained here in more detail.](arangosearch.md)

## fromOptions($options)
fromOptions allows you to set options for the initial 'from' statement of the query builder.
[In AQL these are the 'FOR' options as described here.](https://docs.arangodb.com/stable/aql/high-level-operations/for/#options)

This enables you to indexHint [inverted indexes](https://docs.arangodb.com/stable/index-and-search/indexing/working-with-indexes/inverted-indexes/)
which can then be used by filters such as where and having functions.

### Example:
```php
\DB::table('houses')
        ->fromOptions([
            'indexHint' => 'InvIdx',
            'forceIndexHint' => true
        ])
        ->where('en.description', 'LIKE', '%Westeros%')
        ->get();
```