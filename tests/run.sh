#!/usr/bin/env bash
# Smoke tests in a fresh WordPress Playground (D-011). Needs Node.js. Usage: tests/run.sh [php-version]
set -euo pipefail
cd "$(dirname "$0")/.."
PHP="${1:-8.1}"
RESULTS="$(mktemp -d)"
trap 'rm -rf "$RESULTS"' EXIT

npx -y @wp-playground/cli@latest run-blueprint --wp=latest --php="$PHP" \
  --mount=.:/wordpress/wp-content/plugins/custom-post-type-maker \
  --mount="$RESULTS":/results \
  --blueprint=tests/smoke/blueprint.json > "$RESULTS/playground.log" 2>&1 || { grep -E "^Error|UNCAUGHT|PHP FATAL" "$RESULTS/playground.log" | head -20; cat "$RESULTS"/*.txt 2>/dev/null; echo "FAIL: Playground run failed"; exit 1; }

status=0
for suite in smoke uninstall; do
  if [ -f "$RESULTS/$suite.txt" ]; then cat "$RESULTS/$suite.txt"; grep -q '^PASS' "$RESULTS/$suite.txt" || status=1; else echo "FAIL: no result for $suite"; status=1; fi
done
[ -s "$RESULTS/export.json" ] && echo "PASS cli: wp cptm export wrote $(wc -c < "$RESULTS/export.json") bytes" || { echo "FAIL: wp cptm export"; status=1; }
exit $status
