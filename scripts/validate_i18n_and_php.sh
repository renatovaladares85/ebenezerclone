#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
MSGFMT_BIN="${MSGFMT_BIN:-msgfmt}"
DOCKER_CONFIG_DIR="${DOCKER_CONFIG_DIR:-/tmp/.docker-codex}"

docker_cmd() {
  mkdir -p "$DOCKER_CONFIG_DIR"
  DOCKER_CONFIG="$DOCKER_CONFIG_DIR" docker "$@"
}

run_php_lint_local() {
  find "$ROOT_DIR" -type f -name '*.php' -print0 | xargs -0 -n1 "$PHP_BIN" -l >/dev/null
}

run_msgfmt_local() {
  "$MSGFMT_BIN" --check --output-file=/dev/null "$ROOT_DIR/locales/pt_BR.po"
}

run_php_lint_docker() {
  docker_cmd run --rm -v "$ROOT_DIR":/work -w /work php:8.2-cli sh -lc "find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null"
}

run_msgfmt_docker() {
  docker_cmd run --rm -v "$ROOT_DIR":/work -w /work debian:bookworm-slim sh -lc "apt-get update >/dev/null && apt-get install -y --no-install-recommends gettext >/dev/null && msgfmt --check --output-file=/dev/null /work/locales/pt_BR.po"
}

echo "[1/2] PHP lint"
if command -v "$PHP_BIN" >/dev/null 2>&1; then
  run_php_lint_local
else
  if ! command -v docker >/dev/null 2>&1; then
    echo "ERROR: php not found and docker not available." >&2
    exit 1
  fi
  run_php_lint_docker
fi

echo "[2/2] msgfmt check"
if command -v "$MSGFMT_BIN" >/dev/null 2>&1; then
  run_msgfmt_local
else
  if ! command -v docker >/dev/null 2>&1; then
    echo "ERROR: msgfmt not found and docker not available." >&2
    exit 1
  fi
  run_msgfmt_docker
fi

echo "Validation OK"
