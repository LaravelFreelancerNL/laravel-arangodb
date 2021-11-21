# Compatibility list
This page shows a list of tried and tested compatible abilities and methods. Unmentioned methods & abilities 
might work but your mileage may vary. 

**_Note_**: This list is limited to database interactions. It lists 'create' but not 'make' etc.

- [Query Builder](#query-builder)
- [Eloquent](#eloquent)
- [Database Connection](#database-connection)
- [Known Incompatibilities](#known-incompatibilities)


##<a name="query-builder"></a>Query Builder
- dd
- raw (use AQL)
- toSql

### Selections
- addSelect
- crossJoin
- distinct
- doesntExist
- exists
- find
- get
- groupBy
- inRandomOrder
- join
- leftJoin
- limit
- offset
- orderBy
- orderByRaw (use AQL)
- pluck
- select
- selectRaw (use AQL)
- skip
- table
- take

### filters
- having
- havingBetween
- orWhere
- orWhereColumn
- orWhereIn
- orWhereNotIn
- orWhereNotNull
- orWhereNull
- where
- whereBetween
- whereColumn
- whereDate
- whereDay
- whereExists
- whereIn
- whereJsonContains
- whereJsonLength
- whereMonth
- whereNotBetween
- whereNotIn
- whereNotNull 
- whereNull
- whereRaw (use AQL)
- whereTime
- whereYear

### Statements
- delete
- insert
- insertGetId
- insertOrIgnore
- truncate
- update
- upsert

### Aggregates
- average
- avg
- count
- max
- min
- sum


##<a name="eloquent"></a>Eloquent
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


##<a name="testing"></a>Testing
- assertDatabaseCount
- assertDeleted
- assertDatabaseHas
- assertDatabaseMissing
- assertModelExists
- assertModelMissing
- assertNotSoftDeleted
- assertSoftDeleted
- castAsJson (dummy method)


##<a name="known-incompatibilities"></a>Known incompatibilities
Not all features can be made compatible. Known issues are listed below:

- unions
- rightJoin

## id vs _id
All ArangoDB documents have a default indexed identifier called '_id' consisting of the collection (table) name and
a unique string _key. This looks something like: users/12345.

At the moment _id and id aren't transcribed back and forth. So relations and queries that rely on 'id' will fail if they 
don't retrieve a models keyName. Relevant parts of packages must be overridden.

**Note**: this is high on the list to solve generically. So this behaviour is likely to change in the future.

### Transactions
[At the beginning of a transaction you must declare collections that are used in (write) statements.](transactions.md)

### Locking: sharedLock/lockForUpdate
These methods don't work as ArangoDB requires you to declare the locking mechanics at the start of a transaction. 

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