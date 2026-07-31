#!/usr/bin/env bash
#
# Regenerates the OpenAPI spec from @OA annotations and builds the Python and
# C# client SDKs from it. Output goes to sdks/python and sdks/csharp
# (git-ignored) — this script does not publish anything.
#
# Usage: scripts/generate-sdks.sh [--skip-validate] [--version <semver>]

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SKIP_VALIDATE=""
VERSION="0.0.0-local"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-validate)
      SKIP_VALIDATE="--skip-validate-spec"
      shift
      ;;
    --version)
      VERSION="$2"
      shift 2
      ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
done

# Strip a leading "v" (e.g. a git tag like v1.2.3) — PyPI/NuGet want plain semver.
PACKAGE_VERSION="${VERSION#v}"

echo "==> Regenerating OpenAPI spec (php artisan l5-swagger:generate)"
php artisan l5-swagger:generate

SPEC="storage/api-docs/api-docs.json"
OUT_DIR="sdks"
rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR"

echo "==> Generating Python SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g python \
  -o "$OUT_DIR/python" \
  --package-name gateway_api_sdk \
  --additional-properties=packageVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Generating C# SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g csharp \
  -o "$OUT_DIR/csharp" \
  --additional-properties=packageName=GatewayApiSdk,packageVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Done. SDKs written to $OUT_DIR/python and $OUT_DIR/csharp"
