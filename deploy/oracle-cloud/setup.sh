#!/usr/bin/env bash
set -Eeuo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PERSONNEL_DIR="$REPO_ROOT/personnel-service"
TRAINING_DIR="$REPO_ROOT/training-portal"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/ardic}"

fail() {
    printf 'Error: %s\n' "$1" >&2
    exit 1
}

[[ "$(id -u)" -ne 0 ]] || fail "Run as the non-root deployment user; use sudo only for system administration."
[[ -d "$PERSONNEL_DIR" && -d "$TRAINING_DIR" ]] || fail "Run this script from a checked-out project containing both Laravel apps."
[[ -d "$BACKUP_DIR" && -w "$BACKUP_DIR" ]] || fail "Create a private, writable backup directory before deployment."
[[ "$BACKUP_DIR" == /* && "$BACKUP_DIR" != *"'"* && "$BACKUP_DIR" != *$'\n'* ]] || fail "BACKUP_DIR must be an absolute path without quotes or newlines."
[[ "$(stat -c '%a' "$BACKUP_DIR")" == 700 && "$(stat -c '%u' "$BACKUP_DIR")" == "$(id -u)" ]] || fail "The backup directory must be owned by this user with mode 0700."

WWW_DATA_GID="$(getent group www-data | cut -d: -f3)"
[[ -n "$WWW_DATA_GID" ]] || fail "The www-data group does not exist."
id -G | tr ' ' '\n' | grep -Fqx "$WWW_DATA_GID" || fail "The deployment user must have active www-data group membership; start a fresh login session."

command -v php >/dev/null || fail "PHP 8.3 or newer is required."
command -v composer >/dev/null || fail "Composer is required."
command -v node >/dev/null || fail "Node.js 20.19 or newer is required for Vite 8."
command -v npm >/dev/null || fail "npm is required."
command -v sqlite3 >/dev/null || fail "The sqlite3 CLI is required for consistent pre-migration backups."

php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") && version_compare(PHP_VERSION, "9.0.0", "<") ? 0 : 1);' || fail "PHP 8.3 through PHP 8.x is required."
node -e 'const [major, minor] = process.versions.node.split(".").map(Number); process.exit((major === 20 && minor >= 19) || (major >= 22 && (major > 22 || minor >= 12)) ? 0 : 1)' || fail "Node.js 20.19+ or 22.12+ is required for Vite 8."
php -r 'foreach (["curl", "mbstring", "openssl", "pdo_sqlite", "xml"] as $extension) { if (! extension_loaded($extension)) { fwrite(STDERR, "Missing PHP extension: {$extension}\n"); exit(1); } }' || fail "Install the required PHP extensions before deploying."

get_env_value() {
    awk -v key="$1" 'index($0, key "=") == 1 { sub(/^[^=]*=/, ""); print; found = 1; exit } END { if (!found) exit 1 }' "$2"
}

require_setting() {
    grep -Fqx "$1=$2" "$3" || fail "A required production setting is missing or incorrect in an app environment file."
}

validate_environment() {
    local app_dir="$1"
    local app_name="$2"
    local app_url="$3"
    local db_path="$4"
    local env_file="$app_dir/.env"
    local app_key
    local api_token

    [[ -f "$env_file" ]] || fail "Create and secure $app_name/.env from its production example before running this script."
    [[ -r "$env_file" ]] || fail "The deployment user cannot read $app_name/.env; verify active www-data group membership."
    [[ -f "$db_path" ]] || fail "Create the persistent SQLite database file for $app_name before running this script."
    [[ "$app_url" == https://* && "$app_url" != *example.invalid* ]] || fail "Set a real HTTPS APP_URL in each production environment file."
    [[ "$(stat -c '%a' "$env_file")" == 640 && "$(stat -c '%U:%G' "$env_file")" == root:www-data ]] || fail "Set each production .env to root:www-data with mode 0640."
    [[ "$(stat -c '%a' "$db_path")" == 660 && "$(stat -c '%U:%G' "$db_path")" == www-data:www-data && -r "$db_path" && -w "$db_path" ]] || fail "Set the persistent SQLite database to www-data:www-data, mode 0660."
    [[ "$(stat -c '%a:%G' "$(dirname "$db_path")")" == 2770:www-data && -w "$(dirname "$db_path")" ]] || fail "Set the persistent SQLite parent directory to www-data:www-data, mode 2770."
    [[ "$(stat -c '%a:%G' "$app_dir")" == 2755:www-data ]] || fail "Set the app directory group to www-data and mode 2755 so generated files inherit the shared group."
    for runtime_dir in "$app_dir/storage" "$app_dir/bootstrap/cache"; do
        [[ "$(stat -c '%a:%G' "$runtime_dir")" == 2770:www-data && -w "$runtime_dir" ]] || fail "Set Laravel storage and bootstrap/cache directories to group www-data, mode 2770."
    done
    require_setting APP_ENV production "$env_file"
    require_setting APP_DEBUG false "$env_file"
    require_setting APP_URL "$app_url" "$env_file"
    require_setting DB_CONNECTION sqlite "$env_file"
    require_setting DB_DATABASE "$db_path" "$env_file"
    require_setting SESSION_DRIVER database "$env_file"
    require_setting SESSION_SECURE_COOKIE true "$env_file"
    require_setting SESSION_HTTP_ONLY true "$env_file"
    require_setting SESSION_SAME_SITE lax "$env_file"
    require_setting CACHE_STORE database "$env_file"
    require_setting QUEUE_CONNECTION database "$env_file"

    app_key="$(get_env_value APP_KEY "$env_file")" || fail "APP_KEY is missing from an app environment file."
    [[ -n "$app_key" && "$app_key" != *replace-* ]] || fail "Set a unique APP_KEY for each application."
    api_token="$(get_env_value PERSONNEL_API_TOKEN "$env_file")" || fail "PERSONNEL_API_TOKEN is missing from an app environment file."
    [[ "${#api_token}" -ge 43 && "$api_token" != *replace-* ]] || fail "Set a high-entropy shared API token in both app environment files."

    printf '%s' "$app_key"
}

[[ -f "$PERSONNEL_DIR/.env" && -f "$TRAINING_DIR/.env" ]] || fail "Create both production .env files before running this script."
PERSONNEL_URL="$(get_env_value APP_URL "$PERSONNEL_DIR/.env")" || fail "APP_URL is missing from the Personnel Service environment file."
TRAINING_URL="$(get_env_value APP_URL "$TRAINING_DIR/.env")" || fail "APP_URL is missing from the Training Portal environment file."
PERSONNEL_KEY="$(validate_environment "$PERSONNEL_DIR" personnel-service \
    "$PERSONNEL_URL" \
    /var/lib/ardic/personnel-service/database/database.sqlite)"
TRAINING_KEY="$(validate_environment "$TRAINING_DIR" training-portal \
    "$TRAINING_URL" \
    /var/lib/ardic/training-portal/database/database.sqlite)"
PERSONNEL_TOKEN="$(get_env_value PERSONNEL_API_TOKEN "$PERSONNEL_DIR/.env")"
TRAINING_TOKEN="$(get_env_value PERSONNEL_API_TOKEN "$TRAINING_DIR/.env")"

[[ "$PERSONNEL_KEY" != "$TRAINING_KEY" ]] || fail "The two applications must have different APP_KEY values."
[[ "$PERSONNEL_TOKEN" == "$TRAINING_TOKEN" ]] || fail "The two applications must use the same PERSONNEL_API_TOKEN."
require_setting PERSONNEL_SERVICE_URL "$PERSONNEL_URL" "$TRAINING_DIR/.env"

for app in personnel-service training-portal; do
    [[ -f "$REPO_ROOT/$app/composer.lock" && -f "$REPO_ROOT/$app/package-lock.json" ]] || fail "A Composer or npm lock file is missing for $app."
done

BACKUP_STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
for app in personnel-service training-portal; do
    db_path="/var/lib/ardic/$app/database/database.sqlite"
    backup_path="$BACKUP_DIR/$app-$BACKUP_STAMP.sqlite"
    [[ ! -e "$backup_path" ]] || fail "A backup with this timestamp already exists; retry after a moment."
    (umask 077; sqlite3 "$db_path" ".backup '$backup_path'")
    chmod 0600 "$backup_path"
    [[ "$(stat -c '%a:%U' "$backup_path")" == 600:"$(id -un)" ]] || fail "Could not verify private ownership and permissions for a SQLite backup."
done

for app in personnel-service training-portal; do
    app_dir="$REPO_ROOT/$app"
    (umask 0007; composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --working-dir="$app_dir")
    chgrp -R www-data "$app_dir/vendor"
    find "$app_dir/vendor" -type d -exec chmod 2770 {} +
    find "$app_dir/vendor" -type f -exec chmod g+rw,o-rwx {} +
    (
        cd "$app_dir"
        umask 0022
        npm ci
        npm run build
        find public/build -type d -exec chmod 0755 {} +
        find public/build -type f -exec chmod 0644 {} +
    )
    (
        cd "$app_dir"
        umask 0007
        php artisan config:clear
        php artisan migrate --force
        php artisan config:cache
        chgrp www-data bootstrap/cache/config.php
        chmod 0640 bootstrap/cache/config.php
        php artisan view:cache
    )
done

printf '%s\n' "Both applications updated. Backups are in $BACKUP_DIR."
printf '%s\n' "Demo seeding is intentionally not repeated by this script; see DEPLOYMENT-ORACLE.md for first-install seeding and service restart steps."
