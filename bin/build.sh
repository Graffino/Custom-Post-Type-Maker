#!/usr/bin/env bash
# Build the release folder dist/custom-post-type-maker (and a zip) from the working tree, without the files in .distignore.
set -euo pipefail
cd "$(dirname "$0")/.."
OUT=dist/custom-post-type-maker
rm -rf dist && mkdir -p "$OUT"
rsync -a --exclude-from=.distignore ./ "$OUT/"
(cd dist && zip -qr custom-post-type-maker.zip custom-post-type-maker)
echo "Built $OUT ($(find "$OUT" -type f | wc -l | tr -d ' ') files) and dist/custom-post-type-maker.zip"
