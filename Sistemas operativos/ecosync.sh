#!/bin/bash

DIR=$(dirname "$0")

source $DIR/lib/common.sh
source $DIR/modulos/usuarios.sh
source $DIR/modulos/grupos.sh
source $DIR/modulos/redes.sh
source $DIR/modulos/firewall.sh
source $DIR/modulos/basedatos.sh
source $DIR/modulos/respaldos.sh
source $DIR/modulos/logs.sh

require_root

if [ "$1" == "respaldo" ]; then
    respaldo_manual
    enviar_respaldo
    borrar_respaldos_viejos
    exit 0
fi

menu_usuarios() {
    echo "1) Alta  2) Baja  3) Bloquear  4) Desbloquear  5) Listar"
    read -p "Opcion: " op
    case $op in
        1) alta_usuario ;;
        2) baja_usuario ;;
        3) bloquear_usuario ;;
        4) desbloquear_usuario ;;
        5) listar_usuarios ;;
        *) echo "Opcion invalida" ;;
    esac
}

menu_grupos() {
    echo "1) Alta  2) Baja  3) Agregar usuario  4) Listar"
    read -p "Opcion: " op
    case $op in
        1) alta_grupo ;;
        2) baja_grupo ;;
        3) agregar_a_grupo ;;
        4) listar_grupos ;;
        *) echo "Opcion invalida" ;;
    esac
}

menu_redes() {
    echo "1) Ver red  2) IP estatica  3) Probar conexion  4) Ver puertos"
    read -p "Opcion: " op
    case $op in
        1) ver_red ;;
        2) ip_estatica ;;
        3) probar_conexion ;;
        4) ver_puertos ;;
        *) echo "Opcion invalida" ;;
    esac
}

menu_firewall() {
    echo "1) Ver firewall  2) Abrir puerto  3) Cerrar puerto"
    read -p "Opcion: " op
    case $op in
        1) ver_firewall ;;
        2) abrir_puerto ;;
        3) cerrar_puerto ;;
        *) echo "Opcion invalida" ;;
    esac
}

menu_base() {
    echo "1) Estado  2) Crear base  3) Crear usuario  4) Listar  5) Exportar"
    read -p "Opcion: " op
    case $op in
        1) estado_base ;;
        2) crear_base ;;
        3) crear_usuario_base ;;
        4) listar_bases ;;
        5) exportar_base ;;
        *) echo "Opcion invalida" ;;
    esac
}

menu_respaldos() {
    echo "1) Respaldo manual  2) Enviar al servidor  3) Listar  4) Borrar viejos"
    read -p "Opcion: " op
    case $op in
        1) respaldo_manual ;;
        2) enviar_respaldo ;;
        3) listar_respaldos ;;
        4) borrar_respaldos_viejos ;;
        *) echo "Opcion invalida" ;;
    esac
}

menu_logs() {
    echo "1) Ver log  2) Buscar  3) Accesos SSH  4) Log de cron"
    read -p "Opcion: " op
    case $op in
        1) ver_log ;;
        2) buscar_en_log ;;
        3) ver_accesos_ssh ;;
        4) ver_log_cron ;;
        *) echo "Opcion invalida" ;;
    esac
}

while true; do
    echo
    echo "===== ADMINISTRACION SERVIDOR ECOSYNC ====="
    echo "1) Usuarios"
    echo "2) Grupos"
    echo "3) Redes"
    echo "4) Firewall"
    echo "5) Base de datos"
    echo "6) Respaldos"
    echo "7) Logs"
    echo "0) Salir"
    read -p "Opcion: " opcion || exit 0
    case $opcion in
        1) menu_usuarios ;;
        2) menu_grupos ;;
        3) menu_redes ;;
        4) menu_firewall ;;
        5) menu_base ;;
        6) menu_respaldos ;;
        7) menu_logs ;;
        0) exit 0 ;;
        *) echo "Opcion invalida" ;;
    esac
done
