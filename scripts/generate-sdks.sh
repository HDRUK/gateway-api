#!/usr/bin/env bash
#
# Regenerates the OpenAPI spec from @OA annotations and builds the Python, C#,
# Java, Go, Rust, and TypeScript client SDKs from it. Output goes to
# sdks/<language> (git-ignored) — this script does not publish anything.
#
# Each SDK is MIT licensed, with Health Data Research UK as copyright holder.
#
# Usage: scripts/generate-sdks.sh [--skip-validate] [--version <semver>]

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SDK_LANGUAGES=(python csharp java go rust typescript)
# License year is always the current year
LICENSE_YEAR="$(date +%Y)"
LICENSE_HOLDER="Health Data Research UK"

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
  --additional-properties=packageName=GatewayApiSdk,packageVersion="$PACKAGE_VERSION",licenseId=MIT \
  $SKIP_VALIDATE

echo "==> Generating Java SDK (version $PACKAGE_VERSION)"
npx --yes @openapitools/openapi-generator-cli generate \
  -i "$SPEC" \
  -g java \
  -o "$OUT_DIR/java" \
  --git-user-id HDRUK \
  --git-repo-id gateway-api-java-sdk \
  --additional-properties=groupId=uk.ac.hdruk.gatewayapi,artifactId=gateway-api-sdk,invokerPackage=uk.ac.hdruk.gatewayapi,apiPackage=uk.ac.hdruk.gatewayapi.api,modelPackage=uk.ac.hdruk.gatewayapi.model,artifactVersion="$PACKAGE_VERSION",licenseName=MIT,licenseUrl=https://opensource.org/licenses/MIT \
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
  --additional-properties=npmName=@hdruk/gateway-api-sdk,npmVersion="$PACKAGE_VERSION",licenseName=MIT \
  $SKIP_VALIDATE

echo "==> Applying MIT license to SDKs"

# expect_license <file> <grep -E pattern>
# Fails loudly if the file is missing or does not declare MIT, so a generator
# upgrade or spec change cannot silently ship an SDK with the wrong license.
expect_license() {
  local file="$1" pattern="$2"
  if [[ ! -f "$file" ]]; then
    echo "ERROR: expected generated file not found: $file" >&2
    exit 1
  fi
  if ! grep -Eq -- "$pattern" "$file"; then
    echo "ERROR: $file does not declare MIT (expected /$pattern/)" >&2
    exit 1
  fi
}

for sdk in "${SDK_LANGUAGES[@]}"; do
  sed "s/{{YEAR}}/$LICENSE_YEAR/" scripts/templates/LICENSE.mit > "$OUT_DIR/$sdk/LICENSE"
done

# C#: the generator hardcodes <Copyright>No Copyright</Copyright> and has no
# option for it.
CSPROJ="$OUT_DIR/csharp/src/GatewayApiSdk/GatewayApiSdk.csproj"
expect_license "$CSPROJ" '<Copyright>'
perl -pi -e "s{<Copyright>[^<]*</Copyright>}{<Copyright>Copyright (c) $LICENSE_YEAR $LICENSE_HOLDER</Copyright>}" "$CSPROJ"
expect_license "$CSPROJ" "<Copyright>Copyright \\(c\\) $LICENSE_YEAR $LICENSE_HOLDER</Copyright>"

expect_license "$CSPROJ" '<PackageLicenseExpression>MIT</PackageLicenseExpression>'
expect_license "$OUT_DIR/rust/Cargo.toml" '^license = "MIT"$'
expect_license "$OUT_DIR/typescript/package.json" '"license": "MIT"'
expect_license "$OUT_DIR/java/pom.xml" '<name>MIT</name>'
expect_license "$OUT_DIR/python/pyproject.toml" '^license = \{ text = "MIT" \}$'
expect_license "$OUT_DIR/python/setup.py" '^    license="MIT",$'

# Nothing should still claim a generator default.
if grep -rIl -E 'Unlicense|unlicense\.org|No Copyright|NoLicense' \
     --include=Cargo.toml --include=package.json --include=pom.xml \
     --include='*.csproj' --include=pyproject.toml --include=setup.py \
     "$OUT_DIR" | grep -q .; then
  echo "ERROR: SDK metadata still references a generator default license" >&2
  exit 1
fi

echo "==> Done. SDKs written to $OUT_DIR/{$(IFS=,; echo "${SDK_LANGUAGES[*]}")}"
