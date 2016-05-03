#!/bin/bash

ROOT_PATH="../"

BACKUP_PATH="backups/"
BACKUP_NAME="craft-backup-$(date +%Y-%m-%d)"

DB_USER="sandbox_dbusr"
DB_PASS="b(W7NXCF@4UJ"
DB_NAME="sandbox_craft"

# Included directories
includes=(craft)
includes+=(public_html)

# Excluded directories
excludes=(--exclude=craft/storage)
excludes+=(--exclude=public_html/uploads)

# Set start time
start=$(date '+%s')

# Backup files
FILE_BACKUP_NAME="$BACKUP_NAME".tar.gz

if ! tar -czf "$FILE_BACKUP_NAME" "${includes[@]/#/$ROOT_PATH}" "${excludes[@]/=/=$ROOT_PATH}"; then
	file_status="Files: backup failed"
elif ! mv "$FILE_BACKUP_NAME" "$BACKUP_PATH"; then
	file_status="Files: move failed"
else
	file_status="Files: $(ls -lah  $BACKUP_PATH$FILE_BACKUP_NAME | awk '{print $5}') backed up in $((`date '+%s'` - $start)) seconds"
fi

echo "$file_status"

# Reset start time
start=$(date '+%s')

# Backup database
DB_BACKUP_NAME="$BACKUP_NAME".sql

if ! mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DB_BACKUP_NAME"; then
	mysql_status="Database: backup failed"
elif ! mv "$DB_BACKUP_NAME" "$BACKUP_PATH"; then
	mysql_status="Database: move failed"
else
	mysql_status="Database: $(ls -lah  $BACKUP_PATH$DB_BACKUP_NAME | awk '{print $5}') backed up in $((`date '+%s'` - $start)) seconds"
fi

echo "$mysql_status"