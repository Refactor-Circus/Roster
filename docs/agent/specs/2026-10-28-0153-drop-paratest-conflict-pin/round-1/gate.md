VERDICT: PASS

Gate: composer test (phpstan, pint, pest 1239/1239, 4349 assertions), lint:check, ringside check all passed.

Criteria:
1. No `conflict` key: diff removes the block.
2. pest `^5.3.4`: diff in composer.json.
3. composer show brianium/paratest: v7.27.4 (>=7.27.4); lock untracked.
4. composer validate: valid.
5. composer test exit 0, 1239 tests (pre-change count not independently available).
6. Diff touches only composer.json: conflict removal and pest constraint.
