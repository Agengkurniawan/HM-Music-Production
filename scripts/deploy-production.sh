#!/usr/bin/env bash

set -Eeuo pipefail

readonly APP_ROOT='/home/hmmusicp5/HM-Music-Production'
readonly PUBLIC_ROOT='/home/hmmusicp5/public_html'
readonly PHP_BIN='/opt/cpanel/ea-php84/root/usr/bin/php'
readonly COMPOSER_PHAR="$APP_ROOT/storage/app/composer.phar"
readonly COMPOSER_SHA256='5ee7125f8a30a34d246cefdc0bc85b8a783b28f2aec968994118512350d28027'

if [[ "$APP_ROOT" != '/home/hmmusicp5/HM-Music-Production' ]] ||
   [[ "$PUBLIC_ROOT" != '/home/hmmusicp5/public_html' ]]; then
    echo 'Refusing deployment: unexpected production path.' >&2
    exit 1
fi

cd "$APP_ROOT"

if [[ ! -x "$PHP_BIN" ]]; then
    echo "Required PHP binary is unavailable: $PHP_BIN" >&2
    exit 1
fi

if ! "$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);'; then
    echo 'Production dependencies require PHP 8.4.1 or newer.' >&2
    exit 1
fi

if [[ ! -f .env ]] || [[ ! -f artisan ]] || [[ ! -s public/build/manifest.json ]]; then
    echo 'Deployment preflight failed: .env, artisan, or Vite manifest is missing.' >&2
    exit 1
fi

if [[ -f "$COMPOSER_PHAR" ]]; then
    composer_hash=$("$PHP_BIN" -r 'echo hash_file("sha256", $argv[1]);' "$COMPOSER_PHAR")

    if [[ "$composer_hash" != "$COMPOSER_SHA256" ]]; then
        echo 'The private Composer PHAR failed its integrity check.' >&2
        exit 1
    fi

    COMPOSER=("$PHP_BIN" "$COMPOSER_PHAR")
elif command -v composer >/dev/null 2>&1; then
    COMPOSER=("$(command -v composer)")
else
    echo 'Composer is unavailable on the production server.' >&2
    exit 1
fi

mkdir -p storage/framework
exec 9>storage/framework/hm-production-deploy.lock

if command -v flock >/dev/null 2>&1 && ! flock -n 9; then
    echo 'Another production deployment is already running.' >&2
    exit 1
fi

deployment_failed() {
    local exit_code=$?

    if (( exit_code != 0 )); then
        echo 'Deployment failed. Application remains in maintenance mode for safety.' >&2
    fi
}

trap deployment_failed EXIT

"$PHP_BIN" artisan down --retry=60 --refresh=15

"${COMPOSER[@]}" install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

"${COMPOSER[@]}" check-platform-reqs --no-dev
"$PHP_BIN" artisan migrate --force

readonly BUILD_NEXT="$PUBLIC_ROOT/build_next"
readonly BUILD_CURRENT="$PUBLIC_ROOT/build"
readonly BUILD_PREVIOUS="$PUBLIC_ROOT/build_previous"

rm -rf "$BUILD_NEXT"
cp -a "$APP_ROOT/public/build" "$BUILD_NEXT"

if [[ ! -s "$BUILD_NEXT/manifest.json" ]]; then
    echo 'Copied Vite build is incomplete.' >&2
    exit 1
fi

rm -rf "$BUILD_PREVIOUS"

if [[ -d "$BUILD_CURRENT" ]]; then
    mv "$BUILD_CURRENT" "$BUILD_PREVIOUS"
fi

if ! mv "$BUILD_NEXT" "$BUILD_CURRENT"; then
    if [[ -d "$BUILD_PREVIOUS" ]] && [[ ! -d "$BUILD_CURRENT" ]]; then
        mv "$BUILD_PREVIOUS" "$BUILD_CURRENT"
    fi

    echo 'Unable to activate the new Vite build.' >&2
    exit 1
fi

mkdir -p "$PUBLIC_ROOT/audio" "$PUBLIC_ROOT/img"
cp -a "$APP_ROOT/public/audio/." "$PUBLIC_ROOT/audio/"
cp -a "$APP_ROOT/public/img/." "$PUBLIC_ROOT/img/"
cp "$APP_ROOT/public/favicon.ico" "$PUBLIC_ROOT/favicon.ico"
cp "$APP_ROOT/public/robots.txt" "$PUBLIC_ROOT/robots.txt"

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan queue:restart || true
"$PHP_BIN" artisan up

trap - EXIT
echo "Production deployment completed at commit $(git rev-parse --short HEAD)."
