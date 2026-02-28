#!/bin/bash

# =================================================================
# SCRIPT D'INSTALLATION INTERACTIF PHENIXSPOT & WIRE GUARD
# Système : Ubuntu 24.04 (Mode Production)
# =================================================================

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Nettoyage de l'écran
clear
echo -e "${BLUE}=====================================================${NC}"
echo -e "${BLUE}          🚀 INSTALLATEUR PHENIXSPOT CLI             ${NC}"
echo -e "${BLUE}=====================================================${NC}"
echo -e "Que souhaitez-vous installer ?"
echo -e "1) WireGuard + PhenixSpot (Complet)"
echo -e "2) WireGuard uniquement (Tunnel VPN)"
echo -e "3) PhenixSpot uniquement (Application Web)"
echo -e "4) Quitter"
echo -ne "\nOption [1-4] : "
read -r CHOICE

if [[ $CHOICE -eq 4 ]]; then
    echo -e "${RED}Installation annulée.${NC}"
    exit 0
fi

# --- FONCTION WIREGUARD ---
install_wireguard() {
    echo -e "\n${BLUE}🔐 PHASE — Configuration de WireGuard${NC}"
    sudo apt update && sudo apt install -y wireguard curl
    
    sudo mkdir -p /etc/wireguard
    cd /etc/wireguard || exit
    sudo umask 077
    sudo wg genkey | sudo tee server_private.key | sudo wg pubkey | sudo tee server_public.key > /dev/null

    SERVER_PRIV_KEY=$(cat server_private.key)
    SERVER_PUB_KEY=$(cat server_public.key)
    PUB_IFACE=$(ip route | grep default | awk '{print $5}' | head -n 1)
    PUB_IP=$(curl -s https://ifconfig.me)

    cat <<EOF | sudo tee /etc/wireguard/wg0.conf > /dev/null
[Interface]
Address = 10.99.0.1/16
ListenPort = 51820
PrivateKey = $SERVER_PRIV_KEY

# Activation IP forwarding via IPTables
PostUp = sysctl -w net.ipv4.ip_forward=1
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT
PostUp = iptables -A FORWARD -o wg0 -j ACCEPT
PostUp = iptables -t nat -A POSTROUTING -s 10.99.0.0/16 -o $PUB_IFACE -j MASQUERADE

PostDown = iptables -D FORWARD -i wg0 -j ACCEPT
PostDown = iptables -D FORWARD -o wg0 -j ACCEPT
PostDown = iptables -t nat -D POSTROUTING -s 10.99.0.0/16 -o $PUB_IFACE -j MASQUERADE
EOF

    if ! grep -q "net.ipv4.ip_forward=1" /etc/sysctl.conf; then
        echo "net.ipv4.ip_forward=1" | sudo tee -a /etc/sysctl.conf
    fi
    sudo sysctl -p
    sudo systemctl enable wg-quick@wg0
    sudo systemctl restart wg-quick@wg0

    # =================================================================
    # RÉCAPITULATIF FINAL WIRE GUARD
    # =================================================================
    echo -e "\n${GREEN}🎯 INSTALLATION TERMINÉE AVEC SUCCÈS${NC}"
    echo -e "---------------------------------------------------"
    echo -e "🔑 ${BLUE}Clé Privée Serveur :${NC}  $SERVER_PRIV_KEY"
    echo -e "🔑 ${BLUE}Clé Publique Serveur :${NC} $SERVER_PUB_KEY"
    echo -e "🌐 ${BLUE}IP Publique (WAN) :${NC}    $PUB_IP"
    echo -e "📍 ${BLUE}IP Interne (LAN WG) :${NC} 10.99.0.1"
    echo -e "🔌 ${BLUE}Port d'écoute :${NC}        51820 (UDP)"
    echo -e "🖥️  ${BLUE}Interface Sortie :${NC}    $PUB_IFACE"
    echo -e "---------------------------------------------------"
    echo -e "${RED}⚠️  RAPPEL : N'oubliez pas d'ouvrir le port UDP 51820 dans votre Security Group AWS !${NC}"
    
    cd - > /dev/null || exit
}

# --- FONCTION PHENIXSPOT ---
install_phenixspot() {
    echo -e "\n${BLUE}🚀 PHASE — Installation de l'application PhenixSpot${NC}"
    
    # Vérification présence projet
    if [ ! -f "composer.json" ]; then
        echo -e "${RED}Erreur : Aucun fichier composer.json détecté. Veuillez lancer ce script depuis la racine du projet.${NC}"
        return 1
    fi

    # Dépendances système
    sudo apt update && sudo apt install -y \
        git unzip zip mariadb-server curl \
        php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-intl

    # Node.js
    if ! command -v node &> /dev/null; then
        curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
        sudo apt install -y nodejs
    fi

    # Composer
    if ! command -v composer &> /dev/null; then
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
    fi

    echo -e "${BLUE}Installation des dépendances Laravel...${NC}"
    composer install --no-dev --optimize-autoloader

    if [ ! -f ".env" ]; then
        cp .env.example .env
        php artisan key:generate
        echo -e "${RED}⚠️  Action requise : Configurez votre base de données dans le fichier .env${NC}"
    fi

    echo -e "${BLUE}Compilation des assets...${NC}"
    npm install
    npm run build
    
    echo -e "${GREEN}✅ PhenixSpot configuré pour la production.${NC}"
}

# --- LOGIQUE DE SELECTION ---
case $CHOICE in
    1)
        install_wireguard
        install_phenixspot
        ;;
    2)
        install_wireguard
        ;;
    3)
        install_phenixspot
        ;;
    *)
        echo -e "${RED}Option invalide.${NC}"
        ;;
esac

echo -e "\n${GREEN}--- FIN DU PROCESSUS ---${NC}"
