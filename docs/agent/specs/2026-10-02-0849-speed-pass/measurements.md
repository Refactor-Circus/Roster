# Query counts: before (last commit, f4797c1) -> after

Measured with the query log (DB::enableQueryLog), one fresh request's worth of per-request caches per measurement, at 10 and 40 rows.

```
api transfers list n=10                 6 -> 5
api transfers list n=40                 6 -> 5
api users list n=10                     67 -> 7
api users list n=40                     247 -> 7
atrium org members tab n=10             33 -> 13
atrium org members tab n=40             33 -> 13
atrium user page                        43 -> 28
atrium user page                        43 -> 28
export members n=10                     127 -> 50
export members n=40                     367 -> 50
gate 20x can() no scope                 125 -> 9
gate 20x can() no scope                 125 -> 9
import members apply n=10               539 -> 453
import members apply n=40               2009 -> 1653
import members preview n=10             61 -> 46
import members preview n=40             151 -> 76
org sync bulk create n=10               192 -> 153
org sync bulk create n=40               762 -> 603
org sync bulk unchanged n=10            82 -> 53
org sync bulk unchanged n=40            361 -> 203
scim users page n=10                    45 -> 9
scim users page n=40                    165 -> 9
```
