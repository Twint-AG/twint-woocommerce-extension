# Local CI (`infra/ci/`)

Run the GitLab CI jobs on your machine, in Docker, using the **same image and
`spc` as CI** (`shivammathur/node:jammy`). Use it to verify a branch before
pushing — so a red pipeline means a real problem, not a flaky shared runner.

## Usage

```bash
infra/ci/local-ci.sh tests            # `tests` job, all PHP 8.1–8.5 in parallel
infra/ci/local-ci.sh tests 8.3        # just one (or a few) versions
infra/ci/local-ci.sh archive          # `build-archive` job (PHP 8.5) → zip
infra/ci/local-ci.sh all              # tests then archive
```

Per-job logs land in `infra/ci/logs/`; the archive zip in `infra/ci/artifacts/`
(both git-ignored). The command prints a `PASS`/`FAIL` line per job.

## What it reproduces (from `.gitlab-ci.yml`)

- **tests** (matrix PHP 8.1–8.5): `spc` sets up PHP + the CI extension set →
  `cp composerXX.lock composer.lock` → `composer install` → `ecs`, `rector
  --dry-run`, `phpstan` → `npm install` → `prettier --check`.
- **build-archive** (PHP 8.5): `spc` → `bin/archive.sh` → the release zip.

## How it stays honest / isolated

- Each job **copies the repo into a container-local workdir** (excluding
  `.git/vendor/node_modules/dist/build`), so parallel jobs never clobber each
  other's `composer.lock`/`vendor` and your working tree is untouched.
- Private-SDK auth uses `GITLAB_TOKEN` from `infra/local/.env` (maps to CI's
  `GITLAB_ACCESS_TOKEN`).

## Notes

- Running all 5 versions in parallel is resource-heavy (5× `composer install` +
  `phpstan`). Pass specific versions if your machine struggles, e.g.
  `local-ci.sh tests 8.3 8.4`.
- This mirrors the CI script steps; if `.gitlab-ci.yml` changes, update
  `local-ci.sh` to match (the extension lists and step order live in both).
