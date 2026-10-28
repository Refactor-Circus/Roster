# References

## Keystone PR #8

- Location: `../Keystone` (Refactor-Circus/Keystone, PR #8)
- Relevance: the same pin removal, done the same way.
- Pattern to borrow: remove the `conflict` block, bump `pestphp/pest` to `^5.3.4`, update the lock.

## Roster `composer.json`

- Location: `composer.json:33` (`pestphp/pest`), `composer.json:185-187` (`conflict`)
- Relevance: the two places this change touches.
