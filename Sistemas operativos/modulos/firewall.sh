#!/bin/bash

ver_firewall() {
    firewall-cmd --list-all
}

abrir_puerto() {
    read -p "Puerto a abrir (ej 8080/tcp): " puerto
    firewall-cmd --permanent --add-port=$puerto
    firewall-cmd --reload
    log_event "Puerto $puerto abierto"
    echo "Puerto $puerto abierto"
}

cerrar_puerto() {
    read -p "Puerto a cerrar (ej 8080/tcp): " puerto
    firewall-cmd --permanent --remove-port=$puerto
    firewall-cmd --reload
    log_event "Puerto $puerto cerrado"
    echo "Puerto $puerto cerrado"
}
