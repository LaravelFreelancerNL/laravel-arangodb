# Query functions

Aranguent supports the following functions of Laravel's query API.

(unmentioned functions are untested, your mileage may vary)

## Select clauses
- select
- selectSub
- distinct
- addSelect
- get

## Aggregates
- count
- max
- min
- avg
- sum

## Return set clauses
- orderBy
- inRandomOrder
- groupBy
- having
- limit
- offset

## Insert statements
- insert
- insertOrIgnore

## Update statements
- update
- updateOrInsert
- upsert

## Delete statements
- delete
- truncate

## Joins
- join
- leftJoin
- crossJoin

## Where clauses
- where / orWhere
- whereJsonContains / whereJsonLength / JSON where syntax
- whereBetween / orWhereBetween / whereNotBetween / orWhereNotBetween
- whereIn / whereNotIn / orWhereIn / orWhereNotIn
- whereNull / whereNotNull / orWhereNull / orWhereNotNull
- whereDate / whereMonth / whereDay / whereYear / whereTime
- whereColumn / orWhereColumn / whereBetweenColumns
- whereExists / whereNotExists

## Debugging
- dd
- dump

## Raw expressions (!)
ArangoDB does not support SQL, so any raw expressions must be fed with AQL instead.
This means that any package with raw SQL code is incompatible, at least partially.