#!/bin/bash

ver_log() {
    tail -n 20 $LOG_FILE
}

buscar_en_log() {
    read -p "Texto a buscar: " texto
    grep -i "$texto" $LOG_FILE
}

ver_accesos_ssh() {
    grep "Accepted" /var/log/secure | tail -n 10
    grep "Failed" /var/log/secure | tail -n 10
}

ver_log_cron() {
    tail -n 20 /var/log/cron
}
