# shared settings for deploy/rollback/lint (sourced)
REPO="${GP_REPO:-/srv/gp-dev/giganticprint}"
WPC="${GP_WPCONTENT:-/home/giganticprints/public_html/wp-content}"
PHP="${GP_PHP:-/opt/cpanel/ea-php82/root/usr/bin/php}"
OWNER="giganticprints:giganticprints"
BACKUP_ROOT="${GP_BACKUP_ROOT:-/root/backups/giganticprints/deploys}"
# repo dir (relative to repo root) == path relative to wp-content
TARGETS="plugins/printcart-b2bstore plugins/signedge-designer-ui plugins/cmsmart-ui-override themes/storecmsmart"
RSYNC_EXCL=(--exclude=.git --exclude=node_modules --exclude='*.log' --exclude=.DS_Store)
