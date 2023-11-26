# Compatibility list
This page shows a list of tried and tested compatible features as well as incompatibilities. 

Features annotated with a question mark are untested. Known incompatibilities are explicitly mentioned.
Untested features might work but your mileage may vary. 

**_Note_**: This list is limited to database interactions. For example, it lists 'create' but not 'make'.

- [Query Builder](#query-builder)
- [Eloquent](#eloquent)
- [Testing](#testing)
- [Database Connection](#database-connection)
- [Known Incompatibilities](#known-incompatibilities)

## <a name="query-builder"></a>Query Builder

### Data retrieval
find / first / get / value / pluck / sole / 
chunk / chunkById / orderedChunkById / lazy / lazyById / lazyByIdDesc / 
paginate / simplePaginate / cursorPaginate

### Aggregates
average / avg / count / doesntExist / exists / max / min / sum

### Selections
addSelect / distinct / select / selectRaw (use AQL) / selectSub?

### Unions
union / unionAll /  

#### Unsupported union clauses
Union orders / Union aggregates / Union groupBy

### Expressions
Expression / raw

### Joins
crossJoin / join / joinSub? / leftJoin / leftJoinSub?

#### Unsupported join clauses
rightJoin / rightJoinSub

### Where clauses
where / orWhere / whereNot / orWhereNot / whereColumn / whereExists
whereBetween / whereNotBetween / whereBetweenColumns / whereNotBetweenColumns /
whereJsonContains / whereJsonLength /
whereIn / whereNotIn / whereNull / whereNotNull /
whereDate / whereMonth / whereDay / whereYear / whereTime /
whereRaw (use AQL)

nested wheres / subquery wheres on both operands 

#### Incompatible where clauses
whereFullText (use searchView instead)

### Ordering
orderBy / latest / oldest / inRandomOrder / reorder /
orderByRaw (use AQL)

### Grouping
groupBy /
having / havingBetween / havingNull / havingNotNull / 
havingRaw (use AQL) /
Nested havings

#### Incompatible grouping
groupByRaw

### Limit & offset
limit / offset / take / skip

### Conditional clauses
when

### Insert statements
insert / insertOrIgnore / insertUsing? / insertGetId

### Update statements
update / updateOrInsert / upsert /
increment / incrementEach / decrement / decrementEach /
update with join

### Delete statements
delete / truncate

### Debugging
dd / dump / toSql

#### Incompatible debugging functions
dumpRawSql / ddRawSql

## <a name="eloquent"></a>Eloquent
The methods listed below are specific to Eloquent.
Note that a lot of Eloquent's featured are leveraging the query builder. Those methods are listed in the corresponding
chapter below.

### Model CRUD
- all
- create
- delete
- destroy
- find
- first
- firstOr
- firstOrFail
- firstOrCreate
- firstOrNew
- firstWhere
- fresh
- paginate
- save
- updateOrCreate

### Relationships
- One To One
- One To Many
- Many To Many
- One To One (Polymorphic)
- One To Many (Polymorphic)
- Many To Many (Polymorphic)

Methods:
- belongsTo
- belongsToMany
- doesntHave
- enforceMorphMap
- has
- hasMany
- hasOne
- morphedByMany
- morphMany
- morphOne
- morphTo
- with
- withCount


## <a name="testing"></a> Testing

### Traits
DatabaseTransactions / RefreshDatabase?

### Assertions
assertDatabaseCount /
assertDatabaseHas / assertDatabaseMissing /
assertModelExists / assertModelMissing /
assertDeleted  / assertSoftDeleted / assertNotSoftDeleted /
castAsJson (dummy method)

## <a name="database-connection"></a> Database connection
escape

## <a name="known-incompatibilities"></a> Known incompatibilities
Not all features can be made compatible. Known issues are listed below:

### Transactions
[At the beginning of a transaction you must declare collections that are used in (write) statements.](transactions.md)

### Locking: sharedLock / lockForUpdate
These methods don't work as ArangoDB requires you to declare the locking mechanics at the start of a transaction. 
They fail silently and just run the query. (???)

### Separate read and write connections
Aranguent currently doesn't support the combination of a separate read and write connection  

### Foreign keys
ArangoDB doesn't support foreign keys. So if a package depends on that for cascading updates or deletes you'll 
have to extend those parts of the package. 

You can set up an event listener in the Model's boot method or use an
observer besides that you want to override any delete action that is performed directly on the database.

Note that you'll want to delete child models before deleting the model itself. This also lets you retrieve
all child models in case of mass deletion. As you'll be dealing with multiple delete queries it is a good idea to
wrap it in a transaction.

### Raw SQL
Any raw SQL needs to be replaced by AQL.