#!/usr/bin/env bash
set -euo pipefail
# Certbot invokes deploy hooks for all lineages; reload only for this demo.
case " ${RENEWED_DOMAINS:-} " in
    *" demo-repair-app.scannara.asia "*) /usr/sbin/nginx -t; /bin/systemctl reload nginx ;;
esac
