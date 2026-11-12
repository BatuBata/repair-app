#!/usr/bin/env bash
set -euo pipefail
die() { printf '%s\n' "$*" >&2; exit 1; }
[[ $# == 4 ]] || die 'Run from prepared release root: deploy.sh /absolute/php approved_commit /absolute/pre-migration.sql.gz APPLY:approved_commit'
php=$1 revision=$2 backup=$3 confirmation=$4
[[ $php == /* && -x $php && $revision =~ ^[0-9a-f]{40}$ && $confirmation == "APPLY:$revision" ]] || die 'Explicit PHP, full approved commit and confirmation required'
[[ $(git rev-parse HEAD) == "$revision" && -z $(git status --porcelain) ]] || die 'Checkout must exactly match the approved clean revision'
[[ -f artisan && -f vendor/autoload.php && -f public/build/manifest.json && -f .env && -f storage/framework/down ]] || die 'Prepared dependencies, built assets, private env and maintenance mode required'
[[ $backup == /* && -s $backup && ! -L $backup ]] || die 'Explicit verified pre-migration backup required'
gzip -t -- "$backup" || die 'Backup is damaged'
"$php" artisan migrate --force --no-interaction
"$php" artisan config:cache
"$php" artisan route:cache
"$php" artisan view:cache
printf 'Migration/caches complete for %s; maintenance stays ON until operator verification\n' "$revision"
