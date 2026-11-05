#!/bin/bash

respaldo_manual() {
    mkdir -p $BACKUP_DIR
    fecha=$(date +%Y%m%d-%H%M%S)
    mysqldump $DB_NAME | gzip > $BACKUP_DIR/base-$fecha.sql.gz
    tar -czf $BACKUP_DIR/archivos-$fecha.tar.gz /opt/ecosync
    log_event "Respaldo manual $fecha"
    echo "Respaldo guardado en $BACKUP_DIR"
}

enviar_respaldo() {
    scp $BACKUP_DIR/* $USUARIO_RESPALDO@$SERVIDOR_RESPALDO:$CARPETA_REMOTA/
    log_event "Respaldos enviados a $SERVIDOR_RESPALDO"
    echo "Respaldos enviados a $SERVIDOR_RESPALDO"
}

listar_respaldos() {
    ls -lh $BACKUP_DIR
}

borrar_respaldos_viejos() {
    find $BACKUP_DIR -type f -mtime +$DIAS -delete
    log_event "Respaldos de mas de $DIAS dias eliminados"
    echo "Respaldos viejos eliminados"
}
