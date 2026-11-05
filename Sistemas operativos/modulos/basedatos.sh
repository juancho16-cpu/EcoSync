#!/bin/bash

estado_base() {
    systemctl status mysqld
}

crear_base() {
    mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
    log_event "Base de datos $DB_NAME creada"
    echo "Base de datos $DB_NAME lista"
}

crear_usuario_base() {
    read -p "Usuario de la base: " usuario
    read -s -p "Contrasena: " clave
    echo
    mysql -e "CREATE USER IF NOT EXISTS '$usuario'@'%' IDENTIFIED BY '$clave';"
    mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$usuario'@'%';"
    mysql -e "FLUSH PRIVILEGES;"
    log_event "Usuario de base $usuario creado"
    echo "Usuario $usuario creado"
}

listar_bases() {
    mysql -e "SHOW DATABASES;"
}

exportar_base() {
    mkdir -p $BACKUP_DIR
    archivo=$BACKUP_DIR/$DB_NAME-$(date +%Y%m%d-%H%M%S).sql.gz
    mysqldump $DB_NAME | gzip > $archivo
    log_event "Base exportada en $archivo"
    echo "Base exportada en $archivo"
}
