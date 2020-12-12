# Query functions

Aranguent supports the following functions of Laravel's query API.

(unmentioned functions may already work out of the box, but are untested)

## Select clauses
1) select
2) distinct
3) addSelect
4) get

## Aggregates
1) count
2) max
3) min
4) avg
5) sum

## Return set clauses
1) orderBy
2) inRandomOrder
3) groupBy
4) having
5) limit
6) offset

## Insert statements
1) insert
2) insertOrIgnore

## Update statements
1) update
2) updateOrInsert
3) upsert

## Delete statements
1) delete
2) truncate

## Joins
1) join
2) leftJoin
3) crossJoin

## Where clauses
1) where / orWhere
2) whereJsonContains / whereJsonLength / JSON where syntax
3) whereBetween / orWhereBetween / whereNotBetween / orWhereNotBetween
4) whereIn / whereNotIn / orWhereIn / orWhereNotIn
5) whereNull / whereNotNull / orWhereNull / orWhereNotNull
6) whereDate / whereMonth / whereDay / whereYear / whereTime
7) whereColumn / orWhereColumn / whereBetweenColumns

## Debugging
1) dd
2) dump

## Raw expressions (!)
ArangoDB does not support SQL, so any raw expressions must be fed with AQL instead.
This means that any package with raw SQL code is incompatible, at least partially.