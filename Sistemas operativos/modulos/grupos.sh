#!/bin/bash

alta_grupo() {
    read -p "Nombre del grupo: " grupo
    groupadd $grupo
    log_event "Alta del grupo $grupo"
    echo "Grupo $grupo creado"
}

baja_grupo() {
    read -p "Nombre del grupo a eliminar: " grupo
    groupdel $grupo
    log_event "Baja del grupo $grupo"
    echo "Grupo $grupo eliminado"
}

agregar_a_grupo() {
    read -p "Nombre del usuario: " usuario
    read -p "Nombre del grupo: " grupo
    usermod -aG $grupo $usuario
    log_event "Usuario $usuario agregado al grupo $grupo"
    echo "Usuario $usuario agregado al grupo $grupo"
}

listar_grupos() {
    cut -d: -f1 /etc/group
}
