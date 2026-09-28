#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
CONFIG_FILE=${FRANKENPHP_BUILD_CONFIG:-"$SCRIPT_DIR/frankenphp-build.conf"}
INIT_ONLY=false

log() { printf '[frankenphp-build] %s\n' "$*"; }
die() { printf '[frankenphp-build] ERROR: %s\n' "$*" >&2; exit 1; }

usage() {
    cat <<EOF
Usage: $(basename -- "$0") [OPTIONS]

Build a custom FrankenPHP binary using the selected configuration.

Options:
  --init             Create the configuration file if missing, then exit.
  --config FILE      Use FILE instead of the default configuration path.
  -h, --help         Show this help message and exit.

Default configuration:
  $CONFIG_FILE

The FRANKENPHP_BUILD_CONFIG environment variable may also set the config path.
EOF
}

while [ "$#" -gt 0 ]; do
    case "$1" in
        --init)
            INIT_ONLY=true
            shift
            ;;
        --config)
            [ "$#" -ge 2 ] || die "--config requires a file path."
            CONFIG_FILE=$2
            shift 2
            ;;
        --config=*)
            CONFIG_FILE=${1#*=}
            [ -n "$CONFIG_FILE" ] || die "--config requires a file path."
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            die "Unknown option: $1. Use --help for usage information."
            ;;
    esac
done

write_default_config() {
    config_target=$1
    mkdir -p "$(dirname -- "$config_target")"
    config_tmp=$(mktemp "${config_target}.tmp.XXXXXX")
    cat >"$config_tmp" <<'CONFIG'
# FrankenPHP build configuration. This file is never overwritten.
# It is loaded as POSIX shell code; do not add untrusted content.

# Active profile: production or development.
BUILD_PROFILE="production"

# Versions/ref. FRANKENPHP_REF may be a tag (v1.9.1), branch, or commit.
FRANKENPHP_REPOSITORY="https://github.com/php/frankenphp.git"
FRANKENPHP_REF="main"
PHP_VERSION="8.4"

# gnu: mostly-static binary for Linux/glibc (recommended on Ubuntu/Debian).
# musl: fully static binary; it cannot load .so extensions afterward.
BUILD_FLAVOR="gnu"
DOCKER_PLATFORM="linux/amd64"
DOCKER_IMAGE_TAG="local/frankenphp-custom:latest"
DOCKER_PULL="true"
NO_CACHE="false"

# Paths. SOURCE_DIR must be reserved exclusively for this script.
# PROJECT_DIR defaults to the directory where the script is invoked.
# FRANKENPHP_PROJECT_DIR can override it without modifying this file.
PROJECT_DIR=${FRANKENPHP_PROJECT_DIR:-"$(pwd -P)"}
SOURCE_DIR="${PROJECT_DIR}/.frankenphp-build/source"
FINAL_BINARY="${PROJECT_DIR}/frankenphp"
BACKUP_DIR="${PROJECT_DIR}/storage/frankenphp/backups"

# Base list shared by both profiles. Use spaces or line breaks as separators.
COMMON_EXTENSIONS="
apcu bcmath bz2 calendar ctype curl dom exif fileinfo filter ftp gd gettext
grpc iconv igbinary intl mbregex mbstring mysqli mysqlnd opcache opentelemetry
openssl password-argon2 pcntl pdo pdo_mysql phar posix readline redis
session simplexml soap sockets sodium tokenizer xml xmlreader xmlwriter xsl zip zlib
"

# Add production-only extensions here.
PRODUCTION_EXTENSIONS="
"

# Additional development extensions. Remove xdebug if it is not needed.
DEVELOPMENT_EXTENSIONS="
xdebug
"

# Additional libraries for optional extension features.
# Examples: libjpeg libwebp freetype. Leave empty when not needed.
PHP_EXTENSION_LIBS="
"

# Extensions the build must contain; verification fails if any are missing.
REQUIRED_EXTENSIONS="
grpc opentelemetry
"

# Repository and output behavior.
UPDATE_SOURCE="true"
ALLOW_DIRTY_SOURCE="false"
KEEP_DOCKER_IMAGE="true"
CREATE_BACKUP="true"

# Final binary owner and mode. Empty values preserve the current user.
FINAL_OWNER=""
FINAL_GROUP=""
FINAL_MODE="0755"

# Optional application check.
LARAVEL_PROJECT_DIR="${PROJECT_DIR}"
COMPOSER_BINARY="/usr/local/bin/composer"
RUN_COMPOSER_PLATFORM_CHECK="false"

# Extra docker build arguments separated by whitespace, in NAME=value format.
# Values containing whitespace are not supported. Avoid storing secrets here.
EXTRA_BUILD_ARGS=""
CONFIG
    chmod 0600 "$config_tmp"
    mv "$config_tmp" "$config_target"
    log "Initial configuration created: $config_target"
}

if [ ! -f "$CONFIG_FILE" ]; then
    write_default_config "$CONFIG_FILE"
elif [ "$INIT_ONLY" = "true" ]; then
    log "Configuration already exists and was left unchanged: $CONFIG_FILE"
fi

if [ "$INIT_ONLY" = "true" ]; then
    log "Initialization complete. Edit the configuration before building."
    exit 0
fi

if grep -Eq '^[[:space:]]*[A-Z_][A-Z0-9_]*=\(' "$CONFIG_FILE"; then
    die "The configuration uses Bash arrays. Move the old file and run --init, or convert its lists to quoted strings."
fi

# shellcheck source=/dev/null
. "$CONFIG_FILE"

for required_name in \
    BUILD_PROFILE FRANKENPHP_REPOSITORY FRANKENPHP_REF PHP_VERSION \
    BUILD_FLAVOR DOCKER_IMAGE_TAG PROJECT_DIR SOURCE_DIR FINAL_BINARY BACKUP_DIR
do
    eval "required_value=\${$required_name-}"
    [ -n "$required_value" ] || die "Missing required configuration: $required_name"
done

case "$PROJECT_DIR" in
    /*) ;;
    *) die "PROJECT_DIR must be an absolute path: $PROJECT_DIR" ;;
esac

command -v git >/dev/null 2>&1 || die "git was not found."
command -v docker >/dev/null 2>&1 || die "docker was not found."
docker info >/dev/null 2>&1 || die "Docker is not available to the current user."

case "$BUILD_PROFILE" in
    production) PROFILE_EXTENSIONS=${PRODUCTION_EXTENSIONS-} ;;
    development) PROFILE_EXTENSIONS=${DEVELOPMENT_EXTENSIONS-} ;;
    *) die "BUILD_PROFILE must be production or development." ;;
esac

case "$BUILD_FLAVOR" in
    gnu) DOCKERFILE="static-builder-gnu.Dockerfile" ;;
    musl) DOCKERFILE="static-builder-musl.Dockerfile" ;;
    *) die "BUILD_FLAVOR must be gnu or musl." ;;
esac

# Normalize extension lists, sort them, and remove duplicates.
PHP_EXTENSIONS_CSV=$(
    printf '%s\n%s\n' "$COMMON_EXTENSIONS" "$PROFILE_EXTENSIONS" |
        tr ' ,\t' '\n\n\n' |
        sed '/^$/d' |
        LC_ALL=C sort -u |
        paste -sd, -
)
[ -n "$PHP_EXTENSIONS_CSV" ] || die "The extension list is empty."

PHP_EXTENSION_LIBS_CSV=$(
    printf '%s\n' "${PHP_EXTENSION_LIBS-}" |
        tr ' ,\t' '\n\n\n' |
        sed '/^$/d' |
        LC_ALL=C sort -u |
        paste -sd, -
)

clone_or_update_source() {
    if [ ! -e "$SOURCE_DIR" ]; then
        mkdir -p "$(dirname -- "$SOURCE_DIR")"
        log "Cloning FrankenPHP into $SOURCE_DIR"
        git clone --filter=blob:none "$FRANKENPHP_REPOSITORY" "$SOURCE_DIR"
    elif [ ! -d "$SOURCE_DIR/.git" ]; then
        die "SOURCE_DIR exists but is not a Git repository: $SOURCE_DIR"
    fi

    if [ -n "$(git -C "$SOURCE_DIR" status --porcelain)" ] && [ "${ALLOW_DIRTY_SOURCE:-false}" != "true" ]; then
        die "The repository has local changes. Clean it or set ALLOW_DIRTY_SOURCE=true."
    fi

    if [ "${UPDATE_SOURCE:-true}" = "true" ]; then
        log "Updating FrankenPHP references"
        git -C "$SOURCE_DIR" fetch --tags --prune origin
    fi

    git -C "$SOURCE_DIR" rev-parse --verify --quiet "${FRANKENPHP_REF}^{commit}" >/dev/null ||
        die "FRANKENPHP_REF=$FRANKENPHP_REF was not found."
    git -C "$SOURCE_DIR" checkout --detach "$FRANKENPHP_REF"
}

clone_or_update_source
[ -f "$SOURCE_DIR/$DOCKERFILE" ] || die "$DOCKERFILE does not exist in the selected reference."

# The GNU builder currently runs on CentOS 7 and its old Git cannot read
# repositories using format version 1 (for example, partial clones created
# with --filter=blob:none). Build from an exported tree instead of sending the
# host repository metadata to Docker, and provide the version explicitly.
FRANKENPHP_COMMIT=$(git -C "$SOURCE_DIR" rev-parse --verify HEAD)
build_context=$(mktemp -d "${TMPDIR:-/tmp}/frankenphp-context.XXXXXX")
container_id=""
work_dir=""
cleanup() {
    if [ -n "$container_id" ]; then
        docker rm -f "$container_id" >/dev/null 2>&1 || true
    fi
    case "$build_context" in
        "${TMPDIR:-/tmp}"/frankenphp-context.*) rm -rf "$build_context" ;;
    esac
    case "$work_dir" in
        "${TMPDIR:-/tmp}"/frankenphp-build.*) rm -rf "$work_dir" ;;
    esac
}
trap cleanup EXIT HUP INT TERM
git -C "$SOURCE_DIR" archive HEAD | tar -x -C "$build_context"

# Source downloads are the longest and most network-sensitive part of the
# build. Increase SPC retries and retain completed downloads across failed
# Docker builds so a transient reset does not restart the entire download set.
sed -i 's/--retry 5/--retry 10/g' "$build_context/build-static.sh"
sed -i \
    's@RUN --mount=type=secret,id=github-token @RUN --mount=type=secret,id=github-token --mount=type=cache,target=/go/src/app/static-php-cli/downloads @' \
    "$build_context/$DOCKERFILE"

# CentOS 7 is EOL and its archived repositories reject the HTTP URLs enabled
# by the upstream GNU Dockerfile. Keep the Vault repositories, but access them
# through HTTPS. This is applied only to the disposable build context.
if [ "$BUILD_FLAVOR" = "gnu" ]; then
    sed -i \
        's@s/\^#\.\*baseurl=http/baseurl=http/g@s/^#.*baseurl=http/baseurl=https/g@g' \
        "$build_context/$DOCKERFILE"
fi

log "Profile: $BUILD_PROFILE; PHP: $PHP_VERSION; flavor: $BUILD_FLAVOR"
log "Extensions: $PHP_EXTENSIONS_CSV"
log "Building image $DOCKER_IMAGE_TAG"

set -- docker build \
    --file "$build_context/$DOCKERFILE" \
    --tag "$DOCKER_IMAGE_TAG" \
    --build-arg "FRANKENPHP_VERSION=$FRANKENPHP_COMMIT" \
    --build-arg "PHP_VERSION=$PHP_VERSION" \
    --build-arg "PHP_EXTENSIONS=$PHP_EXTENSIONS_CSV"

if [ -n "$PHP_EXTENSION_LIBS_CSV" ]; then
    set -- "$@" --build-arg "PHP_EXTENSION_LIBS=$PHP_EXTENSION_LIBS_CSV"
fi
if [ -n "${DOCKER_PLATFORM:-}" ]; then
    set -- "$@" --platform "$DOCKER_PLATFORM"
fi
if [ "${DOCKER_PULL:-true}" = "true" ]; then
    set -- "$@" --pull
fi
if [ "${NO_CACHE:-false}" = "true" ]; then
    set -- "$@" --no-cache
fi
for extra_pair in ${EXTRA_BUILD_ARGS-}; do
    set -- "$@" --build-arg "$extra_pair"
done
set -- "$@" "$build_context"
"$@"

work_dir=$(mktemp -d "${TMPDIR:-/tmp}/frankenphp-build.XXXXXX")
container_id=$(docker create "$DOCKER_IMAGE_TAG")
case "${DOCKER_PLATFORM:-linux/$(uname -m)}" in
    linux/amd64|linux/x86_64) binary_arch="x86_64" ;;
    linux/arm64|linux/aarch64) binary_arch="aarch64" ;;
    *) die "Unsupported Docker platform for binary extraction: ${DOCKER_PLATFORM:-unset}" ;;
esac
candidate="$work_dir/frankenphp-linux-$binary_arch"
docker cp \
    "$container_id:/go/src/app/dist/frankenphp-linux-$binary_arch" \
    "$candidate"
[ -f "$candidate" ] || die "The compiled FrankenPHP binary was not found in the image."
chmod "$FINAL_MODE" "$candidate"

log "Verifying compiled binary"
"$candidate" version
for extension in ${REQUIRED_EXTENSIONS-}; do
    [ -n "$extension" ] || continue
    "$candidate" php-cli -r "exit(extension_loaded('$extension') ? 0 : 1);" ||
        die "Required extension '$extension' is not loaded."
    log "Verified extension: $extension"
done

if [ "${RUN_COMPOSER_PLATFORM_CHECK:-false}" = "true" ]; then
    [ -d "$LARAVEL_PROJECT_DIR" ] || die "LARAVEL_PROJECT_DIR does not exist."
    [ -f "$COMPOSER_BINARY" ] || die "COMPOSER_BINARY does not exist."
    log "Running composer check-platform-reqs with the compiled PHP"
    (cd "$LARAVEL_PROJECT_DIR" && "$candidate" php-cli "$COMPOSER_BINARY" check-platform-reqs)
fi

mkdir -p "$(dirname -- "$FINAL_BINARY")"
if [ -e "$FINAL_BINARY" ] && [ "${CREATE_BACKUP:-true}" = "true" ]; then
    mkdir -p "$BACKUP_DIR"
    backup="$BACKUP_DIR/frankenphp.$(date -u +%Y%m%dT%H%M%SZ)"
    cp -p "$FINAL_BINARY" "$backup"
    log "Backup created: $backup"
fi

if [ -n "${FINAL_OWNER:-}" ] && [ -n "${FINAL_GROUP:-}" ]; then
    install -m "$FINAL_MODE" -o "$FINAL_OWNER" -g "$FINAL_GROUP" "$candidate" "${FINAL_BINARY}.new"
elif [ -n "${FINAL_OWNER:-}" ]; then
    install -m "$FINAL_MODE" -o "$FINAL_OWNER" "$candidate" "${FINAL_BINARY}.new"
elif [ -n "${FINAL_GROUP:-}" ]; then
    install -m "$FINAL_MODE" -g "$FINAL_GROUP" "$candidate" "${FINAL_BINARY}.new"
else
    install -m "$FINAL_MODE" "$candidate" "${FINAL_BINARY}.new"
fi
mv -f "${FINAL_BINARY}.new" "$FINAL_BINARY"

if [ "${KEEP_DOCKER_IMAGE:-true}" != "true" ]; then
    docker image rm "$DOCKER_IMAGE_TAG" >/dev/null
fi

log "Build installed successfully: $FINAL_BINARY"
log "Octane-compatible startup:"
printf '  cd %s && PATH=%s:$PATH php artisan octane:start --server=frankenphp\n' \
    "${LARAVEL_PROJECT_DIR:-$(dirname -- "$FINAL_BINARY")}" "$(dirname -- "$FINAL_BINARY")"
printf '  Deterministic alternative: %s php-cli artisan octane:frankenphp\n' "$FINAL_BINARY"
