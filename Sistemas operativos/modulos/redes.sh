#!/bin/bash

ver_red() {
    ip a
    ip route
}

ip_estatica() {
    nmcli connection show
    read -p "Nombre de la conexion: " conexion
    read -p "IP con mascara (ej 192.168.56.10/24): " ip
    nmcli connection modify $conexion ipv4.method manual ipv4.addresses $ip
    nmcli connection up $conexion
    log_event "IP estatica $ip en $conexion"
    echo "IP configurada"
}

probar_conexion() {
    read -p "IP o nombre a probar: " destino
    ping -c 3 $destino
    log_event "Ping a $destino"
}

ver_puertos() {
    ss -tuln
}
