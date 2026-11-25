VERDICT: CHANGES

composer test passes (phpstan, pint, 1100 pest tests). One minor finding.

- minor: SearchUsersAction and SearchOrganizationsAction duplicate the LIKE helpers (ESCAPE const, startsWith() anonymous ConditionExpression, escape()). Fix: extract one shared support class (for example Support\LikeEscape) used by both.
  Files: src/Domains/User/Actions/SearchUsersAction.php, src/Domains/Organization/Actions/SearchOrganizationsAction.php
- nit: the cache-forget listener in src/RosterServiceProvider.php keeps the ListedActionEvent/ShownActionEvent suffix checks next to the new ReadActionEvent check; the suffix checks are likely redundant.
