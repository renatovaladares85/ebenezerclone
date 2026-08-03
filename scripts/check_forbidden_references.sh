#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
RG_BIN="${RG_BIN:-rg}"

if [[ $# -gt 1 ]]; then
    echo "ERROR: expected zero or one scan directory." >&2
    exit 2
fi

SCAN_DIR="${1:-$ROOT_DIR}"
if [[ ! -d "$SCAN_DIR" ]]; then
    echo "ERROR: scan directory not found: $SCAN_DIR" >&2
    exit 2
fi

if [[ "$RG_BIN" == */* ]]; then
    if [[ ! -x "$RG_BIN" ]]; then
        echo "ERROR: ripgrep (rg) is required for the forbidden-reference scan." >&2
        exit 127
    fi
elif ! command -v "$RG_BIN" >/dev/null 2>&1; then
    echo "ERROR: ripgrep (rg) is required for the forbidden-reference scan." >&2
    exit 127
fi

term_trt='tr''t'
term_tribunal='tribu''nal'
term_regional='regi''onal'
term_trabalho='traba''lho'
term_poder='po''der'
term_judiciario='judi''ciário'
term_judiciario_plain='judi''ciario'
term_cnj='c''nj'
term_pje='p''je'
pattern="(?<![\\p{L}\\p{N}_])(${term_trt}|${term_tribunal}(?:\\s+${term_regional}\\s+do\\s+${term_trabalho})?|${term_poder}\\s+${term_judiciario}|${term_judiciario}|${term_judiciario_plain}|${term_cnj}|${term_pje})(?![\\p{L}\\p{N}_])"

set +e
"$RG_BIN" -n --hidden -i -P "$pattern" "$SCAN_DIR" -g '!.git/**'
status=$?
set -e

case "$status" in
    0)
        echo "ERROR: forbidden client references were found." >&2
        exit 1
        ;;
    1)
        echo "Forbidden-reference scan passed."
        ;;
    *)
        echo "ERROR: ripgrep failed with exit code $status." >&2
        exit "$status"
        ;;
esac
