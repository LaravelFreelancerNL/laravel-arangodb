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

### Transactions
[At the beginning of a transaction you must declare the write/exclusive collections.](transactions.md)

### Separate read and write connections
Aranguent currently doesn't support the combination of a separate read and write connection  