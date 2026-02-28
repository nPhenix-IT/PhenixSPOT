#!/usr/bin/env bash
set -euo pipefail

###############################################################################
# PhenixSpot - Script d'installation GLOBAL (1 seule commande)
# - WireGuard (paquets + IP forwarding + UFW + user SSH provision)
# - FreeRADIUS (paquets + déploiement des configs fournies + activation modules)
# - SSH (clé + user dédié + sudo limité pour provision WireGuard)
# - UFW (règles minimales)
#
# ✅ Conçu pour Ubuntu 24.x avec FreeRADIUS 3.0
#
# Usage:
#   sudo bash phenixspot_install.sh
#
# Variables optionnelles:
#   WG_USER=phenixwg
#   WG_SSH_PORT=5022
#   APP_KEY_DIR=/www/wwwroot/phenixspot.com/storage/ssh
#   APP_KEY_NAME=wg_provision
#   WG_UDP_PORT=51820
#   RUN_TEST=1
#
# IMPORTANT:
# - Ce script N'INSTALLE PAS ton wg0.conf (il dépend de ta topologie).
#   Il prépare l'OS + firewall + permissions + FreeRADIUS + configs.
###############################################################################

log() { echo "[phenixspot-install] $*"; }
die() { echo "[phenixspot-install] ERREUR: $*" >&2; exit 1; }

need_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    die "Exécute ce script avec sudo."
  fi
}

cmd_exists() { command -v "$1" >/dev/null 2>&1; }

timestamp() { date +"%Y%m%d-%H%M%S"; }

# ---- Variables (modifiable via env) ----
WG_USER="${WG_USER:-phenixwg}"
WG_SSH_PORT="${WG_SSH_PORT:-5022}"
APP_KEY_DIR="${APP_KEY_DIR:-/www/wwwroot/phenixspot.com/storage/ssh}"
APP_KEY_NAME="${APP_KEY_NAME:-wg_provision}"
WG_UDP_PORT="${WG_UDP_PORT:-51820}"
RUN_TEST="${RUN_TEST:-1}"

# ---- Paths FreeRADIUS 3.0 ----
FR_BASE="/etc/freeradius/3.0"
FR_CLIENTS="${FR_BASE}/clients.conf"
FR_MODS_AV="${FR_BASE}/mods-available"
FR_MODS_EN="${FR_BASE}/mods-enabled"
FR_SITES_AV="${FR_BASE}/sites-available"
FR_SITES_EN="${FR_BASE}/sites-enabled"
FR_COUNTER_DIR="${FR_BASE}/mods-config/sql/counter"

backup_file() {
  local path="$1"
  if [[ -f "$path" ]]; then
    local bak="${path}.bak.$(timestamp)"
    cp -a "$path" "$bak"
    log "Backup: $path -> $bak"
  fi
}

write_file_root() {
  # Ecrit un contenu (stdin) dans un fichier, en créant le dossier si besoin
  local dest="$1"
  mkdir -p "$(dirname "$dest")"
  cat > "$dest"
}

chown_freerad_if_exists() {
  # Sur Ubuntu, le user/group FreeRADIUS est souvent "freerad".
  # On adapte si le user existe.
  local path="$1"
  if id freerad >/dev/null 2>&1; then
    chown freerad:freerad "$path" || true
    chmod 640 "$path" || true
  fi
}

install_packages() {
  log "Mise à jour apt + installation des paquets…"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y

  # WireGuard + UFW + OpenSSH server (au cas où)
  apt-get install -y wireguard ufw openssh-server

  # FreeRADIUS + modules demandés
  apt-get install -y freeradius freeradius-mysql freeradius-utils freeradius-rest
}

enable_ip_forwarding() {
  log "Activation IP forwarding (IPv4)…"
  sysctl -w net.ipv4.ip_forward=1 >/dev/null

  # Persistance
  if ! grep -q "^net.ipv4.ip_forward=1" /etc/sysctl.conf 2>/dev/null; then
    echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
  fi
}

setup_ufw() {
  log "Configuration UFW…"

  # Autoriser SSH (port custom) en local et/ou général selon ton choix.
  # Ici: on autorise le port SSH configuré (par défaut 5022).
  ufw allow "${WG_SSH_PORT}/tcp" >/dev/null || true

  # WireGuard
  ufw allow "${WG_UDP_PORT}/udp" >/dev/null || true

  # FreeRADIUS (auth + accounting)
  ufw allow 1812/udp >/dev/null || true
  ufw allow 1813/udp >/dev/null || true

  # Activer UFW si pas déjà actif (sans prompt)
  ufw --force enable >/dev/null || true
  ufw status verbose || true
}

ensure_dirs_and_perms_for_keys() {
  # Dossier de clés SSH côté application
  mkdir -p "$APP_KEY_DIR"
  chmod 700 "$APP_KEY_DIR"
}

ensure_wg_user() {
  if id "$WG_USER" >/dev/null 2>&1; then
    log "Utilisateur existe: $WG_USER"
  else
    log "Création utilisateur: $WG_USER"
    adduser --disabled-password --gecos "" "$WG_USER"
  fi

  # Sécurité: s'assurer qu'il n'est pas dans le groupe sudo
  if id -nG "$WG_USER" | tr ' ' '\n' | grep -qx "sudo"; then
    log "Retrait de $WG_USER du groupe sudo (sécurité)."
    deluser "$WG_USER" sudo || true
  fi
}

generate_ssh_keypair() {
  local key_path="${APP_KEY_DIR%/}/${APP_KEY_NAME}"

  if [[ -f "$key_path" ]]; then
    log "Clé SSH déjà présente: $key_path"
  else
    cmd_exists ssh-keygen || die "ssh-keygen introuvable"
    log "Génération clé SSH ed25519: $key_path"
    ssh-keygen -t ed25519 -f "$key_path" -C "phenixspot-wireguard" -N ""
  fi

  chmod 600 "$key_path"
  chmod 644 "${key_path}.pub"

  echo "$key_path"
}

install_pubkey_local() {
  # Installe la clé publique dans ~WG_USER/.ssh/authorized_keys (local machine)
  local pubkey_file="$1"
  [[ -f "$pubkey_file" ]] || die "Clé publique introuvable: $pubkey_file"

  local home_dir
  home_dir="$(getent passwd "$WG_USER" | cut -d: -f6)"
  [[ -n "$home_dir" ]] || die "Impossible de déterminer le home de $WG_USER"

  local ssh_dir="${home_dir%/}/.ssh"
  local auth_keys="${ssh_dir}/authorized_keys"

  mkdir -p "$ssh_dir"
  chmod 700 "$ssh_dir"
  chown -R "${WG_USER}:${WG_USER}" "$ssh_dir"

  local pubkey
  pubkey="$(cat "$pubkey_file")"

  if [[ -f "$auth_keys" ]] && grep -Fq "$pubkey" "$auth_keys"; then
    log "Clé publique déjà installée dans authorized_keys."
  else
    log "Installation clé publique dans $auth_keys"
    echo "$pubkey" >> "$auth_keys"
  fi

  chmod 600 "$auth_keys"
  chown "${WG_USER}:${WG_USER}" "$auth_keys"
}

write_sudoers_wireguard() {
  # Permet à l'utilisateur WG_USER d'exécuter uniquement des commandes wg en sudo, sans mot de passe
  cmd_exists visudo || die "visudo introuvable"

  local WG_BIN WGQ_BIN TEE_BIN RM_BIN MKTEMP_BIN
  WG_BIN="$(command -v wg || true)";        [[ -n "$WG_BIN" ]] || WG_BIN="/usr/bin/wg"
  WGQ_BIN="$(command -v wg-quick || true)"; [[ -n "$WGQ_BIN" ]] || WGQ_BIN="/usr/bin/wg-quick"
  TEE_BIN="$(command -v tee || true)";      [[ -n "$TEE_BIN" ]] || TEE_BIN="/usr/bin/tee"
  RM_BIN="$(command -v rm || true)";        [[ -n "$RM_BIN" ]] || RM_BIN="/usr/bin/rm"
  MKTEMP_BIN="$(command -v mktemp || true)";[[ -n "$MKTEMP_BIN" ]] || MKTEMP_BIN="/usr/bin/mktemp"

  local sudoers_file="/etc/sudoers.d/99-${WG_USER}-wireguard"
  log "Création sudoers limité: $sudoers_file"

  cat > "$sudoers_file" <<EOF
Defaults:${WG_USER} !requiretty
Defaults:${WG_USER} !authenticate

${WG_USER} ALL=(root) NOPASSWD: ${WG_BIN}, ${WGQ_BIN}, ${TEE_BIN}, ${RM_BIN}, ${MKTEMP_BIN}
EOF

  chmod 440 "$sudoers_file"

  # Validation
  visudo -cf "$sudoers_file" >/dev/null || die "Erreur de syntaxe sudoers: $sudoers_file"
}

deploy_freeradius_configs() {
  log "Déploiement des configurations FreeRADIUS (PhenixSpot)…"

  # Arrêt service pour éviter reload pendant copie
  systemctl stop freeradius >/dev/null || true

  # Backups
  backup_file "$FR_CLIENTS"
  backup_file "${FR_MODS_AV}/rest"
  backup_file "${FR_MODS_AV}/sql"
  backup_file "${FR_MODS_AV}/sqlcounter"
  backup_file "${FR_SITES_AV}/default"

  # --- clients.conf ---
  write_file_root "$FR_CLIENTS" <<'EOF_CLIENTS'
# -*- text -*-
##
## clients.conf -- client configuration directives
##
##	$Id: 5f39ff120a44e3fb837d1893d54a7ebb31fd8749 $

#######################################################################
#
#  Define RADIUS clients (usually a NAS, Access Point, etc.).

#
#  There are a number of security practices which are critical in the
#  modern era.
#
#  * don't use RADIUS/UDP or RADIUS/TCP over the Internet.  Use RADIUS/TLS.
#
#  * If you do send RADIUS over UDP or TCP, don't send MS-CHAPv2.
#    Anyone who can see the MS-CHAPv2 data can crack it in milliseconds.
#
#  * use the "radsecret" program to generate secrets.  It uses Perl (sorry).
#    Every time you run it, it will generate a new strong secret.
#
#  * don't create shared secrets yourself.  Anything you create is likely to
#    be in a "cracking" dictionary, and will allow a hobbyist attacker
#    to crack the shared secret in a few minutes.
#
#  * Don't trust anyone who tells you to ignore the above recommendations.
#

#
#  Defines a RADIUS client.
#
#  '127.0.0.1' is another name for 'localhost'.  It is enabled by default,
#  to allow testing of the server after an initial installation.  If you
#  are not going to be permitting RADIUS queries from localhost, we suggest
#  that you delete, or comment out, this entry.
#
#

#
#  Each client has a "short name" that is used to distinguish it from
#  other clients.
#
#  In version 1.x, the string after the word "client" was the IP
#  address of the client.  In 2.0, the IP address is configured via
#  the "ipaddr" or "ipv6addr" fields.  For compatibility, the 1.x
#  format is still accepted.
#
client localhost {
	#  Only *one* of ipaddr, ipv4addr, ipv6addr may be specified for
	#  a client.
	#
	#  ipaddr will accept IPv4 or IPv6 addresses with optional CIDR
	#  notation '/<mask>' to specify ranges.
	#
	#  ipaddr will accept domain names e.g. example.org resolving
	#  them via DNS.
	#
	#  If both A and AAAA records are found, A records will be
	#  used in preference to AAAA.
	ipaddr = 127.0.0.1

	#  Same as ipaddr but allows v4 addresses only. Requires A
	#  record for domain names.
#	ipv4addr = *	# any.  127.0.0.1 == localhost

	#  Same as ipaddr but allows v6 addresses only. Requires AAAA
	#  record for domain names.
#	ipv6addr = ::	# any.  ::1 == localhost

	#
	#  A note on DNS:  We STRONGLY recommend using IP addresses
	#  rather than host names.  Using host names means that the
	#  server will do DNS lookups when it starts, making it
	#  dependent on DNS.  i.e. If anything goes wrong with DNS,
	#  the server won't start!
	#
	#  The server also looks up the IP address from DNS once, and
	#  only once, when it starts.  If the DNS record is later
	#  updated, the server WILL NOT see that update.
	#

	#
	#  The transport protocol.
	#
	#  If unspecified, defaults to "udp", which is the traditional
	#  RADIUS transport.  It may also be "tcp", in which case the
	#  server will accept connections from this client ONLY over TCP.
	#
	proto = *

	#
	#  The shared secret use to "encrypt" and "sign" packets between
	#  the NAS and FreeRADIUS.  You MUST change this secret from the
	#  default, otherwise it's not a secret any more!
	#
	#  The secret can be any string, up to 8k characters in length.
	#
	#  Control codes can be entered vi octal encoding,
	#	e.g. "\101\102" == "AB"
	#  Quotation marks can be entered by escaping them,
	#	e.g. "foo\"bar"
	#
	#  A note on security: The security of the RADIUS protocol
	#  depends COMPLETELY on this secret!  We recommend using a
	#  shared secret that at LEAST 16 characters long.  It should
	#  preferably be 32 characters in length.  The secret MUST be
	#  random, and should not be words, phrase, or anything else
	#  that is recognisable.
	#
	#  Computing power has increased enormously since RADIUS was
	#  first defined.  A hobbyist with a high-end GPU can try ALL
	#  of the 8-character shared secrets in about a day.  The
	#  security of shared secrets increases MUCH more with the
	#  length of the shared secret, than with number of different
	#  characters used in it.  So don't bother trying to use
	#  "special characters" or anything else in an attempt to get
	#  un-guessable secrets.  Instead, just get data from a secure
	#  random number generator, and use that.
	#
	#  You should create shared secrets using a method like this:
	#
	#	dd if=/dev/random bs=1 count=24 | base64
	#
	#  This process will give output which takes 24 random bytes,
	#  and converts them to 32 characters of ASCII.  The output
	#  should be accepted by all RADIUS clients.
	#
	#  You should NOT create shared secrets by hand.  They will
	#  not be random.  They will will be trivial to crack.
	#
	#  The default secret below is only for testing, and should
	#  not be used in any real environment.
	#
	secret = Rx7Lp9Ta2Zk4

	#
	#  The global configuration "security.require_message_authenticator"
	#  flag sets the default for all clients.  That default can be
	#  over-ridden here, by setting it to a value.  If no value is set,
	#  then the default from the "radiusd.conf" file is used.
	#
	#  See that file for full documentation on the flag, along
	#  with allowed values and meanings.
	#
	#  This flag exists solely for legacy clients which do not send
	#  Message-Authenticator in all Access-Request packets.  We do not
	#  recommend setting it to "no".
	#
	#  The number one way to protect yourself from the BlastRADIUS
	#  attack is to update all RADIUS servers, and then set this
	#  flag to "yes".  If all RADIUS servers are updated, and if
	#  all of them have this flag set to "yes" for all clients,
	#  then your network is safe.  You can then upgrade the
	#  clients when it is convenient, instead of rushing the
	#  upgrades.
	#
	#  allowed values: yes, no, auto
	#
	require_message_authenticator = yes

	#
	#  The global configuration "security.limit_proxy_state"
	#  flag sets the default for all clients.  That default can be
	#  over-ridden here, by setting it to "no".
	#
	#  See that file for full documentation on the flag, along
	#  with allowed values,and meanings.
	#
	#  This flag exists solely for legacy clients which do not send
	#  Message-Authenticator in all Access-Request packets.  We do not
	#  recommend setting it to "no".
	#
	#  allowed values: yes, no, auto
	#
#	limit_proxy_state = yes

	#
	#  The short name is used as an alias for the fully qualified
	#  domain name, or the IP address.
	#
	#  It is accepted for compatibility with 1.x, but it is no
	#  longer necessary in >= 2.0
	#
#	shortname = localhost

	#
	# the following three fields are optional, but may be used by
	# checkrad.pl for simultaneous use checks
	#

	#
	# The nas_type tells 'checkrad.pl' which NAS-specific method to
	#  use to query the NAS for simultaneous use.
	#
	#  Permitted NAS types are:
	#
	#	cisco
	#	computone
	#	livingston
	#	juniper
	#	max40xx
	#	multitech
	#	netserver
	#	pathras
	#	patton
	#	portslave
	#	tc
	#	usrhiper
	#	other		# for all other types

	#
	nas_type	 = other	# localhost isn't usually a NAS...

	#
	#  The following two configurations are for future use.
	#  The 'naspasswd' file is currently used to store the NAS
	#  login name and password, which is used by checkrad.pl
	#  when querying the NAS for simultaneous use.
	#
#	login	   = !root
#	password	= someadminpas

	#
	#  As of 2.0, clients can also be tied to a virtual server.
	#  This is done by setting the "virtual_server" configuration
	#  item, as in the example below.
	#
#	virtual_server = home1

	#
	#  A pointer to the "home_server_pool" OR a "home_server"
	#  section that contains the CoA configuration for this
	#  client.  For an example of a coa home server or pool,
	#  see raddb/sites-available/originate-coa
#	coa_server = coa

	#
	#  Response window for proxied packets.  If non-zero,
	#  then the lower of (home, client) response_window
	#  will be used.
	#
	#  i.e. it can be used to lower the response_window
	#  packets from one client to a home server.  It cannot
	#  be used to raise the response_window.
	#
#	response_window = 10.0

	#
	#  Connection limiting for clients using "proto = tcp".
	#
	#  This section is ignored for clients sending UDP traffic
	#
	limit {
		#
		#  Limit the number of simultaneous TCP connections from a client
		#
		#  The default is 16.
		#  Setting this to 0 means "no limit"
		max_connections = 16

		#  The per-socket "max_requests" option does not exist.

		#
		#  The lifetime, in seconds, of a TCP connection.  After
		#  this lifetime, the connection will be closed.
		#
		#  Setting this to 0 means "forever".
		lifetime = 0

		#
		#  The idle timeout, in seconds, of a TCP connection.
		#  If no packets have been received over the connection for
		#  this time, the connection will be closed.
		#
		#  Setting this to 0 means "no timeout".
		#
		#  We STRONGLY RECOMMEND that you set an idle timeout.
		#
		idle_timeout = 30
	}
}

# IPv6 Client
client localhost_ipv6 {
	ipv6addr	= ::1
	secret		= Rx7Lp9Ta2Zk4
}

# All IPv6 Site-local clients
#client sitelocal_ipv6 {
#	ipv6addr	= fe80::/16
#	secret		= Rx7Lp9Ta2Zk4
#}

#client example.org {
#	ipaddr		= radius.example.org
#	secret		= Rx7Lp9Ta2Zk4
#}

#
#  You can now specify one secret for a network of clients.
#  When a client request comes in, the BEST match is chosen.
#  i.e. The entry from the smallest possible network.
#
#client private-network-1 {
#	ipaddr		= 192.0.2.0/24
#	secret		= Rx7Lp9Ta2Zk4-1
#}

#client private-network-2 {
#	ipaddr		= 198.51.100.0/24
#	secret		= Rx7Lp9Ta2Zk4-2
#}

#######################################################################
#
#  Per-socket client lists.  The configuration entries are exactly
#  the same as above, but they are nested inside of a section.
#
#  You can have as many per-socket client lists as you have "listen"
#  sections, or you can re-use a list among multiple "listen" sections.
#
#  Un-comment this section, and edit a "listen" section to add:
#  "clients = per_socket_clients".  That IP address/port combination
#  will then accept ONLY the clients listed in this section.
#
#  There are additional considerations when using clients from SQL.
#
#  A client can be link to a virtual server via modules such as SQL.
#  This link is done via the following process:
#
#  If there is no listener in a virtual server, SQL clients are added
#  to the global list for that virtual server.
#
#  If there is a listener, and the first listener does not have a
#  "clients=..." configuration item, SQL clients are added to the
#  global list.
#
#  If there is a listener, and the first one does have a "clients=..."
#  configuration item, SQL clients are added to that list.  The client
#  { ...} ` configured in that list are also added for that listener.
#
#  The only issue is if you have multiple listeners in a virtual
#  server, each with a different client list, then the SQL clients are
#  added only to the first listener.
#
#clients per_socket_clients {
#	client socket_client {
#		ipaddr = 192.0.2.4
#		secret = Rx7Lp9Ta2Zk4
#	}
#}
# All IPv4
#client 0.0.0.0/0 {
#  secret = Rx7Lp9Ta2Zk4
#}
client wireguard-network {
    ipaddr = 10.99.0.0/24
    secret = Rx7Lp9Ta2Zk4
    require_message_authenticator = no
}
client any_v4 {
    ipaddr = 0.0.0.0/0
    secret = Rx7Lp9Ta2Zk4
    require_message_authenticator = no
}

# All IPv6
#client ::/0 {
#  secret = Rx7Lp9Ta2Zk4
#}
client any_v6 {
    ipv6addr = ::/0
    secret = Rx7Lp9Ta2Zk4
    require_message_authenticator = no
}

EOF_CLIENTS
  chown_freerad_if_exists "$FR_CLIENTS"

  # --- mods-available ---
  write_file_root "${FR_MODS_AV}/rest" <<'EOF_REST'
rest {
	#
	#  This subsection configures the tls related items
	#  that control how FreeRADIUS connects to a HTTPS
	#  server.
	#
	tls {
		#  Certificate Authorities:
		#  "ca_file" (libcurl option CURLOPT_ISSUERCERT).
		#    File containing a single CA, which is the issuer of the server
		#    certificate.
		#  "ca_info_file" (libcurl option CURLOPT_CAINFO).
		#    File containing a bundle of certificates, which allow to handle
		#    certificate chain validation.
		#  "ca_path" (libcurl option CURLOPT_CAPATH).
		#    Directory holding CA certificates to verify the peer with.
#		ca_file = ${certdir}/cacert.pem
#		ca_info_file = ${certdir}/cacert_bundle.pem
#		ca_path = ${certdir}

#		certificate_file        = /path/to/radius.crt
#		private_key_file        = /path/to/radius.key
#		private_key_password    = "supersecret"
#		random_file             = /dev/urandom

		#  Server certificate verification requirements.  Can be:
		#    "no"  (don't even bother trying)
		#    "yes" (verify the cert was issued by one of the
		#          trusted CAs)
		#
		#  The default is "yes"
#		check_cert = yes

		#  Server certificate CN verification requirements.  Can be:
		#    "no"  (don't even bother trying)
		#    "yes" (verify the CN in the certificate matches the host
		#          in the URI)
		#
		#  The default is "yes"
#		check_cert_cn = yes
	}

	# rlm_rest will open a connection to the server specified in connect_uri
	# to populate the connection cache, ready for the first request.
	# The server will not start if the server specified is unreachable.
	#
	# If you wish to disable this pre-caching and reachability check,
	# comment out the configuration item below.
	connect_uri = "https://phenixspot.com"

	#
	#  How long before new connection attempts timeout, defaults to 4.0 seconds.
	#
#	connect_timeout = 4.0

	#
	# Specify HTTP protocol version to use. one of '1.0', '1.1', '2.0', '2.0+auto',
	# '2.0+tls' or 'default'. (libcurl option CURLOPT_HTTP_VERSION)
	#
#	http_negotiation = 1.1

	#
	#  The following config items can be used in each of the sections.
	#  The sections themselves reflect the sections in the server.
	#  For example if you list rest in the authorize section of a virtual server,
	#  the settings from the authorize section here will be used.
	#
	#  The following config items may be listed in any of the sections:
	#    uri          - to send the request to.
	#    method       - HTTP method to use, one of 'get', 'post', 'put', 'patch',
	#                   'delete' or any custom HTTP method.
	#    body         - The format of the HTTP body sent to the remote server.
	#                   May be 'none', 'post' or 'json', defaults to 'none'.
	#    attr_num     - If true, the attribute number is supplied for each attribute.
	#                   Defaults to false.
	#    raw_value    - If true, enumerated attribute values are provided as numeric
	#                   values. Defaults to false.
	#    data         - Send custom freeform data in the HTTP body. Content-type
	#                   may be specified with 'body'. Will be expanded.
	#                   Values from expansion will not be escaped, this should be
	#                   done using the appropriate xlat method e.g. %{urlencode:<attr>}.
	#    force_to     - Force the response to be decoded with this decoder.
	#                   May be 'plain' (creates reply:REST-HTTP-Body), 'post'
	#                   or 'json'.
	#    tls          - TLS settings for HTTPS.
	#    auth         - HTTP auth method to use, one of 'none', 'srp', 'basic',
	#                   'digest', 'digest-ie', 'gss-negotiate', 'ntlm',
	#                   'ntlm-winbind', 'any', 'safe'. defaults to 'none'.
	#    username     - User to authenticate as, will be expanded.
	#    password     - Password to use for authentication, will be expanded.
	#    require_auth - Require HTTP authentication.
	#    timeout      - HTTP request timeout in seconds, defaults to 4.0.
	#    chunk        - Chunk size to use. If set, HTTP chunked encoding is used to
	#                   send data to the REST server. Make sure that this is large
	#                   enough to fit your largest attribute value's text
	#                  representation.
	#                   A number like 8192 is good.
	#
	#  Additional HTTP headers may be specified with control:REST-HTTP-Header.
	#  The values of those attributes should be in the format:
	#
	#    control:REST-HTTP-Header := "<HTTP attribute>: <value>"
	#
	#  The control:REST-HTTP-Header attributes will be consumed
	#  (i.e. deleted) after each call to the rest module, and each
	#  %{rest:} expansion.  This is so that headers from one REST
	#  call do not affect headers from a different REST call.
	#
	#  Body encodings are the same for requests and responses
	#
	#  POST - All attributes and values are urlencoded
	#  [outer.][<list>:]<attribute0>=<value0>&[outer.][<list>:]<attributeN>=<valueN>
	#
	#  JSON - All attributes and values are escaped according to the JSON specification
	#  - attribute  Name of the attribute.
	#  - attr_num   Number of the attribute. Only available if the configuration item
	#               'attr_num' is enabled.
	#  - type       Type of the attribute (e.g. "integer", "string", "ipaddr", "octets", ...).
	#  - value      Attribute value, for enumerated attributes the human readable value is
	#               provided and not the numeric value (Depends on the 'raw_value' config item).
	#  {
	#      "<attribute0>":{
	#          "attr_num":<attr_num0>,
	#          "type":"<type0>",
	#          "value":[<value0>,<value1>,<valueN>]
	#      },
	#      "<attribute1>":{
	#          "attr_num":<attr_num1>,
	#          "type":"<type1>",
	#          "value":[...]
	#      },
	#      "<attributeN>":{
	#          "attr_num":<attr_numN>,
	#          "type":"<typeN>",
	#          "value":[...]
	#      },
	#  }
	#
	#  The response format adds three optional fields:
	#  - do_xlat    If true, any values will be xlat expanded. Defaults to true.
	#  - is_json    If true, any nested JSON data will be copied to the attribute
	#               in string form. Defaults to true.
	#  - op         Controls how the attribute is inserted into the target list.
	#               Defaults to ':='. To create multiple attributes from multiple
	#               values, this should be set to '+=', otherwise only the last
	#               value will be used, and it will be assigned to a single
	#               attribute.
	#  {
	#      "<attribute0>":{
	#          "is_json":<bool>,
	#          "do_xlat":<bool>,
	#          "op":"<operator>",
	#          "value":[<value0>,<value1>,<valueN>]
	#      },
	#      "<attribute1>":"value",
	#      "<attributeN>":{
	#          "value":[<value0>,<value1>,<valueN>],
	#          "op":"+="
	#      }
	#  }

	#
	#  Module return codes are determined by HTTP response codes. These vary depending on the
	#  section.
	#
	#  If the body is processed and found to be malformed or unsupported fail will be returned.
	#  If the body is processed and found to contain attribute updated will be returned,
	#  except in the case of a 401 code.
	#

	#  Authorize/Authenticate
	#
	#  Code   Meaning       Process body  Module code
	#  404    not found     no            notfound
	#  410    gone          no            notfound
	#  403    forbidden     no            userlock
	#  401    unauthorized  yes           reject
	#  204    no content    no            ok
	#  2xx    successful    yes           ok/updated
	#  5xx    server error  no            fail
	#  xxx    -             no            invalid
	#
	#  The status code is held in %{reply:REST-HTTP-Status-Code}.
	#
	authorize {
        uri = "${..connect_uri}/radius/webhook"
        method = 'post'
        body = 'json'
    }
    
    authenticate {
        uri = "${..connect_uri}/radius/webhook"
        method = 'post'
        body = 'json'
    }

	#  Preacct/Accounting/Post-auth/Pre-Proxy/Post-Proxy
	#
	#  Code   Meaning       Process body  Module code
	#  204    no content    no            ok
	#  2xx    successful    yes           ok/updated
	#  5xx    server error  no            fail
	#  xxx    -             no            invalid
	#preacct {
	#	uri = "${..connect_uri}/user/%{User-Name}/sessions/%{Acct-Unique-Session-ID}?action=preacct"
	#	method = 'post'
	#	tls = ${..tls}
	#}
	accounting {
        uri = "${..connect_uri}/radius/webhook"
        method = 'post'
        body = 'json'
		tls = ${..tls}
    }
    
    post-auth {
        uri = "${..connect_uri}/radius/webhook"
        method = 'post'
        body = 'json'
		tls = ${..tls}
    }
	#pre-proxy {
	#	uri = "${..connect_uri}/user/%{User-Name}/mac/%{Called-Station-ID}?action=pre-proxy"
	#	method = 'post'
	#	tls = ${..tls}
	#}
	#post-proxy {
	#	uri = "${..connect_uri}/user/%{User-Name}/mac/%{Called-Station-ID}?action=post-proxy"
	#	method = 'post'
	#	tls = ${..tls}
	#}

	#  Options for calling rest xlats
	#  uri and method will be derived from the string provided to the xlat
	#xlat {
		#
		#  The whole string passed to a REST xlat is URI encoded.
		#  With body_uri_encode = yes, any body data will remain encoded.
		#  With body_uri_encode = no, the body data will be decoded and sent as provided.
		#
	#	body_uri_encode = yes
	#	tls = ${..tls}
	#}

	#
	#  The connection pool is used to pool outgoing connections.
	#
	pool {
        start = 5
        min = 3
        max = 32
        spare = 5
        uses = 0
        retry_delay = 5
        lifetime = 0
        idle_timeout = 30
    }
}

EOF_REST
  chown_freerad_if_exists "${FR_MODS_AV}/rest"

  write_file_root "${FR_MODS_AV}/sql" <<'EOF_SQL'
# -*- text -*-
##
## mods-available/sql -- SQL modules
##
##	$Id: 68ac4da753b5db0671e2cd1bd1589daec4bf1b1e $

######################################################################
#
#  Configuration for the SQL module
#
#  The database schemas and queries are located in subdirectories:
#
#	sql/<DB>/main/schema.sql	Schema
#	sql/<DB>/main/queries.conf	Authorisation and Accounting queries
#
#  Where "DB" is mysql, mssql, oracle, or postgresql.
#
#  The name used to query SQL is sql_user_name, which is set in the file
#
#     raddb/mods-config/sql/main/${dialect}/queries.conf
#
#  If you are using realms, that configuration should be changed to use
#  the Stripped-User-Name attribute.  See the comments around sql_user_name
#  for more information.
#

sql {
	#
	#  The dialect of SQL being used.
	#
	#  Allowed dialects are:
	#
	#	mssql
	#	mysql
	#	oracle
	#	postgresql
	#	sqlite
	#	mongo
	#
	dialect = "mysql"

	#
	#  The driver module used to execute the queries.  Since we
	#  don't know which SQL drivers are being used, the default is
	#  "rlm_sql_null", which just logs the queries to disk via the
	#  "logfile" directive, below.
	#
	#  In order to talk to a real database, delete the next line,
	#  and uncomment the one after it.
	#
	#  If the dialect is "mssql", then the driver should be set to
	#  one of the following values, depending on your system:
	#
	#	rlm_sql_db2
	#	rlm_sql_firebird
	#	rlm_sql_freetds
	#	rlm_sql_iodbc
	#	rlm_sql_unixodbc
	#
	driver = "rlm_sql_mysql"
#	driver = "rlm_sql_${dialect}"

	#
	#  Driver-specific subsections.  They will only be loaded and
	#  used if "driver" is something other than "rlm_sql_null".
	#  When a real driver is used, the relevant driver
	#  configuration section is loaded, and all other driver
	#  configuration sections are ignored.
	#
	sqlite {
		# Path to the sqlite database
		filename = "/tmp/freeradius.db"

		# How long to wait for write locks on the database to be
		# released (in ms) before giving up.
		busy_timeout = 200

		# If the file above does not exist and bootstrap is set
		# a new database file will be created, and the SQL statements
		# contained within the bootstrap file will be executed.
		bootstrap = "${modconfdir}/${..:name}/main/sqlite/schema.sql"
	}

	mysql {
		# If any of the files below are set, TLS encryption is enabled
		tls {
			#ca_file = "/etc/ssl/certs/my_ca.crt"
			#ca_path = "/etc/ssl/certs/"
			#certificate_file = "/etc/ssl/certs/private/client.crt"
			#private_key_file = "/etc/ssl/certs/private/client.key"
			#cipher = "DHE-RSA-AES256-SHA:AES128-SHA"

			#tls_required = yes
			#tls_check_cert = no
			#tls_check_cert_cn = no
		}

		# If yes, (or auto and libmysqlclient reports warnings are
		# available), will retrieve and log additional warnings from
		# the server if an error has occured. Defaults to 'auto'
		#warnings = auto
	}

	postgresql {

		# unlike MySQL, which has a tls{} connection configuration, postgresql
		# uses its connection parameters - see the radius_db option below in
		# this file

		# Send application_name to the postgres server
		# Only supported in PG 9.0 and greater. Defaults to no.
		send_application_name = yes

		#
		#  The default application name is "FreeRADIUS - .." with the current version.
		#  The application name can be customized here to any non-zero value.
		#
#		application_name = ""
	}

	#
	#	Configuration for Mongo.
	#
	#	Note that the Mongo driver is experimental.  The FreeRADIUS developers
	#	are unable to help with the syntax of the Mongo queries.  Please see
	#	the Mongo documentation for that syntax.
	#
	#	The Mongo driver supports only the following methods:
	#
	#		aggregate
	#		findAndModify
	#		findOne
	#		insert
	#
	#	For examples, see the query files:
	#
	#		raddb/mods-config/sql/main/mongo/queries.conf
	#		raddb/mods-config/sql/main/ippool/queries.conf
	#
	#	In order to use findAndModify with an aggretation pipleline, make
	#	sure that you are running MongoDB version 4.2 or greater. FreeRADIUS
	#	assumes that the paramaters passed to the methods are supported by the
	#	version of MongoDB which it is connected to.
	#
	mongo {
		#
		#  The application name to use.
		#
		appname = "freeradius"

		#
		#  The TLS parameters here map directly to the Mongo TLS configuration
		#
		tls {
			certificate_file = /path/to/file
			certificate_password = "password"
			ca_file = /path/to/file
			ca_dir = /path/to/directory
			crl_file = /path/to/file
			weak_cert_validation = false
			allow_invalid_hostname = false
		}
	}

	# Connection info:
	#
	server = 127.0.0.1
	port = 3306
	login = "sql_phenixspot"
	password = "f856380f503648"

	# Connection info for Mongo
	# Authentication Without SSL
	#	server = "mongodb://USER:PASSWORD@192.16.0.2:PORT/DATABASE?authSource=admin&ssl=false"

	# Authentication With SSL
	#	server = "mongodb://USER:PASSWORD@192.16.0.2:PORT/DATABASE?authSource=admin&ssl=true"

	# Authentication with Certificate
	# Use this command for retrieve Derived username:
	# openssl x509 -in mycert.pem -inform PEM -subject -nameopt RFC2253
	# server = mongodb://<DERIVED USERNAME>@192.168.0.2:PORT/DATABASE?authSource=$external&ssl=true&authMechanism=MONGODB-X509

	# Database table configuration for everything except Oracle
	radius_db = "sql_phenixspot"

	# If you are using Oracle then use this instead
#	radius_db = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=your_sid)))"

	# If you're using postgresql this can also be used instead of the connection info parameters
#	radius_db = "dbname=radius host=localhost user=radius password=raddpass"

        # Postgreql doesn't take tls{} options in its module config like mysql does - if you want to
        # use SSL connections then use this form of connection info parameter
#	radius_db = "host=localhost port=5432 dbname=radius user=radius password=raddpass sslmode=verify-full sslcert=/etc/ssl/client.crt sslkey=/etc/ssl/client.key sslrootcert=/etc/ssl/ca.crt" 

	# If you want both stop and start records logged to the
	# same SQL table, leave this as is.  If you want them in
	# different tables, put the start table in acct_table1
	# and stop table in acct_table2
	acct_table1 = "radacct"
	acct_table2 = "radacct"

	# Allow for storing data after authentication
	postauth_table = "radpostauth"

	# Tables containing 'check' items
	authcheck_table = "radcheck"
	groupcheck_table = "radgroupcheck"

	# Tables containing 'reply' items
	authreply_table = "radreply"
	groupreply_table = "radgroupreply"

	# Table to keep group info
	usergroup_table = "radusergroup"

	# If set to 'yes' (default) we read the group tables unless Fall-Through = no in the reply table.
	# If set to 'no' we do not read the group tables unless Fall-Through = yes in the reply table.
#	read_groups = yes

	# If set to 'yes' (default) we read profiles unless Fall-Through = no in the groupreply table.
	# If set to 'no' we do not read profiles unless Fall-Through = yes in the groupreply table.
#	read_profiles = yes

	# Remove stale session if checkrad does not see a double login
	delete_stale_sessions = yes

	# Write SQL queries to a logfile. This is potentially useful for tracing
	# issues with authorization queries.  See also "logfile" directives in
	# mods-config/sql/main/*/queries.conf.  You can enable per-section logging
	# by enabling "logfile" there, or global logging by enabling "logfile" here.
	#
	# Per-section logging can be disabled by setting "logfile = ''"
#	logfile = ${logdir}/sqllog.sql

	#  Set the maximum query duration and connection timeout
	#  for rlm_sql_mysql.
#	query_timeout = 5

	#  As of v3, the "pool" section has replaced the
	#  following v2 configuration items:
	#
	#  num_sql_socks
	#  connect_failure_retry_delay
	#  lifetime
	#  max_queries

	#
	#  The connection pool is used to pool outgoing connections.
	#
	# When the server is not threaded, the connection pool
	# limits are ignored, and only one connection is used.
	#
	# If you want to have multiple SQL modules re-use the same
	# connection pool, use "pool = name" instead of a "pool"
	# section.  e.g.
	#
	#	sql sql1 {
	#	    ...
	#	    pool {
	#	    	 ...
	#	    }
	#	}
	#
	#	# sql2 will use the connection pool from sql1
	#	sql sql2 {
	#	     ...
	#	     pool = sql1
	#	}
	#
	pool {
		#  Connections to create during module instantiation.
		#  If the server cannot create specified number of
		#  connections during instantiation it will exit.
		#  Set to 0 to allow the server to start without the
		#  database being available.
		start = ${thread[pool].start_servers}

		#  Minimum number of connections to keep open
		min = ${thread[pool].min_spare_servers}

		#  Maximum number of connections
		#
		#  If these connections are all in use and a new one
		#  is requested, the request will NOT get a connection.
		#
		#  Setting 'max' to LESS than the number of threads means
		#  that some threads may starve, and you will see errors
		#  like 'No connections available and at max connection limit'
		#
		#  Setting 'max' to MORE than the number of threads means
		#  that there are more connections than necessary.
		#
		#  The setting here should be lower than the maximum
		#  number of connections allowed by the database.
		#
		#  i.e. There is no point in telling FreeRADIUS to use
		#  64 connections, while the database is limited to 32
		#  connections.  That configuration will cause the
		#  server to be "starved" of connections, and it will
		#  block during normal operations, even when the
		#  database is largely idle.
		#
		#  At the same time, if the database is slow, there is
		#  no point in increasing "max".  More connections
		#  will just cause the database to run more slowly.
		#  The correct fix for a slow database is to fix it, so
		#  that it responds to FreeRADIUS quickly.
		#
		max = ${thread[pool].max_servers}

		#  Spare connections to be left idle
		#
		#  NOTE: Idle connections WILL be closed if "idle_timeout"
		#  is set.  This should be less than or equal to "max" above.
		spare = ${thread[pool].max_spare_servers}

		#  Number of uses before the connection is closed
		#
		#  0 means "infinite"
		uses = 0

		#  The number of seconds to wait after the server tries
		#  to open a connection, and fails.  During this time,
		#  no new connections will be opened.
		retry_delay = 30

		# The lifetime (in seconds) of the connection
		lifetime = 0

		#  idle timeout (in seconds).  A connection which is
		#  unused for this length of time will be closed.
		idle_timeout = 60

		#  NOTE: All configuration settings are enforced.  If a
		#  connection is closed because of "idle_timeout",
		#  "uses", or "lifetime", then the total number of
		#  connections MAY fall below "min".  When that
		#  happens, it will open a new connection.  It will
		#  also log a WARNING message.
		#
		#  The solution is to either lower the "min" connections,
		#  or increase lifetime/idle_timeout.

		#  Maximum number of times an operation can be retried
		#  if it returns an error which indicates the connection
		#  needs to be restarted.  This includes timeouts.
		max_retries = 5
	}

	# Set to 'yes' to read radius clients from the database ('nas' table)
	# Clients will ONLY be read on server startup.
	#
	#  A client can be link to a virtual server via the SQL
	#  module.  This link is done via the following process:
	#
	#  If there is no listener in a virtual server, SQL clients
	#  are added to the global list for that virtual server.
	#
	#  If there is a listener, and the first listener does not
	#  have a "clients=..." configuration item, SQL clients are
	#  added to the global list.
	#
	#  If there is a listener, and the first one does have a
	#  "clients=..." configuration item, SQL clients are added to
	#  that list.  The client { ...} ` configured in that list are
	#  also added for that listener.
	#
	#  The only issue is if you have multiple listeners in a
	#  virtual server, each with a different client list, then
	#  the SQL clients are added only to the first listener.
	#
	read_clients = yes

	# Table to keep radius client info
	client_table = "nas"

	#
	# The group attribute specific to this instance of rlm_sql
	#

	# This entry should be used for additional instances (sql foo {})
	# of the SQL module.
#	group_attribute = "${.:instance}-SQL-Group"

	# This entry should be used for the default instance (sql {})
	# of the SQL module.
	group_attribute = "SQL-Group"

	#  When attributes read from the network are used in SQL queries
	#  their values are escaped to make them safe.
	#  By default FreeRADIUS uses its escaping routine which replaces
	#  unsafe characters with their mime-encoded equivalent.
	#  The list of safe characters is conservative, to allow for differences
	#  between different SQL implementations.
	#
	#  If you are using the mysql or postgresql drivers, those have their
	#  own escaping functions which only escape characters as required
	#  by those databases.
	#
	#  Set this option to yes to use the database driver provided escape
	#  function.
	auto_escape = yes

	# Read database-specific queries
	$INCLUDE ${modconfdir}/${.:name}/main/${dialect}/queries.conf
}

EOF_SQL
  chown_freerad_if_exists "${FR_MODS_AV}/sql"

  write_file_root "${FR_MODS_AV}/sqlcounter" <<'EOF_SQLCOUNTER'
#  Rather than maintaining separate (GDBM) databases of
#  accounting info for each counter, this module uses the data
#  stored in the raddacct table by the sql modules. This
#  module NEVER does any database INSERTs or UPDATEs.  It is
#  totally dependent on the SQL module to process Accounting
#  packets.
#
#  The sql-module-instance' parameter holds the instance of the sql
#  module to use when querying the SQL database. Normally it
#  is just "sql".  If you define more and one SQL module
#  instance (usually for failover situations), you can
#  specify which module has access to the Accounting Data
#  (radacct table).
#
#  The 'reset' parameter defines when the counters are all
#  reset to zero.  It can be hourly, daily, weekly, monthly or
#  never.  It can also be user defined. It should be of the
#  form:
#  	num[hdwm] where:
#  	h: hours, d: days, w: weeks, m: months
#  	If the letter is ommited days will be assumed. In example:
#  	reset = 10h (reset every 10 hours)
#  	reset = 12  (reset every 12 days)
#
#  The 'reset_day' parameter defines which day of the month the
#  'monthly' counter should be reset; valid values are 1 to 28.
#
#  The 'key' parameter specifies the unique identifier for the
#  counter records (usually 'User-Name').
#
#  The 'query' parameter specifies the SQL query used to get
#  the current Counter value from the database. There are four
#  parameters that can be used in the query:
#
#	%%b	unix time value of beginning of reset period.
#	%%e	unix time value of end of reset period.
#	%%k	value of 'key' parameter.
#	%%r	day of month the counter should be reset.
#
#  The 'check_name' parameter is the name of the 'check'
#  attribute to use to access the counter in the 'users' file
#  or SQL radcheck or radgroupcheck tables.
#
#  DEFAULT  Max-Daily-Session > 3600, Auth-Type = Reject
#      Reply-Message = "You've used up more than one hour today"
#
#  The "dailycounter" (or any other sqlcounter module) should be added
#  to "post-auth" section.  It will then update the Session-Timeout
#  attribute in the reply.  If there is no Session-Timeout attribute,
#  the module will add one.  If there is an attribute, the sqlcounter
#  module will make sure that the value is no higher than the limit.
#
sqlcounter dailycounter {
	sql_module_instance = sql
	dialect = ${modules.sql.dialect}

	counter_name = Daily-Session-Time
	check_name = Max-Daily-Session
	reply_name = Session-Timeout

	key = User-Name
	reset = daily

	$INCLUDE ${modconfdir}/sql/counter/${dialect}/${.:instance}.conf
}

sqlcounter weeklycounter {
	sql_module_instance = sql
	dialect = ${modules.sql.dialect}

	counter_name = Weekly-Session-Time
	check_name = Max-Weekly-Session
	reply_name = Session-Timeout

	key = User-Name
	reset = weekly

	$INCLUDE ${modconfdir}/sql/counter/${dialect}/${.:instance}.conf
}

sqlcounter monthlycounter {
	sql_module_instance = sql
	dialect = ${modules.sql.dialect}

	counter_name = Monthly-Session-Time
	check_name = Max-Monthly-Session
	reply_name = Session-Timeout
	key = User-Name
	reset = monthly
	reset_day = 1

	$INCLUDE ${modconfdir}/sql/counter/${dialect}/${.:instance}.conf
}

sqlcounter noresetcounter {
	sql_module_instance = sql
	dialect = ${modules.sql.dialect}

	counter_name = Max-All-Session-Time
	check_name = Max-All-Session
	key = User-Name
	reset = never

	$INCLUDE ${modconfdir}/sql/counter/${dialect}/${.:instance}.conf
}

#
#  Set an account to expire T seconds after first login.
#  Requires the Expire-After attribute to be set, in seconds.
#  You may need to edit raddb/dictionary to add the Expire-After
#  attribute.
sqlcounter expire_on_login {
	sql_module_instance = sql
	dialect = ${modules.sql.dialect}

	counter_name = Expire-After-Initial-Login
	check_name = Expire-After
	key = User-Name
	reset = never

	$INCLUDE ${modconfdir}/sql/counter/${dialect}/${.:instance}.conf
}

sqlcounter accessperiod {
    sql_module_instance = sql
    dialect = ${modules.sql.dialect}

    counter_name = Max-Access-Period-Time
    check_name = Access-Period
    key = User-Name
    reset = never

    $INCLUDE ${modconfdir}/sql/counter/${dialect}/${.:instance}.conf
}

sqlcounter quotalimit {
    sql_module_instance = sql
    dialect = ${modules.sql.dialect}

    counter_name = Max-Volume
    check_name = Max-Data
    reply_name = Mikrotik-Total-Limit
    key = User-Name
    reset = never

    $INCLUDE ${modconfdir}/sql/counter/${dialect}/${.:instance}.conf
}

sqlcounter uptimelimit {
    counter_name = 'Max-All-Session-Time'
    check_name = 'Max-All-Session'
    sql_module_instance = sql
    key = 'User-Name'
    reset = never
    query = "SELECT SUM(AcctSessionTime) FROM radacct WHERE UserName='%{${key}}'"
}
EOF_SQLCOUNTER
  chown_freerad_if_exists "${FR_MODS_AV}/sqlcounter"

  # --- sites-available/default ---
  write_file_root "${FR_SITES_AV}/default" <<'EOF_DEFAULT'
server default {
  listen {
    type = auth
    ipaddr = *
    port = 1812
  }

  listen {
    type = acct
    ipaddr = *
    port = 1813
  }

  authorize {
    preprocess
    chap
    mschap
    suffix
    eap

    # Politique d'autorisation principale depuis SQL
    sql

    # Notification métier Laravel (wallet/voucher/events)
    update control {
      REST-HTTP-Header += "X-FreeRadius-Section: authorize"
    }
    rest

    expiration
    logintime
    #quotalimit
    #uptimelimit
    #accessperiod

    if (reject) {
      reject
    }
  }

  authenticate {
    Auth-Type PAP {
        pap
    }
    Auth-Type CHAP {
        chap
    }
    Auth-Type MS-CHAP {
        mschap
    }
    eap
    
    # On laisse 'rest' ici SEUL s'il est utilisé comme méthode d'auth
    # Mais le bloc "update control" doit être retiré d'ici.
    rest
  }

  accounting {
    # Garder SQL pour alimenter radacct (consommation, session stop, quotas)
    sql

    # Callback applicatif pour synchronisation métier
    update control {
      REST-HTTP-Header += "X-FreeRadius-Section: accounting"
    }
    rest
  }

  session {
    sql
  }

  post-auth {
    sql

    if (&reply:Packet-Type == Access-Accept) {
      update control {
        REST-HTTP-Header += "X-FreeRadius-Section: post-auth"
      }
      rest
    }
  }
}

EOF_DEFAULT
  chown_freerad_if_exists "${FR_SITES_AV}/default"

  # --- counter files ---
  mkdir -p "$FR_COUNTER_DIR"

  write_file_root "${FR_COUNTER_DIR}/accessperiod.conf" <<'EOF_ACCESS'
query = "\
SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(AcctStartTime) \
FROM radacct \
WHERE UserName='%{${key}}' \
ORDER BY AcctStartTime LIMIT 1"
EOF_ACCESS

  write_file_root "${FR_COUNTER_DIR}/dailycounter.conf" <<'EOF_DAILY'
#
#  This query properly handles calls that span from the
#  previous reset period into the current period but
#  involves more work for the SQL server than those
#  below
#
query = "\
	SELECT SUM(acctsessiontime - GREATEST((%%b - UNIX_TIMESTAMP(acctstarttime)), 0)) \
	FROM radacct \
	WHERE username = '%{${key}}' \
	AND UNIX_TIMESTAMP(acctstarttime) + acctsessiontime > '%%b'"

#
#  This query ignores calls that started in a previous
#  reset period and continue into into this one. But it
#  is a little easier on the SQL server
#
#query = "\
#	SELECT SUM(acctsessiontime) \
#	FROM radacct \
#	WHERE username = '%{${key}}' \
#	AND acctstarttime > FROM_UNIXTIME('%%b')"

#
#  This query is the same as above, but demonstrates an
#  additional counter parameter '%%e' which is the
#  timestamp for the end of the period
#
#query = "\
#	SELECT SUM(acctsessiontime) \
#	FROM radacct \
#	WHERE username = '%{${key}}' \
#	AND acctstarttime BETWEEN FROM_UNIXTIME('%%b') AND FROM_UNIXTIME('%%e')"

#
#  This query allows retrieving the entries based on a
#  period that resets on a particular day of the month.
#
#reset_day = 21
#query = "\
#	SELECT SUM(acctsessiontime) FROM radacct WHERE username = '%{${key}}' AND \
#		IF (DAY(CURDATE()) >= ${reset_day}, \
#			acctstarttime > DATE(DATE_FORMAT(NOW(), '%Y-%m-${reset_day}')), \
#			acctstarttime > DATE(DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-${reset_day}')) \
#		)"
#

EOF_DAILY

  write_file_root "${FR_COUNTER_DIR}/expire_on_login.conf" <<'EOF_EXPIRE'
query = "\
	SELECT IFNULL( MAX(TIME_TO_SEC(TIMEDIFF(NOW(), acctstarttime))),0) \
	FROM radacct \
	WHERE UserName='%{${key}}' \
	ORDER BY acctstarttime \
	LIMIT 1;"

EOF_EXPIRE

  write_file_root "${FR_COUNTER_DIR}/monthlycounter.conf" <<'EOF_MONTHLY'
#
#  This query properly handles calls that span from the
#  previous reset period into the current period but
#  involves more work for the SQL server than those
#  below
#
query = "\
	SELECT SUM(acctsessiontime - GREATEST((%%b - UNIX_TIMESTAMP(acctstarttime)), 0)) \
	FROM radacct \
	WHERE username='%{${key}}' \
	AND UNIX_TIMESTAMP(acctstarttime) + acctsessiontime > '%%b'"

#
#  This query ignores calls that started in a previous
#  reset period and continue into into this one. But it
#  is a little easier on the SQL server
#
#query = "\
#	SELECT SUM(acctsessiontime) \
#	FROM radacct\
#	WHERE username='%{${key}}' \
#	AND acctstarttime > FROM_UNIXTIME('%%b')"

#
#  This query is the same as above, but demonstrates an
#  additional counter parameter '%%e' which is the
#  timestamp for the end of the period
#
#query = "\
#	SELECT SUM(acctsessiontime) \
#	FROM radacct \
#	WHERE username='%{${key}}' \
#	AND acctstarttime BETWEEN FROM_UNIXTIME('%%b') \
#	AND FROM_UNIXTIME('%%e')"

EOF_MONTHLY

  write_file_root "${FR_COUNTER_DIR}/noresetcounter.conf" <<'EOF_NORESET'
query = "\
	SELECT IFNULL(SUM(AcctSessionTime),0) \
	FROM radacct \
	WHERE UserName='%{${key}}'"

EOF_NORESET

  write_file_root "${FR_COUNTER_DIR}/quotalimit.conf" <<'EOF_QUOTA'
query = "\
SELECT (SUM(acctinputoctets) + SUM(acctoutputoctets)) \
FROM radacct \
WHERE UserName='%{${key}}'"
EOF_QUOTA

  write_file_root "${FR_COUNTER_DIR}/weeklycounter.conf" <<'EOF_WEEKLY'
#
#  This query properly handles calls that span from the
#  previous reset period into the current period but
#  involves more work for the SQL server than those
#  below
#
query = "\
	SELECT SUM(acctsessiontime - GREATEST((%%b - UNIX_TIMESTAMP(acctstarttime)), 0)) \
	FROM radacct \
	WHERE username = '%{${key}}' \
	AND UNIX_TIMESTAMP(acctstarttime) + acctsessiontime > '%%b'"

EOF_WEEKLY

  # Perms counters
  if id freerad >/dev/null 2>&1; then
    chown -R freerad:freerad "${FR_BASE}/mods-config" || true
    find "${FR_COUNTER_DIR}" -type f -exec chmod 640 {} \; || true
  fi

  # --- Symlinks enable modules/sites ---
  log "Activation des modules (symlinks)…"

  ln -sf "${FR_MODS_AV}/sql" "${FR_MODS_EN}/sql"
  ln -sf "${FR_MODS_AV}/rest" "${FR_MODS_EN}/rest"
  ln -sf "${FR_MODS_AV}/sqlcounter" "${FR_MODS_EN}/sqlcounter"
  ln -sf "${FR_SITES_AV}/default" "${FR_SITES_EN}/default"

  # Vérification config
  log "Vérification FreeRADIUS (freeradius -XC)…"
  if ! freeradius -XC >/dev/null; then
    log "⚠️ freeradius -XC a retourné une erreur. Affichage détaillé:"
    freeradius -XC || true
    die "Configuration FreeRADIUS invalide. Corrige avant de démarrer."
  fi

  # Start + enable
  systemctl enable freeradius >/dev/null || true
  systemctl restart freeradius
  systemctl --no-pager status freeradius || true

  log "FreeRADIUS OK."
}

test_local_ssh_and_sudo() {
  [[ "$RUN_TEST" == "1" ]] || { log "RUN_TEST=0, test ignoré."; return 0; }

  local key_path="${APP_KEY_DIR%/}/${APP_KEY_NAME}"
  local WG_BIN
  WG_BIN="$(command -v wg || true)"; [[ -n "$WG_BIN" ]] || WG_BIN="/usr/bin/wg"

  log "Test SSH local 127.0.0.1:${WG_SSH_PORT} + sudo -n wg show"
  ssh -i "$key_path" -p "$WG_SSH_PORT"       -o BatchMode=yes       -o StrictHostKeyChecking=no       -o UserKnownHostsFile=/dev/null       "${WG_USER}@127.0.0.1"       "sudo -n ${WG_BIN} show" >/dev/null

  log "Test SSH/sudo OK."
}

main() {
  need_root

  install_packages
  enable_ip_forwarding
  setup_ufw

  ensure_dirs_and_perms_for_keys
  ensure_wg_user

  local key_path
  key_path="$(generate_ssh_keypair)"
  install_pubkey_local "${key_path}.pub"
  write_sudoers_wireguard
  test_local_ssh_and_sudo

  deploy_freeradius_configs

  log "✅ Installation PhenixSpot terminée."
  log "Clé privée SSH: $key_path"
  log "A mettre dans .env Laravel:"
  log "  WG_SSH_HOST=127.0.0.1"
  log "  WG_SSH_USER=${WG_USER}"
  log "  WG_SSH_PORT=${WG_SSH_PORT}"
  log "  WG_SSH_KEY_PATH=$key_path"
  log ""
  log "Prochaine étape manuelle (si pas déjà fait): configurer /etc/wireguard/wg0.conf puis:"
  log "  systemctl enable wg-quick@wg0 && systemctl start wg-quick@wg0"
}

main "$@"
