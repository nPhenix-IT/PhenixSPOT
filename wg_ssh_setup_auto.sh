#!/usr/bin/env bash
set -euo pipefail

# PhenixSpot - WireGuard SSH Provisioning (fully automatic, no human interaction)
#
# What it does (single run, as root):
#  1) Creates the WireGuard provisioning user on THIS machine (default: phenixwg)
#  2) Generates an ed25519 keypair for the App to use (default path below)
#  3) Installs the public key into ~phenixwg/.ssh/authorized_keys (local)
#  4) Creates sudoers rule to allow ONLY wg-related commands without password
#  5) (Optional) Tests SSH locally against 127.0.0.1 and runs "sudo -n /usr/bin/wg show"
#
# IMPORTANT:
# - This script is fully automatic ONLY when your App server and WireGuard server are the SAME machine,
#   which matches your current setup (ssh to 127.0.0.1:5022).
# - If your WireGuard server is a different host, you need an initial trust/bootstrap method (existing SSH key or password).
#
# Usage:
#   sudo bash wg_ssh_setup_auto.sh
#
# Optional env vars:
#   WG_USER=phenixwg
#   WG_SSH_PORT=5022
#   APP_KEY_DIR=/www/wwwroot/phenixspot.com/storage/ssh
#   APP_KEY_NAME=wg_provision
#   RUN_TEST=1  (default 1)
#
# Output:
#   - Private key:  $APP_KEY_DIR/$APP_KEY_NAME
#   - Public key:   $APP_KEY_DIR/$APP_KEY_NAME.pub
#   - Sudoers file: /etc/sudoers.d/99-$WG_USER-wireguard

log(){ echo "[wg-ssh-setup-auto] $*"; }
die(){ echo "[wg-ssh-setup-auto] ERROR: $*" >&2; exit 1; }

need_root(){
  if [[ "${EUID}" -ne 0 ]]; then
    die "Run as root (sudo)."
  fi
}

cmd_exists(){ command -v "$1" >/dev/null 2>&1; }

WG_USER="${WG_USER:-phenixwg}"
WG_SSH_PORT="${WG_SSH_PORT:-5022}"
APP_KEY_DIR="${APP_KEY_DIR:-/www/wwwroot/phenixspot.com/storage/ssh}"
APP_KEY_NAME="${APP_KEY_NAME:-wg_provision}"
RUN_TEST="${RUN_TEST:-1}"

ensure_dirs_and_perms(){
  mkdir -p "$APP_KEY_DIR"
  chmod 700 "$APP_KEY_DIR"
}

generate_keypair(){
  local key_path="${APP_KEY_DIR%/}/${APP_KEY_NAME}"
  if [[ -f "$key_path" ]]; then
    log "Key already exists: $key_path (skipping generation)"
  else
    cmd_exists ssh-keygen || die "ssh-keygen not found"
    log "Generating ed25519 keypair: $key_path"
    ssh-keygen -t ed25519 -f "$key_path" -C "phenixspot-wireguard" -N ""
  fi

  chmod 600 "$key_path"
  chmod 644 "${key_path}.pub"

  echo "$key_path"
}

ensure_user(){
  if id "$WG_USER" >/dev/null 2>&1; then
    log "User exists: $WG_USER"
  else
    log "Creating user: $WG_USER"
    adduser --disabled-password --gecos "" "$WG_USER"
  fi
}

install_pubkey(){
  local pubkey_file="$1"
  [[ -f "$pubkey_file" ]] || die "Public key file missing: $pubkey_file"

  local home_dir
  home_dir="$(getent passwd "$WG_USER" | cut -d: -f6)"
  [[ -n "$home_dir" ]] || die "Cannot determine home for $WG_USER"

  local ssh_dir="${home_dir%/}/.ssh"
  local auth_keys="${ssh_dir}/authorized_keys"

  mkdir -p "$ssh_dir"
  chmod 700 "$ssh_dir"
  chown -R "${WG_USER}:${WG_USER}" "$ssh_dir"

  local pubkey
  pubkey="$(cat "$pubkey_file")"

  if [[ -f "$auth_keys" ]] && grep -Fq "$pubkey" "$auth_keys"; then
    log "Public key already present in $auth_keys"
  else
    log "Installing public key into $auth_keys"
    echo "$pubkey" >> "$auth_keys"
  fi

  chmod 600 "$auth_keys"
  chown "${WG_USER}:${WG_USER}" "$auth_keys"
}

write_sudoers(){
  cmd_exists visudo || die "visudo not found"

  # Resolve absolute paths
  local WG_BIN WGQ_BIN TEE_BIN RM_BIN MKTEMP_BIN
  WG_BIN="$(command -v wg || true)";        [[ -n "$WG_BIN" ]] || WG_BIN="/usr/bin/wg"
  WGQ_BIN="$(command -v wg-quick || true)"; [[ -n "$WGQ_BIN" ]] || WGQ_BIN="/usr/bin/wg-quick"
  TEE_BIN="$(command -v tee || true)";      [[ -n "$TEE_BIN" ]] || TEE_BIN="/usr/bin/tee"
  RM_BIN="$(command -v rm || true)";        [[ -n "$RM_BIN" ]] || RM_BIN="/usr/bin/rm"
  MKTEMP_BIN="$(command -v mktemp || true)";[[ -n "$MKTEMP_BIN" ]] || MKTEMP_BIN="/usr/bin/mktemp"

  local sudoers_file="/etc/sudoers.d/99-${WG_USER}-wireguard"
  log "Writing sudoers include: $sudoers_file"

  cat > "$sudoers_file" <<EOF
Defaults:${WG_USER} !requiretty
Defaults:${WG_USER} !authenticate

${WG_USER} ALL=(root) NOPASSWD: ${WG_BIN}, ${WGQ_BIN}, ${TEE_BIN}, ${RM_BIN}, ${MKTEMP_BIN}
EOF

  chmod 440 "$sudoers_file"

  # Validate sudoers syntax (non-interactive)
  if ! visudo -cf "$sudoers_file" >/dev/null; then
    rm -f "$sudoers_file" || true
    die "sudoers validation failed; file removed: $sudoers_file"
  fi

  log "Sudoers OK."
}

ensure_not_in_sudo_group(){
  # Safety: ensure the automation user is NOT in the sudo group (would grant full root)
  if id -nG "$WG_USER" | tr ' ' '\n' | grep -qx "sudo"; then
    log "User $WG_USER is in 'sudo' group; removing for safety."
    deluser "$WG_USER" sudo || true
  fi
}

run_local_test(){
  [[ "$RUN_TEST" == "1" ]] || { log "RUN_TEST=0, skipping test."; return 0; }

  local key_path="${APP_KEY_DIR%/}/${APP_KEY_NAME}"
  local WG_BIN
  WG_BIN="$(command -v wg || true)"; [[ -n "$WG_BIN" ]] || WG_BIN="/usr/bin/wg"

  log "Testing SSH locally (127.0.0.1:${WG_SSH_PORT}) + sudo wg show"
  ssh -i "$key_path" -p "$WG_SSH_PORT"       -o BatchMode=yes       -o StrictHostKeyChecking=no       -o UserKnownHostsFile=/dev/null       "${WG_USER}@127.0.0.1"       "sudo -n ${WG_BIN} show" >/dev/null

  log "Test OK."
}

main(){
  need_root
  ensure_dirs_and_perms

  ensure_user
  ensure_not_in_sudo_group

  local key_path
  key_path="$(generate_keypair)"
  install_pubkey "${key_path}.pub"
  write_sudoers
  run_local_test

  log "DONE."
  log "Private key: ${key_path}"
  log "Public key : ${key_path}.pub"
  log "Sudoers    : /etc/sudoers.d/99-${WG_USER}-wireguard"
  log "Next step: set in Laravel .env:"
  log "  WG_SSH_HOST=127.0.0.1"
  log "  WG_SSH_USER=${WG_USER}"
  log "  WG_SSH_PORT=${WG_SSH_PORT}"
  log "  WG_SSH_KEY_PATH=${key_path}"
}

main "$@"
