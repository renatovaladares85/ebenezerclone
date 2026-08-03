#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
OUTPUT_DIR="$ROOT_DIR/dist"
RELEASE_TAG=""
DRY_RUN=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --output-dir)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        --tag)
            RELEASE_TAG="$2"
            shift 2
            ;;
        --dry-run)
            DRY_RUN=1
            shift
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 2
            ;;
    esac
done

if [[ -z "$RELEASE_TAG" ]]; then
    echo "A release tag is required." >&2
    exit 2
fi

VERSION="$(rg -No "PLUGIN_EBENEZERCLONE_VERSION', '[0-9]+\.[0-9]+\.[0-9]+" "$ROOT_DIR/setup.php" | sed -E "s/.*'([0-9]+\.[0-9]+\.[0-9]+)$/\1/")"
if [[ -z "$VERSION" ]]; then
    echo "Unable to read the plugin version." >&2
    exit 2
fi

if [[ "$RELEASE_TAG" != "v$VERSION" && "$RELEASE_TAG" != "$VERSION" ]]; then
    echo "Release tag does not match plugin version." >&2
    exit 2
fi

STAGING_DIR="$(mktemp -d)"
trap 'rm -rf "$STAGING_DIR"' EXIT
PACKAGE_ROOT="$STAGING_DIR/ebenezerclone"
mkdir -p "$PACKAGE_ROOT" "$PACKAGE_ROOT/front" "$PACKAGE_ROOT/inc" "$PACKAGE_ROOT/js" "$PACKAGE_ROOT/locales"

cp "$ROOT_DIR/setup.php" "$ROOT_DIR/hook.php" "$ROOT_DIR/LICENSE" "$PACKAGE_ROOT/"
cp "$ROOT_DIR/front/"*.php "$PACKAGE_ROOT/front/"
cp "$ROOT_DIR/inc/"*.php "$PACKAGE_ROOT/inc/"
cp "$ROOT_DIR/js/ebenezerclone.js" "$ROOT_DIR/js/restrict_native_clone_actions.js.php" "$PACKAGE_ROOT/js/"
cp "$ROOT_DIR/locales/pt_BR.mo" "$PACKAGE_ROOT/locales/"
cp "$ROOT_DIR/README.md" "$ROOT_DIR/CHANGELOG.md" "$PACKAGE_ROOT/"

find "$PACKAGE_ROOT" -type f -printf '%P\n' | sort > "$STAGING_DIR/files.txt"

if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "Package dry-run succeeded for ebenezerclone-$VERSION."
    cat "$STAGING_DIR/files.txt"
    exit 0
fi

mkdir -p "$OUTPUT_DIR"
ZIP_FILE="$OUTPUT_DIR/ebenezerclone-$VERSION.zip"
TAR_FILE="$OUTPUT_DIR/ebenezerclone-$VERSION.tar.gz"
MANIFEST_FILE="$OUTPUT_DIR/RELEASE-MANIFEST.json"
SUMS_FILE="$OUTPUT_DIR/SHA256SUMS"

rm -f "$ZIP_FILE" "$TAR_FILE" "$MANIFEST_FILE" "$SUMS_FILE"
(
    cd "$STAGING_DIR"
    zip -qr "$ZIP_FILE" ebenezerclone
    tar -czf "$TAR_FILE" ebenezerclone
)

sha256sum "$ZIP_FILE" "$TAR_FILE" > "$SUMS_FILE"
FILES_JSON="$(sed 's/\\/\\\\/g; s/\"/\\\"/g; s/.*/\"&\"/' "$STAGING_DIR/files.txt" | paste -sd, -)"
cat > "$MANIFEST_FILE" <<EOF
{
  "name": "ebenezerclone",
  "version": "$VERSION",
  "tag": "$RELEASE_TAG",
  "commit": "$(git -C "$ROOT_DIR" rev-parse HEAD)",
  "date_utc": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "glpi": {"min": "10.0.20", "max_exclusive": "11.0.0"},
  "files": [$FILES_JSON],
  "checksums_file": "SHA256SUMS",
  "validation": "Package generated from allowlist. Run release validation separately."
}
EOF

echo "Package generated in $OUTPUT_DIR"
