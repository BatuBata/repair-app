#!/usr/bin/env bash
set -euo pipefail
umask 077
die() { printf '%s\n' "$*" >&2; exit 1; }
[[ $# == 5 ]] || die 'Usage: backup.sh /private/client.cnf database /backup/dir ssh-alias /remote/dir (use - - while Q-04 is pending)'
cnf=$1 db=$2 dir=$3 remote=$4 remote_dir=$5
[[ $db =~ ^[A-Za-z][A-Za-z0-9_]{0,63}$ ]] || die 'Invalid database name'
[[ $cnf == /* && -f $cnf && ! -L $cnf && -O $cnf ]] || die 'Client file must be absolute, regular, owned by this user'
mode=$(stat -c %a "$cnf" 2>/dev/null || stat -f %Lp "$cnf")
[[ $mode == 600 || $mode == 400 ]] || die 'Client file must have mode 600 or 400'
[[ $dir == /* && $dir != / && -d $dir && ! -L $dir && -O $dir ]] || die 'Use an existing private absolute backup directory'
mode=$(stat -c %a "$dir" 2>/dev/null || stat -f %Lp "$dir")
[[ $mode == 700 ]] || die 'Backup directory must have mode 700'
if [[ $remote != - || $remote_dir != - ]]; then
    [[ $remote =~ ^[A-Za-z][A-Za-z0-9_-]*$ && $remote_dir =~ ^/[A-Za-z0-9_/-]+$ && $remote_dir != / ]] || die 'Use a safe SSH alias and an absolute remote directory'
    [[ $remote_dir != *//* && $remote_dir != */../* && $remote_dir != */.. && $remote_dir != */ ]] || die 'Invalid remote directory'
fi
unset MYSQL_PWD
lock=$dir/.backup-$db.lock
mkdir "$lock" 2>/dev/null || die 'Backup locked; inspect owner/process before removing a stale lock'
work=''
cleanup() { [[ -z $work ]] || rm -rf -- "$work"; rmdir "$lock"; }
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
work=$(mktemp -d "$dir/.work-XXXXXX")
name=$db-$(date -u +%Y%m%dT%H%M%SZ)-${work##*-}.sql.gz
# No --databases: a restore must choose its target rather than replay CREATE DATABASE / USE.
if ! mariadb-dump --defaults-file="$cnf" --single-transaction --quick --no-tablespaces --hex-blob --triggers "$db" 2>"$work/error" | gzip -c >"$work/dump.gz"; then
    die 'Dump/compression failed; no retention or off-VPS copy performed'
fi
gzip -t "$work/dump.gz" 2>"$work/error" || die 'Archive validation failed'
mv -- "$work/dump.gz" "$dir/$name"
[[ $remote != - ]] || die 'Local archive saved; Q-04 off-VPS destination missing; retention skipped (NOT a successful daily backup)'
scp -q -o BatchMode=yes -o StrictHostKeyChecking=yes "$dir/$name" "$remote:$remote_dir/$name.partial" 2>"$work/error" || die 'Off-VPS copy failed; local archive kept; retention skipped'
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes "$remote" "mv -- '$remote_dir/$name.partial' '$remote_dir/$name'" 2>"$work/error" || die 'Off-VPS publish failed; local archive kept; retention skipped'
# GNU/BSD find: +13 whole elapsed days means at least 14 days; never follow symlinks.
find "$dir" -maxdepth 1 -type f -name "$db-????????T??????Z-??????.sql.gz" -mtime +13 -exec rm -- {} +
printf 'Backup and off-VPS copy complete: %s\n' "$name"
