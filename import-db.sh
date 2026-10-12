#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# Usage
# =============================================================================
#
# Required:
#   --pod        <name>    Kubernetes pod name           (e.g. mysql-0)
#   --container  <name>    Container name inside the pod (e.g. mysql)
#   --file       <path>    Path to the .sql dump file
#
# Optional:
#   --target-db  <name>    Load only this database (uses --one-database).
#                          Omit to load ALL databases from the file.
#   --db-name    <name>    Database to DROP and recreate before import.
#                          Required when --target-db is set.
#
# Example:
#   ./import-db.sh \
#     --pod mysql-0 \
#     --container mysql \
#     --file '/path/to/Cloud_SQL_Export.sql' \
#     --target-db hdr-cohort-prod \
#     --db-name hdr-cohort-prod
#
# =============================================================================

POD=""
CONTAINER=""
SQL_FILE=""
TARGET_DB=""
DB_NAME=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --pod)        POD="$2";        shift 2 ;;
    --container)  CONTAINER="$2";  shift 2 ;;
    --file)       SQL_FILE="$2";   shift 2 ;;
    --target-db)  TARGET_DB="$2";  shift 2 ;;
    --db-name)    DB_NAME="$2";    shift 2 ;;
    *)
      echo "Unknown argument: $1"
      echo "Run with no arguments to see usage."
      exit 1
      ;;
  esac
done

if [[ -z "${POD}" || -z "${CONTAINER}" || -z "${SQL_FILE}" ]]; then
  echo "Error: --pod, --container, and --file are required."
  echo ""
  sed -n '/^# Usage/,/^# ===/p' "$0" | head -n -1
  exit 1
fi

NAMESPACE="default"
STATEFULSET="mysql"

MYSQL_USER="root"
MYSQL_PASSWORD="nEun5ugNrX"

MAX_ALLOWED_PACKET="1073741824" # 1 GiB

# Temporary import resources
IMPORT_CPU_REQUEST="1"
IMPORT_CPU_LIMIT="4"
IMPORT_MEMORY_REQUEST="4Gi"
IMPORT_MEMORY_LIMIT="8Gi"
IMPORT_STORAGE_REQUEST="10Gi"
IMPORT_STORAGE_LIMIT="40Gi"

# Original resources to restore afterwards
ORIGINAL_CPU_REQUEST="500m"
ORIGINAL_CPU_LIMIT="750m"
ORIGINAL_MEMORY_REQUEST="768Mi"
ORIGINAL_MEMORY_LIMIT="768Mi"
ORIGINAL_STORAGE_REQUEST="1Gi"
ORIGINAL_STORAGE_LIMIT="1Gi"

# =============================================================================
# Helpers
# =============================================================================

wait_for_pod_ready() {
  echo "Waiting for pod ${POD} to be ready..."
  kubectl wait \
    --for=condition=ready \
    pod/"${POD}" \
    -n "${NAMESPACE}" \
    --timeout=300s
}

patch_mysql_resources() {
  local cpu_request="$1"
  local cpu_limit="$2"
  local memory_request="$3"
  local memory_limit="$4"
  local storage_request="$5"
  local storage_limit="$6"

  echo "Patching ${STATEFULSET} resources..."
  kubectl patch statefulset "${STATEFULSET}" -n "${NAMESPACE}" --type='json' -p="[
    {
      \"op\": \"replace\",
      \"path\": \"/spec/template/spec/containers/0/resources\",
      \"value\": {
        \"requests\": {
          \"cpu\": \"${cpu_request}\",
          \"memory\": \"${memory_request}\",
          \"ephemeral-storage\": \"${storage_request}\"
        },
        \"limits\": {
          \"cpu\": \"${cpu_limit}\",
          \"memory\": \"${memory_limit}\",
          \"ephemeral-storage\": \"${storage_limit}\"
        }
      }
    }
  ]"

  echo "Restarting ${STATEFULSET}..."
  kubectl rollout restart statefulset/"${STATEFULSET}" -n "${NAMESPACE}"
  kubectl rollout status statefulset/"${STATEFULSET}" -n "${NAMESPACE}" --timeout=300s
  wait_for_pod_ready
}

restore_original_limits() {
  echo "Restoring original MySQL pod limits..."
  patch_mysql_resources \
    "${ORIGINAL_CPU_REQUEST}" \
    "${ORIGINAL_CPU_LIMIT}" \
    "${ORIGINAL_MEMORY_REQUEST}" \
    "${ORIGINAL_MEMORY_LIMIT}" \
    "${ORIGINAL_STORAGE_REQUEST}" \
    "${ORIGINAL_STORAGE_LIMIT}"
}

# Restore limits even if the import fails
trap restore_original_limits EXIT

# =============================================================================
# Pre-flight checks
# =============================================================================

if ! command -v pv >/dev/null 2>&1; then
  echo "pv is not installed. Install with: brew install pv"
  exit 1
fi

if [[ ! -f "${SQL_FILE}" ]]; then
  echo "SQL file not found: ${SQL_FILE}"
  exit 1
fi

echo "============================================="
echo "  SQL file   : ${SQL_FILE}"
if [[ -n "${TARGET_DB}" ]]; then
  echo "  Target DB  : ${TARGET_DB} (--one-database)"
  echo "  Will drop  : ${DB_NAME}"
else
  echo "  Target DB  : ALL databases in the file"
fi
echo "  Pod        : ${NAMESPACE}/${POD}"
echo "============================================="
echo ""
read -r -p "Proceed? [y/N] " confirm
[[ "${confirm}" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 0; }

# =============================================================================
# 1. Increase pod limits for the duration of the import
# =============================================================================

patch_mysql_resources \
  "${IMPORT_CPU_REQUEST}" \
  "${IMPORT_CPU_LIMIT}" \
  "${IMPORT_MEMORY_REQUEST}" \
  "${IMPORT_MEMORY_LIMIT}" \
  "${IMPORT_STORAGE_REQUEST}" \
  "${IMPORT_STORAGE_LIMIT}"

# =============================================================================
# 2. Set MySQL packet size
# =============================================================================

echo "Setting max_allowed_packet..."
kubectl exec -i -n "${NAMESPACE}" "${POD}" -c "${CONTAINER}" -- \
  sh -c "MYSQL_PWD='${MYSQL_PASSWORD}' mysql -u '${MYSQL_USER}' -e \
    'SET GLOBAL max_allowed_packet=${MAX_ALLOWED_PACKET};'"

# =============================================================================
# 3. Drop and recreate the target database (only when loading a specific DB)
# =============================================================================

if [[ -n "${TARGET_DB}" ]]; then
  echo "Dropping and recreating database ${DB_NAME}..."
  kubectl exec -i -n "${NAMESPACE}" "${POD}" -c "${CONTAINER}" -- \
    sh -c "MYSQL_PWD='${MYSQL_PASSWORD}' mysql -u '${MYSQL_USER}' -e \"
DROP DATABASE IF EXISTS \\\`${DB_NAME}\\\`;
CREATE DATABASE \\\`${DB_NAME}\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
\""
fi

# =============================================================================
# 4. Stream the SQL dump into the pod
# =============================================================================

echo "Starting import (this will take a while for a large file)..."

if [[ -n "${TARGET_DB}" ]]; then
  # Load only the TARGET_DB — mysql ignores USE statements for other databases
  pv "${SQL_FILE}" | kubectl exec -i -n "${NAMESPACE}" "${POD}" -c "${CONTAINER}" -- \
    sh -c "MYSQL_PWD='${MYSQL_PASSWORD}' mysql \
      -u '${MYSQL_USER}' \
      --one-database \
      --max_allowed_packet=1G \
      --init-command='SET SESSION foreign_key_checks=0; SET SESSION unique_checks=0;' \
      '${TARGET_DB}'"
else
  # Load all databases from the file
  pv "${SQL_FILE}" | kubectl exec -i -n "${NAMESPACE}" "${POD}" -c "${CONTAINER}" -- \
    sh -c "MYSQL_PWD='${MYSQL_PASSWORD}' mysql \
      -u '${MYSQL_USER}' \
      --max_allowed_packet=1G \
      --init-command='SET SESSION foreign_key_checks=0; SET SESSION unique_checks=0;'"
fi

echo ""
echo "Import complete."

# =============================================================================
# 5. Trap will restore original limits on exit
# =============================================================================

trap - EXIT
restore_original_limits

echo ""
echo "Done. Verify with:"
echo "  kubectl exec -i ${POD} -n ${NAMESPACE} -- \\"
echo "    sh -c \"MYSQL_PWD='${MYSQL_PASSWORD}' mysql -u ${MYSQL_USER} -e \\"
echo "    'SELECT table_schema, COUNT(*) AS tables FROM information_schema.tables GROUP BY table_schema;'\""
