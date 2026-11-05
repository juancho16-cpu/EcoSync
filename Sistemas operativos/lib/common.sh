#!/bin/bash
set -o pipefail

LOG_DIR=/var/log/ecosync
LOG_FILE=$LOG_DIR/ecosync.log
BACKUP_DIR=/var/backups/ecosync
DB_NAME=sigeru
DIAS=7
SERVIDOR_RESPALDO=192.168.56.30
USUARIO_RESPALDO=backup
CARPETA_REMOTA=/srv/respaldos/ecosync

require_root() {
    if [ "$EUID" -ne 0 ]; then
        echo "Este script debe ejecutarse como root"
        exit 1
    fi
}

log_event() {
    mkdir -p $LOG_DIR
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}
