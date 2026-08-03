#!/usr/bin/env bash
set -euo pipefail

if [[ $# -gt 1 ]]; then
    echo "ERROR: expected zero or one setup.php path." >&2
    exit 2
fi

SETUP_FILE="${1:-setup.php}"
if [[ ! -f "$SETUP_FILE" ]]; then
    echo "ERROR: setup.php file not found: $SETUP_FILE" >&2
    exit 2
fi

mapfile -t versions < <(
    sed -nE "s/^[[:space:]]*define[[:space:]]*\([[:space:]]*'PLUGIN_EBENEZERCLONE_VERSION'[[:space:]]*,[[:space:]]*'([0-9]+\.[0-9]+\.[0-9]+)'[[:space:]]*\)[[:space:]]*;[[:space:]]*$/\1/p" "$SETUP_FILE"
)

if [[ "${#versions[@]}" -ne 1 ]]; then
    echo "ERROR: expected exactly one valid PLUGIN_EBENEZERCLONE_VERSION declaration in $SETUP_FILE." >&2
    exit 2
fi

printf '%s\n' "${versions[0]}"
