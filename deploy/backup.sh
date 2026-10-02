#!/usr/bin/env bash
# Sauvegarde quotidienne de la base et des fichiers envoyés (pièces d'identité, photos).
# Cron (crontab -e, utilisateur ubuntu) : 0 3 * * * bash /var/www/go-api/deploy/backup.sh
# Pense à rapatrier /var/backups/go HORS d'Oracle (rsync/scp depuis ton PC, ou rclone).
set -euo pipefail
DEST=/var/backups/go
STAMP=$(date +%F)

# Identifiants lus dans ~/.my.cnf ([mysqldump] user=... password=...)
mysqldump --single-transaction go | gzip > "$DEST/db-$STAMP.sql.gz"
tar -czf "$DEST/storage-$STAMP.tar.gz" -C /var/www/go-api storage/app

# On garde 14 jours
find "$DEST" -type f -mtime +14 -delete
