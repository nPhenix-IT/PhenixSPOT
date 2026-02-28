#!/usr/bin/env bash
set -euo pipefail

# =========================
# CONFIG
# =========================
PROJECT_PATH="/www/wwwroot/phenixspot.com"
ENV_FILE="${PROJECT_PATH}/.env"
GEOIP_DIR="${PROJECT_PATH}/storage/app/geoip"
DB_NAME="GeoLite2-Country.mmdb"
DB_PATH="${GEOIP_DIR}/${DB_NAME}"

TMP_DIR="$(mktemp -d -t geoip_update_XXXXXX)"
ARCHIVE="${TMP_DIR}/GeoLite2-Country.tar.gz"
LOG_PREFIX="[geoip-update]"

# =========================
# HELPERS
# =========================
cleanup() {
  rm -rf "${TMP_DIR}" || true
}
trap cleanup EXIT

get_env_value() {
  # Lit une clé depuis .env en supportant quotes simples/doubles
  # Usage: get_env_value "MAXMIND_ACCOUNT_ID"
  local key="$1"
  local line
  line="$(grep -E "^${key}=" "${ENV_FILE}" | tail -n 1 || true)"
  if [[ -z "${line}" ]]; then
    echo ""
    return 0
  fi
  local val="${line#*=}"
  # enlever quotes éventuelles + espaces
  val="${val%$'\r'}"
  val="${val#\"}"; val="${val%\"}"
  val="${val#\'}"; val="${val%\'}"
  echo "${val}"
}

# =========================
# CHECKS
# =========================
if [[ ! -f "${ENV_FILE}" ]]; then
  echo "${LOG_PREFIX} ERREUR: .env introuvable: ${ENV_FILE}"
  exit 1
fi

mkdir -p "${GEOIP_DIR}"

ACCOUNT_ID="$(get_env_value "MAXMIND_ACCOUNT_ID")"
LICENSE_KEY="$(get_env_value "MAXMIND_LICENSE_KEY")"

if [[ -z "${ACCOUNT_ID}" || -z "${LICENSE_KEY}" ]]; then
  echo "${LOG_PREFIX} ERREUR: MAXMIND_ACCOUNT_ID ou MAXMIND_LICENSE_KEY manquant(s) dans .env"
  exit 1
fi

# =========================
# DOWNLOAD
# =========================
echo "${LOG_PREFIX} Téléchargement GeoLite2-Country..."
curl -fsSL -u "${ACCOUNT_ID}:${LICENSE_KEY}" \
  "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=${LICENSE_KEY}&suffix=tar.gz" \
  -o "${ARCHIVE}"

# =========================
# EXTRACT
# =========================
echo "${LOG_PREFIX} Extraction..."
tar -xzf "${ARCHIVE}" -C "${TMP_DIR}"

NEW_DB="$(find "${TMP_DIR}" -type f -name "*.mmdb" | head -n 1 || true)"
if [[ -z "${NEW_DB}" || ! -f "${NEW_DB}" ]]; then
  echo "${LOG_PREFIX} ERREUR: fichier .mmdb introuvable après extraction"
  exit 1
fi

# =========================
# ATOMIC REPLACE (backup)
# =========================
echo "${LOG_PREFIX} Remplacement de la base..."
if [[ -f "${DB_PATH}" ]]; then
  cp -f "${DB_PATH}" "${DB_PATH}.bak" || true
fi

# move atomique : copie dans un fichier temporaire puis rename
cp -f "${NEW_DB}" "${DB_PATH}.new"
chmod 0644 "${DB_PATH}.new"
mv -f "${DB_PATH}.new" "${DB_PATH}"

echo "${LOG_PREFIX} OK: base mise à jour => ${DB_PATH}"