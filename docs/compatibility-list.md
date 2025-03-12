# Compatibility list
This page shows a list of tried and tested compatible features as well as incompatibilities. 

Features annotated with a question mark are untested. Known incompatibilities are explicitly mentioned.
Untested features might work but your mileage may vary. 

**_Note_**: This list is limited to database interactions. For example, it lists 'create' but not 'make' 
as the latter Eloquent function is in memory only.

- [Migrations](#migrations)
- [Query Builder](#query-builder)
- [Eloquent](#eloquent)
- [Artisan commands](#artisan)
- [Testing](#testing)
- [Database Connection](#database-connection)
- [Known Incompatibilities](#known-incompatibilities)

## <a name="migrations"></a>Migrations

### Columns
This driver doesn't support schema validation at this time. Column methods within a blueprint are silently
ignored. Any index modifiers on a column are used to create the index.

### Indexes
index / primary / unique / spatialIndex /
invertedIndex / ttlIndex
dropPrimary / dropUnique / dropIndex / dropSpatialIndex /
dropInvertedIndex / dropTtlIndex /
renameIndex

#### Unsupported index methods
fullText / dropFullText

Fulltext indexes are deprecated per ArangoDB 3.10; you can use [ArangoSearch](arangosearch.md) instead.

## <a name="query-builder"></a>Query Builder

### Data retrieval
find / first / get / value / pluck / sole / 
chunk / chunkById / orderedChunkById / lazy / lazyById / lazyByIdDesc / 
paginate / simplePaginate / cursorPaginate

### Aggregates
average / avg / count / doesntExist / exists / max / min / sum

### Selections
addSelect / distinct / select / selectRaw (use AQL) / selectSub?

### Raw Expressions
**Note** that all raw expressions need to use AQL instead of SQL, so if a third-party
package uses raw SQL you'll need to override it.

selectRaw / raw / whereRaw / orWhereRaw / havingRaw / orHavingRaw

#### Unsupported raw Expressions
groupByRaw

### Unions
union / unionAll

### Expressions
Expression / raw

### Joins
crossJoin / join / joinSub / lateralJoin / leftJoin / leftJoinSub

#### Unsupported join clauses
rightJoin / rightJoinSub / rightJoinWhere / joinWhere? / leftJoinWhere?

### Where clauses
where / orWhere / whereNot / orWhereNot / whereColumn / whereExists
whereBetween / whereNotBetween / whereBetweenColumns / whereNotBetweenColumns /
whereJsonContains / whereJsonLength /
whereIn / whereNotIn /
whereLike / orWhereLike / whereNotLike / orWhereNotLike /
whereNone / orWhereNone / whereNot / orWhereNot / 
whereNull / whereNotNull /
whereDate / whereMonth / whereDay / whereYear / whereTime /
whereRaw (use AQL) /
whereAll / orWhereAll / whereAny / orWhereAny

nested wheres / subquery wheres on both operands 

#### Incompatible where clauses
whereFullText (use searchView instead)

### Ordering
orderBy / latest / oldest / inRandomOrder / reorder /
orderByRaw (use AQL)

### Grouping
groupBy / groupByRaw /
having / havingBetween / havingNull / havingNotNull / 
havingRaw (use AQL) / Nested havings

### Limit & offset
limit / offset / take / skip

### Conditional clauses
when

### Insert statements
insert / insertOrIgnore / insertUsing / insertOrIgnoreUsing / insertGetId

### Update statements
update / updateOrInsert / upsert /
increment / incrementEach / decrement / decrementEach /
update with join

### Delete statements
delete / truncate

### Debugging
dd  / dump / toSql / ddRawSql? / dumpRawSql / toRawSql?

## <a name="eloquent"></a>Eloquent
The methods listed below are **specific** to Eloquent.
_Functions handed off to the **Query Builder** are specified 
in the chapter above._

### Model CRUD
all / first / firstWhere / firstOr / firstOrFail /
firstOrCreate / firstOrNew / 
find / findOr? / fresh / refresh / 
create / createOrFirst / fill? / save / update / updateOrCreate /
upsert / replicate? / delete / destroy / truncate / softDeletes? / 
trashed? / restore? / withTrashed? / forceDelete /
isDirty? / isClean? / wasChanged? / getOriginal? /
pruning? / query scopes? / saveQuietly? / deleteQuietly? /
forceDeleteQuietly? / restoreQuietly?

#### Model comparison
is? / isNot?

### Relationships
#### hasOne (One To One)
hasOne / create / save /
doesntHave / orDoestntHave / has / orHas  /
whereDoesntHave / whereRelation / 
with / WithCount / withExists

#### BelongsTo (One To One / One To Many)
belongsTo / associate 
dissociate / load
orWhereDoesntHave / whereRelation / orWhereRelation
whereDoesntHaveRelation / orWhereDoesntHaveRelation
whereHas / orWhereHas

#### One To Many
#### Many To Many
#### One To One (Polymorphic)
#### One To Many (Polymorphic)
#### Many To Many (Polymorphic)


enforceMorphMap? / getMorphClass? / getMorphedModel? / resolveRelationUsing?


#### Querying Relationship Absence
whereMorphDoesntHaveRelation / orWhereMorphDoesntHaveRelation

#### Querying Morph To Relationships
whereHasMorph / orWhereHasMorph / whereDoesntHaveMorph / orWhereDoesntHaveMorph / whereMorphedTo? / whereNotMorphedTo?

#### Inserting and updating related models
save / saveMany / refresh / push / pushQuietly / 
create / createMany / createQuietly / createManyQuietly /
associate / dissociate / attach / detach / 
sync / syncWithPivotValues / syncWithoutDetaching / toggle /
updateExistingPivot / 

## <a name="artisan"></a> Artisan commands
The following database-related artisan commands are supported:
db / db:monitor / db:show / db:table / db:wipe /
make:migration / make:model / migrate:install / migrate /
migrate:fresh / migrate:refresh / migrate:reset / migrate:rollback / 
migrate:status / convert:migrations 

The following database-related artisan commands are NOT support at this time:

schema:dump

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

### Cross database queries
ArangoDB does not support cross database queries.

### Foreign keys
ArangoDB doesn't support foreign keys. So if a package depends on that for cascading updates or deletes you'll
have to extend those parts of the package.

You can set up an event listener in the Model's boot method or use an
observer besides that you want to override any delete action that is performed directly on the database.

Note that you'll want to delete child models before deleting the model itself. This also lets you retrieve
all child models in case of mass deletion. As you'll be dealing with multiple delete queries it is a good idea to
wrap it in a transaction.

### Locking: sharedLock / lockForUpdate
These methods don't work as ArangoDB requires you to declare the locking mechanics at the start of a transaction.
**_They fail silently and just run the query. (???)_**

### Raw SQL
Any raw SQL needs to be replaced by raw AQL.

### Database replication: separate read and write connections
ArangoDB offers a cluster setup for high availability instead of replication and as such
doesn't support the combination of a separate read and write connection.  

### Transactions
[At the beginning of a transaction you must declare collections that are used in (write) statements.](transactions.md)

