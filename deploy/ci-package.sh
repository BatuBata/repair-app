#!/usr/bin/env bash
set -euo pipefail
[[ $# == 2 && $1 =~ ^[0-9a-f]{40}$ && $2 == /* ]] || { echo 'Full commit and absolute output path required' >&2; exit 1; }
revision=$1 output=$2
[[ $(git rev-parse HEAD) == "$revision" ]] || { echo 'Checkout revision mismatch' >&2; exit 1; }
git diff --quiet HEAD -- app bootstrap config database resources routes public artisan composer.json composer.lock
[[ -f vendor/autoload.php && -f public/build/manifest.json ]] || { echo 'Dependencies and assets required' >&2; exit 1; }
stamp=$(mktemp -d)
trap 'rm -rf -- "$stamp"' EXIT
printf '%s\n' "$revision" > "$stamp/.release-revision"
tar --dereference --hard-dereference --mode='u+rwX,g+rX,o-rwx' \
  --exclude='bootstrap/cache' --exclude='public/storage' \
  -czf "$output" app bootstrap config database resources routes public vendor \
  artisan composer.json composer.lock -C "$stamp" .release-revision
