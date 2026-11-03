#!/usr/bin/env bash
# Build the release and run Plugin Check (static checks) on it in Playground (D-011). Fails on any error.
set -euo pipefail
cd "$(dirname "$0")/.."
bin/build.sh
RESULTS="$(mktemp -d)"
trap 'rm -rf "$RESULTS"' EXIT
cp tests/plugin-check.php "$RESULTS/"
cat > "$RESULTS/blueprint.json" <<'JSON'
{"steps":[
 {"step":"installPlugin","pluginData":{"resource":"wordpress.org/plugins","slug":"plugin-check"},"options":{"activate":true}},
 {"step":"activatePlugin","pluginPath":"custom-post-type-maker/class-cptm.php"},
 {"step":"runPHP","code":"<?php define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true ); require '/wordpress/wp-load.php'; require '/results/plugin-check.php';"}
]}
JSON
npx -y @wp-playground/cli@latest run-blueprint --wp=latest --php="${1:-8.1}" \
  --mount=dist/custom-post-type-maker:/wordpress/wp-content/plugins/custom-post-type-maker \
  --mount="$RESULTS":/results --blueprint="$RESULTS/blueprint.json" > "$RESULTS/playground.log" 2>&1 \
  || { grep -E "^Error|Fatal|Uncaught" "$RESULTS/playground.log" | head -20; echo "FAIL: Playground run failed"; exit 1; }
cat "$RESULTS/plugin-check.txt"
grep -q '^PASS' "$RESULTS/plugin-check.txt"
