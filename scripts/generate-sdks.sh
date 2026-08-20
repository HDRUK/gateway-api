#!/usr/bin/env bash
#
# Regenerates the OpenAPI spec from @OA annotations and builds the Python, C#,
# Java, Go, Rust, and TypeScript client SDKs from it. Output goes to
# sdks/<language> (git-ignored) — this script does not publish anything.
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

echo "==> Stripping internal-only endpoints (x-internal) from the spec"
php artisan app:strip-internal-endpoints

SPEC="storage/api-docs/api-docs.json"
OUT_DIR="sdks"
rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR"

echo "==> Generating Python SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g python \
  -o "$OUT_DIR/python" \
  --git-user-id HDRUK \
  --git-repo-id gateway-api-python-sdk \
  --package-name gateway_api_sdk \
  --additional-properties=packageVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Generating C# SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g csharp \
  -o "$OUT_DIR/csharp" \
  --git-user-id HDRUK \
  --git-repo-id gateway-api-csharp-sdk \
  --additional-properties=packageName=GatewayApiSdk,packageVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Generating Java SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g java \
  -o "$OUT_DIR/java" \
  --git-user-id HDRUK \
  --git-repo-id gateway-api-java-sdk \
  --additional-properties=groupId=uk.ac.hdruk.gatewayapi,artifactId=gateway-api-sdk,invokerPackage=uk.ac.hdruk.gatewayapi,apiPackage=uk.ac.hdruk.gatewayapi.api,modelPackage=uk.ac.hdruk.gatewayapi.model,artifactVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Generating Go SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g go \
  -o "$OUT_DIR/go" \
  --git-user-id HDRUK \
  --git-repo-id gateway-api-go-sdk \
  --additional-properties=packageName=gatewayapisdk,packageVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Generating Rust SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g rust \
  -o "$OUT_DIR/rust" \
  --git-user-id HDRUK \
  --git-repo-id gateway-api-rust-sdk \
  --additional-properties=packageName=gateway-api-sdk,packageVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Generating TypeScript SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g typescript-axios \
  -o "$OUT_DIR/typescript" \
  --git-user-id HDRUK \
  --git-repo-id gateway-api-typescript-sdk \
  --additional-properties=npmName=@hdruk/gateway-api-sdk,npmVersion="$PACKAGE_VERSION" \
  $SKIP_VALIDATE

echo "==> Done. SDKs written to $OUT_DIR/{python,csharp,java,go,rust,typescript}"
