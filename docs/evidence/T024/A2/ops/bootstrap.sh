#!/usr/bin/env bash
# First demo install only. Run reviewed root-owned copy from /root/repair-app-t024-bootstrap.
set -euo pipefail
umask 077
export LC_ALL=C
die() { printf '%s\n' "$*" >&2; exit 1; }
[[ $# == 1 && $1 == PREPARE:demo-repair-app ]] || die 'Usage: bootstrap.sh PREPARE:demo-repair-app'
[[ $EUID == 0 ]] || die 'Run as root from the reviewed, root-owned staged bundle'
bundle=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)
[[ $bundle == /root/repair-app-t024-bootstrap ]] || die 'Stage reviewed bundle under /root/repair-app-t024-bootstrap first'
for file in "$bundle" "$bundle"/{bootstrap.sh,php-fpm.conf,nginx-http.conf,nginx-https.conf,logrotate.conf,renewal-hook.sh}; do
    [[ ! -L $file && $(stat -c %u "$file") == 0 ]] || die 'Bundle must be root-owned and contain no symlinks'
    mode=$(stat -c %a "$file")
    (( (8#$mode & 022) == 0 )) || die 'Bundle must not be writable by group or others'
done
[[ -d /root && ! -L /root && $(stat -c %u /root) == 0 ]] || die 'Untrusted staging parent'
. /etc/os-release
[[ $ID == debian && $VERSION_ID == 12 ]] || die 'This bundle is only reviewed for Debian 12'
id muzib >/dev/null
getent group www-data >/dev/null
for file in /var/www/demo-repair-app /var/lib/demo-repair-app /var/log/demo-repair-app /etc/nginx/sites-available/demo-repair-app /etc/nginx/sites-enabled/demo-repair-app /etc/nginx/sites-available/demo-repair-app.next /etc/nginx/sites-available/demo-repair-app.http503 /etc/php/8.3/fpm/pool.d/demo-repair-app.conf /etc/logrotate.d/demo-repair-app /etc/letsencrypt/renewal-hooks/deploy/demo-repair-app /etc/letsencrypt/live/demo-repair-app.scannara.asia /etc/letsencrypt/renewal/demo-repair-app.scannara.asia.conf; do
    [[ ! -e $file && ! -L $file ]] || die "Already exists: $file. Stop and inspect; never rerun blindly after a partial install."
done
systemctl is-active --quiet nginx || die 'Existing Nginx must be active'
systemctl is-active --quiet certbot.timer || die 'Owner must inspect/enable the existing Certbot timer before bootstrap'
nginx_before=$(mktemp)
trap 'rm -f -- "$nginx_before"' EXIT
/usr/sbin/nginx -T >"$nginx_before" 2>&1 || die 'Existing Nginx configuration is invalid'
if grep -Fq 'demo-repair-app.scannara.asia' "$nginx_before"; then die 'Domain already occurs in existing Nginx config; inspect first'; fi
packages=(php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl)
apt_plan=$(apt-get -s install --no-upgrade --no-install-recommends "${packages[@]}")
printf '%s\n' "$apt_plan"
grep -Eq '^0 upgraded, [0-9]+ newly installed, 0 to remove and [0-9]+ not upgraded\.$' <<<"$apt_plan" || die 'APT plan would upgrade/remove packages or has unexpected output'
while read -r package; do
    [[ $package == php8.3-* ]] || die "Unexpected new APT package: $package"
done < <(awk '/^Inst / { print $2 }' <<<"$apt_plan")
# Pin existing executable values before apt registers 8.3 at higher priority.
# This changes alternatives auto -> manual only; preserve php/phar runtime versions.
groups=(php phar phar.phar)
defaults=()
for group in "${groups[@]}"; do
    query=$(update-alternatives --query "$group") || die "Cannot inspect existing $group alternative"
    value=$(awk '/^Value: / { print $2 }' <<<"$query")
    [[ $value == /* && -x $value ]] || die "Invalid current $group alternative"
    defaults+=("$value")
done
trap 'printf "%s\n" "Bootstrap failed. Keep pending marker/HTTP503; inspect the completed steps before any manual recovery." >&2' ERR
for index in "${!groups[@]}"; do
    group=${groups[$index]}
    printf 'Preserve %s=%s (manual mode)\n' "$group" "${defaults[$index]}"
    update-alternatives --set "$group" "${defaults[$index]}"
done
DEBIAN_FRONTEND=noninteractive apt-get install -y --no-upgrade --no-install-recommends "${packages[@]}"
for index in "${!groups[@]}"; do
    group=${groups[$index]}
    value=$(update-alternatives --query "$group" | awk '/^Value: / { print $2 }')
    [[ $value == "${defaults[$index]}" ]] || die "Default $group changed unexpectedly"
done
install -d -o muzib -g www-data -m 2750 /var/www/demo-repair-app
install -o muzib -g www-data -m 0640 /dev/null /var/www/demo-repair-app/.deploy-pending
install -d -o root -g root -m 0755 /var/lib/demo-repair-app /var/lib/demo-repair-app/acme
install -d -o root -g www-data -m 0750 /var/log/demo-repair-app
install -o www-data -g www-data -m 0640 /dev/null /var/log/demo-repair-app/php-error.log
for name in nginx-access nginx-error; do
    install -o root -g adm -m 0640 /dev/null "/var/log/demo-repair-app/$name.log"
done
install -o root -g root -m 0644 "$bundle/php-fpm.conf" /etc/php/8.3/fpm/pool.d/demo-repair-app.conf
install -o root -g root -m 0644 "$bundle/logrotate.conf" /etc/logrotate.d/demo-repair-app
/usr/sbin/php-fpm8.3 -t
systemctl reload php8.3-fpm
install -o root -g root -m 0644 "$bundle/nginx-http.conf" /etc/nginx/sites-available/demo-repair-app
ln -s /etc/nginx/sites-available/demo-repair-app /etc/nginx/sites-enabled/demo-repair-app
if ! /usr/sbin/nginx -t; then
    # Only this run created this link; remove it to preserve the prior valid config.
    rm -- /etc/nginx/sites-enabled/demo-repair-app
    /usr/sbin/nginx -t || die 'Prior Nginx config also fails; inspect host before reload'
    die 'Initial demo HTTP config failed validation; new enabled link removed, old sites unchanged'
fi
systemctl reload nginx
# Existing Certbot account selection/email, if requested, stays in owner's terminal.
certbot certonly --webroot -w /var/lib/demo-repair-app/acme --cert-name demo-repair-app.scannara.asia -d demo-repair-app.scannara.asia
[[ -s /etc/letsencrypt/live/demo-repair-app.scannara.asia/fullchain.pem && -s /etc/letsencrypt/live/demo-repair-app.scannara.asia/privkey.pem ]] || die 'Certificate files missing'
# Replace only the file this first-install script just created.
cp -- /etc/nginx/sites-available/demo-repair-app /etc/nginx/sites-available/demo-repair-app.http503
install -o root -g root -m 0644 "$bundle/nginx-https.conf" /etc/nginx/sites-available/demo-repair-app.next
mv -- /etc/nginx/sites-available/demo-repair-app.next /etc/nginx/sites-available/demo-repair-app
if ! /usr/sbin/nginx -t; then
    mv -- /etc/nginx/sites-available/demo-repair-app.http503 /etc/nginx/sites-available/demo-repair-app
    /usr/sbin/nginx -t || die 'HTTP503 recovery failed validation; inspect host before reload'
    systemctl reload nginx
    die 'TLS config failed validation; restored validated HTTP503, old sites unchanged'
fi
systemctl reload nginx
install -o root -g root -m 0755 "$bundle/renewal-hook.sh" /etc/letsencrypt/renewal-hooks/deploy/demo-repair-app
certbot renew --cert-name demo-repair-app.scannara.asia --dry-run
# Certbot 2.1 does not support --run-deploy-hooks; exercise only this root-owned hook explicitly.
env RENEWED_LINEAGE=/etc/letsencrypt/live/demo-repair-app.scannara.asia RENEWED_DOMAINS=demo-repair-app.scannara.asia /etc/letsencrypt/renewal-hooks/deploy/demo-repair-app
[[ -f /var/www/demo-repair-app/.deploy-pending ]] || die 'Pending gate was removed unexpectedly'
printf '%s\n' 'Host/TLS preparation complete; HTTPS remains 503. No DB, source install, migration or go-live performed.'
