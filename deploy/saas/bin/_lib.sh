#!/usr/bin/env bash
# Funciones comunes de los scripts del SaaS. No se ejecuta directamente.
set -euo pipefail

SAAS_HOME="${SAAS_HOME:-/opt/saas}"
DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

[ -f "$SAAS_HOME/saas.env" ] || { echo "Falta $SAAS_HOME/saas.env (copia saas.env.example)." >&2; exit 1; }
set -a; . "$SAAS_HOME/saas.env"; set +a

BACKUP_DIR="${BACKUP_DIR:-$SAAS_HOME/backups}"
MYSQL_CONTAINER="${MYSQL_CONTAINER:-saas-mysql-1}"

die()  { echo "✖ $*" >&2; exit 1; }
info() { echo "▸ $*"; }
ok()   { echo "✔ $*"; }

tenant_dir()  { echo "$SAAS_HOME/tenants/$1"; }
tenant_env()  { echo "$(tenant_dir "$1")/.env"; }
tenant_list() { [ -d "$SAAS_HOME/tenants" ] && find "$SAAS_HOME/tenants" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort; }
tenant_version() { cat "$(tenant_dir "$1")/VERSION"; }
tenant_domain()  { grep '^APP_URL=' "$(tenant_env "$1")" | cut -d= -f2- | sed 's#^https\?://##'; }
tenant_db()      { grep '^DB_DATABASE=' "$(tenant_env "$1")" | cut -d= -f2-; }

require_tenant() {
    [ -n "${1:-}" ] || die "Indica el cliente."
    [ -f "$(tenant_env "$1")" ] || die "No existe el cliente '$1'."
}

# docker compose del cliente con su versión (o la indicada en VERSION_OVERRIDE)
tenant_compose() {
    local t="$1"; shift
    TENANT="$t" DOMAIN="$(tenant_domain "$t")" IMAGE="$IMAGE" \
    VERSION="${VERSION_OVERRIDE:-$(tenant_version "$t")}" TENANT_ENV="$(tenant_env "$t")" \
        docker compose -p "crm-$t" -f "$DEPLOY_DIR/compose.tenant.yml" "$@"
}

artisan() {
    local t="$1"; shift
    tenant_compose "$t" exec -T -u www-data web php artisan "$@"
}

mysql_root() {
    docker exec -i "$MYSQL_CONTAINER" mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$@" 2>/dev/null
}

# Espera a que el CRM responda dentro del contenedor web (máx. ~2 min)
wait_ready() {
    local t="$1" i
    for i in $(seq 1 60); do
        if tenant_compose "$t" exec -T web curl -fsS -o /dev/null http://127.0.0.1/up 2>/dev/null; then
            return 0
        fi
        sleep 2
    done
    return 1
}

random() { openssl rand -hex "${1:-16}"; }
