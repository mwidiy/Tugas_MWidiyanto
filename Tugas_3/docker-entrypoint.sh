#!/bin/bash
set -e

# Disable conflicting MPMs and ensure mpm_prefork is active
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Generate .env dynamically for production
cat <<EOF > /var/www/html/.env
CI_ENVIRONMENT = production
app.baseURL = "${APP_URL:-https://tugas.quacxel.my.id/}"
app.forceGlobalSecureRequests = true
app.indexPage = ""

database.default.hostname = "${MYSQLHOST:-mysql.railway.internal}"
database.default.database = "${MYSQLDATABASE:-railway}"
database.default.username = "${MYSQLUSER:-root}"
database.default.password = "${MYSQLPASSWORD}"
database.default.DBDriver = MySQLi
database.default.DBPrefix = ""
database.default.port = ${MYSQLPORT:-3306}
EOF

# Ensure writable permissions
chown -R www-data:www-data /var/www/html/writable
chmod -R 775 /var/www/html/writable

# Wait for MySQL database to become ready
echo "[INFO] Checking MySQL connection..."
for i in {1..30}; do
    if php -r '
        $h = getenv("MYSQLHOST") ?: "mysql.railway.internal";
        $u = getenv("MYSQLUSER") ?: "root";
        $p = getenv("MYSQLPASSWORD");
        $d = getenv("MYSQLDATABASE") ?: "railway";
        $port = (int)(getenv("MYSQLPORT") ?: 3306);
        $c = @new mysqli($h, $u, $p, $d, $port);
        if (!$c->connect_error) { exit(0); }
        exit(1);
    '; then
        echo "[OK] MySQL is connected and ready!"
        break
    fi
    echo "[WAIT] Waiting for MySQL database... ($i/30)"
    sleep 2
done

# Run migrations
echo "[INFO] Running database migrations..."
php spark migrate --all || true

# Run seeders ONLY if tables are empty (prevent duplicate data on container restart)
echo "[INFO] Checking if seed data already exists..."
USER_COUNT=$(php -r '
    $h = getenv("MYSQLHOST") ?: "mysql.railway.internal";
    $u = getenv("MYSQLUSER") ?: "root";
    $p = getenv("MYSQLPASSWORD");
    $d = getenv("MYSQLDATABASE") ?: "railway";
    $port = (int)(getenv("MYSQLPORT") ?: 3306);
    $c = new mysqli($h, $u, $p, $d, $port);
    $r = $c->query("SELECT COUNT(*) as cnt FROM users");
    $row = $r ? $r->fetch_assoc() : ["cnt" => 0];
    echo $row["cnt"];
')

if [ "$USER_COUNT" -eq "0" ]; then
    echo "[INFO] Tables are empty. Running database seeders..."
    php spark db:seed DatabaseSeeder || true
    echo "[OK] Seed data inserted."
else
    echo "[SKIP] Seed data already exists ($USER_COUNT users found). Skipping seeder."
fi

# Configure Apache listening port and VirtualHost for Railway
PORT=${PORT:-8080}
echo "Listen ${PORT}" > /etc/apache2/ports.conf

cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

echo "[OK] Starting Apache HTTP Server on port ${PORT}..."
exec apache2-foreground