#!/usr/bin/env bash
set -euo pipefail

# PhenixSpot - WireGuard Provisioner SSH setup
# Usage:
#   1) On the Laravel/App server (to generate SSH keypair):
#        sudo bash wg_ssh_setup.sh app --key-dir /www/wwwroot/phenixspot.com/storage/ssh --key-name wg_provision
#
#   2) Copy the public key:
#        cat /www/wwwroot/phenixspot.com/storage/ssh/wg_provision.pub
#
#   3) On the WireGuard server (to create user + sudoers + install pubkey):
#        sudo bash wg_ssh_setup.sh wg --user phenixwg --pubkey-file /path/to/wg_provision.pub
#      OR (paste pubkey directly):
#        sudo bash wg_ssh_setup.sh wg --user phenixwg --pubkey "ssh-ed25519 AAAA... phenixspot-wireguard"
#
# Notes:
# - This config allows ONLY these passwordless sudo commands for the user:
#   /usr/bin/wg, /usr/bin/wg-quick, /usr/bin/tee, /usr/bin/rm, /usr/bin/mktemp
# - It does NOT add the user to the sudo group.

log(){ echo "[wg-ssh-setup] $*"; }
die(){ echo "[wg-ssh-setup] ERROR: $*" >&2; exit 1; }

need_root(){
  if [[ "${EUID}" -ne 0 ]]; then
    die "Run as root (sudo)."
  fi
}

cmd_exists(){ command -v "$1" >/dev/null 2>&1; }

ensure_dir(){
  local d="$1"
  mkdir -p "$d"
}

sub_app(){
  need_root

  local key_dir=""
  local key_name="wg_provision"

  while [[ $# -gt 0 ]]; do
    case "$1" in
      --key-dir) key_dir="$2"; shift 2;;
      --key-name) key_name="$2"; shift 2;;
      *) die "Unknown arg for 'app': $1";;
    esac
  done

  [[ -n "$key_dir" ]] || die "Missing --key-dir"

  ensure_dir "$key_dir"
  chmod 700 "$key_dir"

  local key_path="${key_dir%/}/${key_name}"

  if [[ -f "$key_path" ]]; then
    log "Key already exists: $key_path (skipping generation)"
  else
    cmd_exists ssh-keygen || die "ssh-keygen not found"
    log "Generating ed25519 key: $key_path"
    # Empty passphrase for non-interactive automation
    ssh-keygen -t ed25519 -f "$key_path" -C "phenixspot-wireguard" -N ""
  fi

  chmod 600 "$key_path"
  chmod 644 "${key_path}.pub"

  log "Done."
  log "Private key: $key_path"
  log "Public  key: ${key_path}.pub"
  log "Next: copy the public key and run this script on the WireGuard server (wg mode)."
}

sub_wg(){
  need_root

  local user="phenixwg"
  local pubkey=""
  local pubkey_file=""
  local ssh_home=""

  while [[ $# -gt 0 ]]; do
    case "$1" in
      --user) user="$2"; shift 2;;
      --pubkey) pubkey="$2"; shift 2;;
      --pubkey-file) pubkey_file="$2"; shift 2;;
      --home) ssh_home="$2"; shift 2;;
      *) die "Unknown arg for 'wg': $1";;
    esac
  done

  if [[ -n "$pubkey_file" ]]; then
    [[ -f "$pubkey_file" ]] || die "pubkey file not found: $pubkey_file"
    pubkey="$(cat "$pubkey_file")"
  fi
  [[ -n "$pubkey" ]] || die "Provide --pubkey or --pubkey-file"

  # Create user if missing
  if id "$user" >/dev/null 2>&1; then
    log "User exists: $user"
  else
    log "Creating user: $user"
    adduser --disabled-password --gecos "" "$user"
  fi

  # Determine home
  if [[ -z "$ssh_home" ]]; then
    ssh_home="$(getent passwd "$user" | cut -d: -f6)"
    [[ -n "$ssh_home" ]] || die "Cannot determine home for $user"
  fi

  local ssh_dir="${ssh_home%/}/.ssh"
  local auth_keys="${ssh_dir}/authorized_keys"

  ensure_dir "$ssh_dir"
  chmod 700 "$ssh_dir"
  chown -R "${user}:${user}" "$ssh_dir"

  # Append pubkey if not present
  if [[ -f "$auth_keys" ]] && grep -Fq "$pubkey" "$auth_keys"; then
    log "Public key already present in $auth_keys"
  else
    log "Installing public key into $auth_keys"
    echo "$pubkey" >> "$auth_keys"
  fi

  chmod 600 "$auth_keys"
  chown "${user}:${user}" "$auth_keys"

  # Create dedicated sudoers include
  local sudoers_file="/etc/sudoers.d/99-${user}-wireguard"
  log "Writing sudoers rule: $sudoers_file"

  # Resolve absolute paths
  local WG_BIN WGQ_BIN TEE_BIN RM_BIN MKTEMP_BIN
  WG_BIN="$(command -v wg || true)";        [[ -n "$WG_BIN" ]] || WG_BIN="/usr/bin/wg"
  WGQ_BIN="$(command -v wg-quick || true)"; [[ -n "$WGQ_BIN" ]] || WGQ_BIN="/usr/bin/wg-quick"
  TEE_BIN="$(command -v tee || true)";      [[ -n "$TEE_BIN" ]] || TEE_BIN="/usr/bin/tee"
  RM_BIN="$(command -v rm || true)";        [[ -n "$RM_BIN" ]] || RM_BIN="/usr/bin/rm"
  MKTEMP_BIN="$(command -v mktemp || true)";[[ -n "$MKTEMP_BIN" ]] || MKTEMP_BIN="/usr/bin/mktemp"

  cat > "$sudoers_file" <<EOF
Defaults:${user} !requiretty
Defaults:${user} !authenticate

${user} ALL=(root) NOPASSWD: ${WG_BIN}, ${WGQ_BIN}, ${TEE_BIN}, ${RM_BIN}, ${MKTEMP_BIN}
EOF

  chmod 440 "$sudoers_file"

  log "Done."
  log "Test from the App server:"
  log "  ssh -i <private_key> -p <port> ${user}@<wg_host> \"sudo -n ${WG_BIN} show\""
}

main(){
  if [[ $# -lt 1 ]]; then
    cat <<'USAGE'
Usage:
  wg_ssh_setup.sh app --key-dir <dir> [--key-name <name>]
  wg_ssh_setup.sh wg  [--user <user>] (--pubkey "<ssh-ed25519 ...>" | --pubkey-file <file>) [--home <home>]
USAGE
    exit 1
  fi

  local mode="$1"; shift
  case "$mode" in
    app) sub_app "$@";;
    wg)  sub_wg "$@";;
    *) die "Unknown mode: $mode (use: app|wg)";;
  esac
}

main "$@"
