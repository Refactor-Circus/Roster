# Tasks: Drop the paratest conflict pin

## Group 1: Drop the conflict pin and require Pest ^5.3.4
depends on: none
files: composer.json
criteria: 1, 2, 3, 4, 5, 6

- [x] 1.1 Record the baseline: run `composer test:unit` and note the parallel test count (the "before" for criterion 5); confirm `grep -c '"conflict"' composer.json` returns 1
- [x] 1.2 Edit `composer.json`: delete the `conflict` block (fix the trailing comma on the preceding entry) and change `pestphp/pest` from `^5.3.1` to `^5.3.4`
- [x] 1.3 Run `composer update pestphp/pest brianium/paratest --with-all-dependencies`; check `composer show brianium/paratest` is `>=7.27.4` and `composer why brianium/paratest` shows `pestphp/pest`; leave `composer.lock` uncommitted
- [x] 1.4 Run `composer validate`; `grep -c '"conflict"' composer.json` returns 0
- [x] 1.5 Run `composer test`; exits 0 with a parallel run and the same test count as the baseline; on any failure stop and report (spec failure path), no workarounds
- [x] 1.6 Check `git diff --stat` touches only `composer.json`, and the diff is just the `conflict` removal and the pest constraint change
