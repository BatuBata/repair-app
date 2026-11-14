#!/usr/bin/env bash
# Bounded T024 A2: only demo permissions and its existing renewal hook; no database actions.
set -euo pipefail
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
umask 027
die() { printf '%s\n' "$*" >&2; exit 1; }
[[ $# == 1 && $1 == FINALIZE:demo-repair-app ]] || die 'Usage: finalize-host.sh FINALIZE:demo-repair-app'
[[ $EUID == 0 ]] || die 'Root is required only for this bounded finalization'
self=${BASH_SOURCE[0]}
[[ $self == /root/repair-app-t024-finalize.sh && ! -L $self && $(stat -c %u "$self") == 0 ]] || die 'Run only the reviewed root-owned fixed copy'
self_mode=$(stat -c %a "$self")
(( (8#$self_mode & 022) == 0 )) || die 'Finalizer must not be writable by group/others'
app=/var/www/demo-repair-app/current
marker=/var/www/demo-repair-app/.deploy-pending
manifest=/home/muzib/deploy-demo-repair-app/source-install-evidence/FROZEN-SHA256SUMS
hook=/etc/letsencrypt/renewal-hooks/deploy/demo-repair-app
revision=d047f25ea31d703660212e4205b937053c92adcb
[[ -d $app && ! -L $app && $(readlink -f "$app") == "$app" && $(stat -c %U "$app") == muzib ]] || die 'Unexpected application target/owner'
[[ -f $marker && ! -L $marker ]] || die '503 pending marker must remain present'
for path in "$app/.git" "$app/storage" "$app/storage/logs" "$app/bootstrap" "$app/bootstrap/cache"; do
    [[ -d $path && ! -L $path && $(readlink -f "$path") == "$path" ]] || die 'Unexpected symlink/runtime path'
done
[[ $(runuser -u muzib -- git -C "$app" rev-parse HEAD) == "$revision" ]] || die 'Source revision does not match accepted candidate'
[[ -z $(runuser -u muzib -- git -C "$app" status --porcelain) ]] || die 'Source must remain clean'
[[ -f $manifest && ! -L $manifest ]] || die 'Frozen source manifest missing'
printf '%s  %s\n' d98d5f99a8eae15ef63812af89772ecda1524998882a42ed988eadae2b09a46e "$manifest" | sha256sum -c -
(cd "$app" && runuser -u muzib -- sha256sum -c "$manifest" >/dev/null)
[[ -f $hook && ! -L $hook && $(stat -c %u "$hook") == 0 ]] || die 'Expected root-owned demo hook missing'
mode=$(stat -c %a "$hook")
(( (8#$mode & 022) == 0 )) || die 'Demo hook is writable by group/others'
printf '%s  %s\n' 454db919f7be4faea483d2eed3639a8ba70ff8bb323881592505fd0d4493af43 "$hook" | sha256sum -c -
active_probe=''
cleanup_probe() {
    if [[ -n $active_probe && -f $active_probe && ! -L $active_probe && $(stat -c %U "$active_probe") == www-data ]]; then
        case $active_probe in
            "$app/storage/logs"/.t024-acl-*|"$app/bootstrap/cache"/.t024-acl-*) rm -- "$active_probe" ;;
        esac
    fi
}
trap cleanup_probe EXIT
trap 'printf "%s\n" "Finalization failed: keep503; inspect evidence before retry, no DB or go-live performed." >&2' ERR
find "$app" -path "$app/.git" -prune -o -exec chgrp -h www-data {} +
find "$app" -path "$app/.git" -prune -o -type d -exec chmod g+s {} +
[[ $(stat -c %a "$app/.git") == 700 ]] || die 'Git directory permission changed unexpectedly'
for file in "$app/public/index.php" "$app/vendor/autoload.php" "$app/.env"; do
    runuser -u www-data -- test -r "$file"
done
runuser -u www-data -- test ! -w "$app"
runuser -u www-data -- test ! -w "$app/app/Services/DocumentNumber.php"
runuser -u www-data -- test ! -w "$app/vendor/autoload.php"
for dir in "$app/storage/logs" "$app/bootstrap/cache"; do
    file="$dir/.t024-acl-$(od -An -N8 -tx1 /dev/urandom | tr -d ' \n')"
    # PHP exclusive fopen models normal0666 creation; mktemp0600 would mask inherited ACLs.
    runuser -u www-data -- /usr/bin/php8.3 -r '$h=fopen($argv[1], "x"); if (!$h || fwrite($h,"fpm\n")!==4) exit(1); fclose($h);' "$file"
    active_probe=$file
    runuser -u muzib -- /usr/bin/php8.3 -r 'if (file_put_contents($argv[1],"operator\n",FILE_APPEND)!==9) exit(1);' "$file"
    [[ $(cat "$file") == "$(printf 'fpm\noperator')" ]] || die 'Runtime ACL write proof failed'
    [[ $(stat -c %a "$file") == 660 ]] || die 'Runtime probe must have mode660'
    stat -c '%U:%G %a %n' "$file"
    getfacl -cp "$file"
    [[ -f $file && ! -L $file && $(stat -c %U "$file") == www-data ]] || die 'Unexpected probe ownership'
    # Delete only the exclusive synthetic probe this loop just created.
    cleanup_probe
    active_probe=''
done
stat -c '%U:%G %a %n' "$app" "$app/vendor" "$app/vendor/autoload.php" "$app/.env" "$app/storage" "$app/bootstrap/cache" "$app/.git"
[[ -z $(runuser -u muzib -- git -C "$app" status --porcelain) ]] || die 'Source changed during permission correction'
env RENEWED_LINEAGE=/etc/letsencrypt/live/demo-repair-app.scannara.asia RENEWED_DOMAINS=demo-repair-app.scannara.asia "$hook"
systemctl is-active nginx php8.3-fpm certbot.timer
[[ -f $marker && ! -L $marker ]] || die 'Pending marker changed unexpectedly'
printf '%s\n' 'PASS bounded demo permission/ACL/hook finalization;503 marker retained; no DB/key/migration/seed/cron/up action'
