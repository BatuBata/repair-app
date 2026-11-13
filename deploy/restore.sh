#!/usr/bin/env bash
set -euo pipefail
umask 077
die() { printf '%s\n' "$*" >&2; exit 1; }
[[ $# == 4 ]] || die 'Usage: restore.sh /private/restore-client.cnf target_database /absolute/trusted.sql.gz RESTORE:target_database'
cnf=$1 db=$2 archive=$3 confirmation=$4
[[ $db =~ ^[A-Za-z][A-Za-z0-9_]{0,63}$ ]] || die 'Invalid target database'
case "$(printf '%s' "$db" | tr '[:upper:]' '[:lower:]')" in mysql|sys|information_schema|performance_schema) die 'System database forbidden';; esac
[[ $confirmation == "RESTORE:$db" ]] || die 'Explicit matching restore confirmation required'
[[ $cnf == /* && -f $cnf && ! -L $cnf && -O $cnf ]] || die 'Client file must be absolute, regular, owned by this user'
mode=$(stat -c %a "$cnf" 2>/dev/null || stat -f %Lp "$cnf")
[[ $mode == 600 || $mode == 400 ]] || die 'Client file must have mode 600 or 400'
[[ $archive == /* && -f $archive && ! -L $archive && -r $archive ]] || die 'Use an absolute, readable, trusted archive'
unset MYSQL_PWD
work=$(mktemp -d "${TMPDIR:-/tmp}/repair-restore.XXXXXX")
trap 'rm -rf -- "$work"' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
# Fully decompress before any DB connection, so damaged gzip never causes a partial import.
gzip -dc -- "$archive" >"$work/restore.sql" 2>"$work/error" || die 'Archive decompression failed; MySQL not called'
[[ -s $work/restore.sql ]] || die 'Empty dump; MySQL not called'
mysql --defaults-file="$cnf" --batch --binary-mode --database="$db" <"$work/restore.sql" >"$work/output" 2>"$work/error" || die 'Restore failed; target may be partially changed; keep maintenance mode, investigate before retry'
printf 'Import complete for %s; validate data and application before reopening\n' "$db"
