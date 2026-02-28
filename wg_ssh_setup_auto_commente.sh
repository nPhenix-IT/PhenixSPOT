#!/usr/bin/env bash
set -euo pipefail

###############################################################################
# PhenixSpot - Configuration automatique SSH pour WireGuard
#
# OBJECTIF :
# Ce script configure automatiquement l'accès SSH sécurisé entre Laravel
# (serveur applicatif) et WireGuard (même machine dans ton cas).
#
# Il réalise automatiquement :
# 1) Création de l'utilisateur système dédié (par défaut: phenixwg)
# 2) Génération d'une clé SSH ed25519 (sans passphrase)
# 3) Installation de la clé publique dans authorized_keys
# 4) Création d'un fichier sudoers limité aux commandes WireGuard
# 5) Retrait du groupe sudo si présent (sécurité)
# 6) Test automatique SSH + sudo wg show
#
# IMPORTANT :
# Ce script fonctionne automatiquement si Laravel et WireGuard
# sont sur la même machine (127.0.0.1).
#
# EXECUTION :
#   sudo bash wg_ssh_setup_auto.sh
#
###############################################################################


########################
# Fonctions utilitaires
########################

# Affiche un message standard
log(){
  echo "[wg-ssh-setup-auto] $*"
}

# Stoppe le script en cas d'erreur
die(){
  echo "[wg-ssh-setup-auto] ERREUR: $*" >&2
  exit 1
}

# Vérifie que le script est exécuté en root
need_root(){
  if [[ "${EUID}" -ne 0 ]]; then
    die "Ce script doit être exécuté avec sudo."
  fi
}

# Vérifie si une commande existe sur le système
cmd_exists(){
  command -v "$1" >/dev/null 2>&1
}


########################
# Paramètres configurables
########################

WG_USER="${WG_USER:-phenixwg}"                       # Utilisateur système dédié
WG_SSH_PORT="${WG_SSH_PORT:-5022}"                   # Port SSH local
APP_KEY_DIR="${APP_KEY_DIR:-/www/wwwroot/phenixspot.com/storage/ssh}"  # Dossier clés Laravel
APP_KEY_NAME="${APP_KEY_NAME:-wg_provision}"         # Nom de la clé
RUN_TEST="${RUN_TEST:-1}"                            # Exécuter test final (1 = oui)


########################
# Création du dossier de clés
########################
ensure_dirs_and_perms(){
  # Création du dossier s'il n'existe pas
  mkdir -p "$APP_KEY_DIR"
  
  # Permission stricte (seul root peut lire/écrire)
  chmod 700 "$APP_KEY_DIR"
}


########################
# Génération de la paire de clés SSH
########################
generate_keypair(){

  local key_path="${APP_KEY_DIR%/}/${APP_KEY_NAME}"

  # Si la clé existe déjà, on ne la régénère pas
  if [[ -f "$key_path" ]]; then
    log "Clé déjà existante : $key_path"
  else
    cmd_exists ssh-keygen || die "ssh-keygen introuvable."
    
    log "Génération d'une clé SSH ed25519..."
    
    # Génération sans passphrase pour usage automatisé
    ssh-keygen -t ed25519 -f "$key_path" -C "phenixspot-wireguard" -N ""
  fi

  # Sécurisation permissions
  chmod 600 "$key_path"
  chmod 644 "${key_path}.pub"

  echo "$key_path"
}


########################
# Création de l'utilisateur système
########################
ensure_user(){

  # Vérifie si l'utilisateur existe
  if id "$WG_USER" >/dev/null 2>&1; then
    log "Utilisateur existant : $WG_USER"
  else
    log "Création utilisateur système : $WG_USER"
    
    # Création sans mot de passe
    adduser --disabled-password --gecos "" "$WG_USER"
  fi
}


########################
# Installation de la clé publique dans authorized_keys
########################
install_pubkey(){

  local pubkey_file="$1"
  [[ -f "$pubkey_file" ]] || die "Clé publique introuvable."

  # Récupération du home de l'utilisateur
  local home_dir
  home_dir="$(getent passwd "$WG_USER" | cut -d: -f6)"
  [[ -n "$home_dir" ]] || die "Impossible de déterminer le home utilisateur."

  local ssh_dir="${home_dir%/}/.ssh"
  local auth_keys="${ssh_dir}/authorized_keys"

  # Création dossier .ssh
  mkdir -p "$ssh_dir"
  chmod 700 "$ssh_dir"
  chown -R "${WG_USER}:${WG_USER}" "$ssh_dir"

  local pubkey
  pubkey="$(cat "$pubkey_file")"

  # Ajoute la clé si elle n'existe pas déjà
  if [[ -f "$auth_keys" ]] && grep -Fq "$pubkey" "$auth_keys"; then
    log "Clé publique déjà installée."
  else
    log "Installation clé publique dans authorized_keys"
    echo "$pubkey" >> "$auth_keys"
  fi

  chmod 600 "$auth_keys"
  chown "${WG_USER}:${WG_USER}" "$auth_keys"
}


########################
# Configuration sudo sécurisé
########################
write_sudoers(){

  # Détection chemins absolus des commandes
  local WG_BIN WGQ_BIN TEE_BIN RM_BIN MKTEMP_BIN

  WG_BIN="$(command -v wg || true)";        [[ -n "$WG_BIN" ]] || WG_BIN="/usr/bin/wg"
  WGQ_BIN="$(command -v wg-quick || true)"; [[ -n "$WGQ_BIN" ]] || WGQ_BIN="/usr/bin/wg-quick"
  TEE_BIN="$(command -v tee || true)";      [[ -n "$TEE_BIN" ]] || TEE_BIN="/usr/bin/tee"
  RM_BIN="$(command -v rm || true)";        [[ -n "$RM_BIN" ]] || RM_BIN="/usr/bin/rm"
  MKTEMP_BIN="$(command -v mktemp || true)";[[ -n "$MKTEMP_BIN" ]] || MKTEMP_BIN="/usr/bin/mktemp"

  local sudoers_file="/etc/sudoers.d/99-${WG_USER}-wireguard"

  log "Création fichier sudoers sécurisé : $sudoers_file"

  cat > "$sudoers_file" <<EOF
Defaults:${WG_USER} !requiretty
Defaults:${WG_USER} !authenticate

${WG_USER} ALL=(root) NOPASSWD: ${WG_BIN}, ${WGQ_BIN}, ${TEE_BIN}, ${RM_BIN}, ${MKTEMP_BIN}
EOF

  chmod 440 "$sudoers_file"

  # Vérification syntaxe sudoers
  visudo -cf "$sudoers_file" >/dev/null || die "Erreur syntaxe sudoers."
}


########################
# Sécurité : retirer groupe sudo si présent
########################
ensure_not_in_sudo_group(){

  # Vérifie si l'utilisateur est dans le groupe sudo
  if id -nG "$WG_USER" | tr ' ' '\n' | grep -qx "sudo"; then
    log "Retrait de l'utilisateur du groupe sudo (sécurité)"
    deluser "$WG_USER" sudo || true
  fi
}


########################
# Test automatique final
########################
run_local_test(){

  [[ "$RUN_TEST" == "1" ]] || return 0

  local key_path="${APP_KEY_DIR%/}/${APP_KEY_NAME}"
  local WG_BIN
  WG_BIN="$(command -v wg || true)"; [[ -n "$WG_BIN" ]] || WG_BIN="/usr/bin/wg"

  log "Test SSH automatique + sudo wg show"

  ssh -i "$key_path" -p "$WG_SSH_PORT"       -o BatchMode=yes       -o StrictHostKeyChecking=no       -o UserKnownHostsFile=/dev/null       "${WG_USER}@127.0.0.1"       "sudo -n ${WG_BIN} show" >/dev/null

  log "Test réussi."
}


########################
# Programme principal
########################
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

  log "Configuration terminée avec succès."
}

main "$@"
