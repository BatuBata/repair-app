#!/usr/bin/env bash
set -euo pipefail
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
umask 077
qa=/home/muzib/deploy-demo-repair-app/qa-mariadb
test "$(id -un)" = muzib
test -d "$qa" && test ! -L "$qa"
test "$(readlink -f "$qa")" = "$qa"
test "$(stat -c %a "$qa")" = 700
test ! -e "$qa/data"
systemctl show -p MainPID --value mariadb >"$qa/service-pid-before"
mkdir -m 700 "$qa/data" "$qa/cache" "$qa/env" "$qa/storage"
mkdir -p "$qa/storage/framework/cache/data" "$qa/storage/framework/sessions" "$qa/storage/framework/views" "$qa/storage/logs"
/usr/bin/mariadb-install-db --no-defaults --datadir="$qa/data" --auth-root-authentication-method=normal --skip-test-db >"$qa/initialize.log" 2>&1
nohup /usr/sbin/mariadbd --no-defaults --datadir="$qa/data" --socket="$qa/mariadb.sock" --pid-file="$qa/mariadb.pid" --log-error="$qa/server-error.log" --skip-networking --skip-log-bin --innodb-buffer-pool-size=64M --max-connections=16 --performance-schema=OFF >"$qa/server-stdout.log" 2>&1 < /dev/null &
printf '%s\n' "$!" >"$qa/launch-pid"
for n in {1..60}; do
    if test -S "$qa/mariadb.sock"; then
        printf '%s\n' 'Private no-TCP MariaDB socket created; identity must pass before SQL writes.'
        exit 0
    fi
    sleep 0.25
done
printf '%s\n' 'Private instance startup failed; inspect private logs. No SQL writes attempted.' >&2
exit 1
