# Tasks: Drop the paratest conflict pin and require Pest ^5.3.4

## Group 1: Raise Pest, drop the conflict
depends on: none
files: composer.json
criteria: 1, 2, 3, 4, 5, 6

- [x] 1.1 Write the checks first: `composer.json` has a `conflict` key and `pestphp/pest` is not `^5.3.4` (`composer validate` plus a read of `composer.json`); both fail today
- [x] 1.2 Delete the `conflict` block and set `require-dev` `pestphp/pest` to `^5.3.4`; change nothing else in `composer.json`
- [ ] 1.3 Run `composer update pestphp/pest brianium/paratest --with-dependencies`; `composer show brianium/paratest` reports 7.27.4 or newer, `composer show pestphp/pest` 5.3.4 or newer; the local `composer.lock` is not staged (gitignored)
- [ ] 1.4 Run `composer test` (analyse, lint:check, test:types, test:unit); all green
- [ ] 1.5 Confirm `git diff --name-only` lists only `composer.json` besides the spec folder
