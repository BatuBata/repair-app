#!/bin/sh
set -eu
cnf=${1#--defaults-file=}
/opt/homebrew/bin/docker --context colima cp "$cnf" "saas-s009-a1-mariadb:$cnf"
exec /opt/homebrew/bin/docker --context colima exec -i saas-s009-a1-mariadb /usr/bin/mariadb "$@"
