#!/bin/bash

# =================================================================
# SCRIPT D'INSTALLATION AUTOMATIQUE WIRE GUARD - PHENIXSPOT
# Système : Ubuntu 24.04 (AWS / Autre)
# =================================================================

# Couleurs pour l'affichage
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧱 PHASE 1 — Installation WireGuard sur Ubuntu${NC}"

# 1. Mise à jour et Installation
echo -e "${BLUE}[1/6] Installation des paquets...${NC}"
sudo apt update && sudo apt install wireguard -y
if [[ $? -ne 0 ]]; then echo -e "${RED}Erreur installation${NC}"; exit 1; fi

# 2. Génération des clés
echo -e "${BLUE}[2/6] Génération des clés du serveur...${NC}"
sudo mkdir -p /etc/wireguard
cd /etc/wireguard
sudo umask 077
sudo wg genkey | sudo tee server_private.key | sudo wg pubkey | sudo tee server_public.key > /dev/null

SERVER_PRIV_KEY=$(cat server_private.key)
SERVER_PUB_KEY=$(cat server_public.key)

# 3. Détection de l'interface publique
# On cherche l'interface par défaut utilisée pour la route 0.0.0.0
PUB_IFACE=$(ip route | grep default | awk '{print $5}' | head -n 1)
PUB_IP=$(curl -s https://ifconfig.me)

echo -e "${BLUE}[3/6] Détection réseau...${NC}"
echo -e "Interface publique detectée : ${GREEN}$PUB_IFACE${NC}"
echo -e "IP Publique du serveur : ${GREEN}$PUB_IP${NC}"

# 4. Création du fichier de configuration wg0.conf
echo -e "${BLUE}[4/6] Création de /etc/wireguard/wg0.conf...${NC}"
cat <<EOF | sudo tee /etc/wireguard/wg0.conf > /dev/null
[Interface]
Address = 10.99.0.1/24
ListenPort = 51820
PrivateKey = $SERVER_PRIV_KEY

# Activation IP forwarding via IPTables
PostUp = sysctl -w net.ipv4.ip_forward=1
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT
PostUp = iptables -A FORWARD -o wg0 -j ACCEPT
PostUp = iptables -t nat -A POSTROUTING -s 10.99.0.0/24 -o $PUB_IFACE -j MASQUERADE

PostDown = iptables -D FORWARD -i wg0 -j ACCEPT
PostDown = iptables -D FORWARD -o wg0 -j ACCEPT
PostDown = iptables -t nat -D POSTROUTING -s 10.99.0.0/24 -o $PUB_IFACE -j MASQUERADE
EOF

# 5. Rendre le forwarding permanent
echo -e "${BLUE}[5/6] Configuration du forwarding permanent...${NC}"
if ! grep -q "net.ipv4.ip_forward=1" /etc/sysctl.conf; then
    echo "net.ipv4.ip_forward=1" | sudo tee -a /etc/sysctl.conf
fi
sudo sysctl -p

# 6. Démarrage du service
echo -e "${BLUE}[6/6] Démarrage du service WireGuard...${NC}"
sudo systemctl enable wg-quick@wg0
sudo systemctl restart wg-quick@wg0

# =================================================================
# RÉCAPITULATIF FINAL
# =================================================================
echo -e "\n${GREEN}🎯 INSTALLATION TERMINÉE AVEC SUCCÈS${NC}"
echo -e "---------------------------------------------------"
echo -e "🔑 ${BLUE}Clé Privée Serveur :${NC}  $SERVER_PRIV_KEY"
echo -e "🔑 ${BLUE}Clé Publique Serveur :${NC} $SERVER_PUB_KEY"
echo -e "🌐 ${BLUE}IP Publique (WAN) :${NC}   $PUB_IP"
echo -e "📍 ${BLUE}IP Interne (LAN WG) :${NC} 10.99.0.1"
echo -e "🔌 ${BLUE}Port d'écoute :${NC}       51820 (UDP)"
echo -e "🖥️  ${BLUE}Interface Sortie :${NC}    $PUB_IFACE"
echo -e "---------------------------------------------------"
echo -e "${RED}⚠️  RAPPEL : N'oubliez pas d'ouvrir le port UDP 51820 dans votre Security Group AWS !${NC}"
