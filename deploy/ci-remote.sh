#!/usr/bin/env bash
# Installed outside the web root; the dedicated SSH key can only call this receiver.
set -euo pipefail
export PATH=/usr/local/bin:/usr/bin:/bin
umask 0027
base=/var/www/demo-repair-app
current=$base/current
ops=/home/muzib/deploy-demo-repair-app/cicd
php=/usr/bin/php8.3
domain=https://demo-repair-app.scannara.asia
[[ $EUID != 0 ]] || { echo 'Root deployment is forbidden' >&2; exit 1; }
[[ ${SSH_ORIGINAL_COMMAND:-} =~ ^deploy\ ([0-9a-f]{40})\ ([0-9a-f]{64})$ ]] || { echo 'Only a pinned release deployment is allowed' >&2; exit 1; }
revision=${BASH_REMATCH[1]} digest=${BASH_REMATCH[2]}
exec 9>"$ops/deploy.lock"
flock -w 60 9 || { echo 'Another deployment holds the server lock' >&2; exit 1; }
[[ -d $current && ! -L $current && -f $current/.env && ! -L $current/.env ]] || exit 1
[[ ! -e $base/.deploy-pending && ! -e $current/storage/framework/down ]] || { echo 'Existing maintenance requires operator review' >&2; exit 1; }
work=$(mktemp -d "$ops/.incoming.XXXXXX")
updating=0
finish() {
  result=$?
  trap - EXIT
  if (( result != 0 && updating )); then
    : > "$base/.deploy-pending"
    echo 'Deployment failed; demo held in maintenance. Inspect private cicd/last-deploy.log.' >&2
  fi
  rm -rf -- "$work"
  exit "$result"
}
trap finish EXIT
trap 'exit 143' TERM HUP
trap 'exit 130' INT
head -c 268435457 > "$work/release.tar.gz"
[[ $(wc -c < "$work/release.tar.gz") -le 268435456 ]] || exit 1
read -r actual _ < <(sha256sum "$work/release.tar.gz")
[[ $actual == "$digest" ]] || { echo 'Archive digest mismatch' >&2; exit 1; }
mkdir "$work/release"
python3 "$ops/ci-archive.py" "$work/release.tar.gz" "$work/release" "$revision"
"$php" /usr/local/bin/composer --working-dir="$work/release" check-platform-reqs --no-dev > "$ops/last-deploy.log" 2>&1
cd "$current"
private_before=$(sha256sum .env)
updating=1
: > "$base/.deploy-pending"
run() { "$php" artisan "$@" --no-interaction >> "$ops/last-deploy.log" 2>&1; }
run down --retry=60
run optimize:clear --except=cache
# Existing SGID directories pass www-data to new files. Never chmod/chgrp the live tree.
rsync -rlt --checksum --delete-delay --omit-dir-times --no-perms --no-owner --no-group \
  --exclude='/.env' --exclude='/.git' --exclude='/storage' \
  --exclude='/public/storage' --exclude='/bootstrap/cache' --exclude='/.release-revision' \
  "$work/release/" "$current/" >> "$ops/last-deploy.log" 2>&1
run migrate --force
run config:cache
run route:cache
run view:cache
run migrate:status
[[ $(sha256sum .env) == "$private_before" ]] || { echo 'Private environment changed' >&2; exit 1; }
run up
rm -- "$base/.deploy-pending"
# FPM checks changed PHP files with its existing two-second opcode-cache interval.
sleep 3
for path in up login; do
  status=$(curl --fail --silent --show-error --retry 4 --retry-delay 2 --retry-all-errors \
    --output /dev/null --write-out '%{http_code}' "$domain/$path")
  [[ $status == 200 ]] || exit 1
done
printf '%s\n' "$revision" > .release-revision
printf '{"revision":"%s","archive_sha256":"%s","status":"pass","utc":"%s"}\n' \
  "$revision" "$digest" "$(date -u +%FT%TZ)" > "$ops/last-success.json"
updating=0
printf 'DEPLOYED %s; HTTPS health/login PASS\n' "$revision"
