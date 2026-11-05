#!/bin/bash

alta_usuario() {
    read -p "Nombre del usuario: " usuario
    if id $usuario &> /dev/null; then
        echo "El usuario ya existe"
        return
    fi
    useradd -m $usuario
    passwd $usuario
    log_event "Alta del usuario $usuario"
    echo "Usuario $usuario creado"
}

baja_usuario() {
    read -p "Nombre del usuario a eliminar: " usuario
    if ! id $usuario &> /dev/null; then
        echo "El usuario no existe"
        return
    fi
    userdel -r $usuario
    log_event "Baja del usuario $usuario"
    echo "Usuario $usuario eliminado"
}

bloquear_usuario() {
    read -p "Nombre del usuario a bloquear: " usuario
    if ! id $usuario &> /dev/null; then
        echo "El usuario no existe"
        return
    fi
    usermod -L $usuario
    log_event "Bloqueo del usuario $usuario"
    echo "Usuario $usuario bloqueado"
}

desbloquear_usuario() {
    read -p "Nombre del usuario a desbloquear: " usuario
    if ! id $usuario &> /dev/null; then
        echo "El usuario no existe"
        return
    fi
    usermod -U $usuario
    log_event "Desbloqueo del usuario $usuario"
    echo "Usuario $usuario desbloqueado"
}

listar_usuarios() {
    grep "/home" /etc/passwd | cut -d: -f1
}
