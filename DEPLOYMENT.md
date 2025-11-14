# Guida Deployment - www.compagnidiviaggi.com

## 📋 Requisiti Server

- **PHP**: 7.4 o superiore
- **MySQL**: 5.7 o superiore
- **Apache**: con mod_rewrite abilitato
- **HTTPS**: SSL/TLS certificato (Let's Encrypt consigliato)

## 🚀 Installazione Step-by-Step

### 1. Upload dei File

**Opzione A: Document Root su `public/` (CONSIGLIATO)**

Configura il tuo server affinché il **Document Root** punti a:
```
/path/to/compagni-di-viaggi/public
```

Struttura file sul server:
```
/var/www/compagnidiviaggi.com/
├── config/
├── database/
├── includes/
├── logs/
├── src/
├── uploads/
└── public/          ← DOCUMENT ROOT
    ├── index.php
    ├── index-demo.php
    ├── css/
    ├── js/
    └── .htaccess
```

**Opzione B: Tutto nella root (alternativa)**

Se non puoi cambiare il document root, sposta il contenuto di `public/` nella root:

```bash
# Sul server
cd /var/www/compagnidiviaggi.com
mv public/* .
mv public/.htaccess .
rmdir public
```

### 2. Configurazione Database

**A. Crea il database MySQL:**

```bash
mysql -u root -p
```

```sql
CREATE DATABASE compagni_di_viaggi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'compagni_user'@'localhost' IDENTIFIED BY 'password_sicura_qui';
GRANT ALL PRIVILEGES ON compagni_di_viaggi.* TO 'compagni_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**B. Importa lo schema:**

```bash
mysql -u compagni_user -p compagni_di_viaggi < database/schema.sql
```

### 3. Configurazione Applicazione

**A. Configura database:**

Modifica `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'compagni_di_viaggi');
define('DB_USER', 'compagni_user');
define('DB_PASS', 'la_tua_password');
```

**OPPURE** usa file `.env` (opzionale):

```bash
cp .env.example .env
nano .env
```

Modifica:
```env
DB_HOST=localhost
DB_NAME=compagni_di_viaggi
DB_USER=compagni_user
DB_PASS=la_tua_password_sicura
ENVIRONMENT=production
```

### 4. Permessi File

```bash
# Permessi cartelle
find . -type d -exec chmod 755 {} \;

# Permessi file
find . -type f -exec chmod 644 {} \;

# Cartella uploads scrivibile
chmod -R 755 uploads/
chown -R www-data:www-data uploads/

# Cartella logs scrivibile
chmod -R 755 logs/
chown -R www-data:www-data logs/

# File di configurazione protetti
chmod 600 config/database.php
chmod 600 .env 2>/dev/null || true
```

### 5. Configurazione Apache/Nginx

**Per Apache:**

Il file `.htaccess` in `public/` è già configurato.

Verifica che `mod_rewrite` sia abilitato:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Virtual Host esempio:**

```apache
<VirtualHost *:80>
    ServerName www.compagnidiviaggi.com
    ServerAlias compagnidiviaggi.com

    DocumentRoot /var/www/compagnidiviaggi.com/public

    <Directory /var/www/compagnidiviaggi.com/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/compagni-error.log
    CustomLog ${APACHE_LOG_DIR}/compagni-access.log combined
</VirtualHost>
```

**Per Nginx:**

```nginx
server {
    listen 80;
    server_name www.compagnidiviaggi.com compagnidiviaggi.com;

    root /var/www/compagnidiviaggi.com/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### 6. SSL/HTTPS (OBBLIGATORIO)

Installa Let's Encrypt:

```bash
sudo apt install certbot python3-certbot-apache

# Per Apache
sudo certbot --apache -d www.compagnidiviaggi.com -d compagnidiviaggi.com

# Per Nginx
sudo certbot --nginx -d www.compagnidiviaggi.com -d compagnidiviaggi.com
```

Il redirect HTTP→HTTPS è già configurato nel `.htaccess`.

### 7. Test

**A. Versione DEMO (senza database):**
```
https://www.compagnidiviaggi.com/index-demo.php
```

**B. Versione COMPLETA (con database):**
```
https://www.compagnidiviaggi.com/
```

### 8. Primo Accesso

1. Vai su: **https://www.compagnidiviaggi.com/register.php**
2. Registra il primo utente (diventerà admin)
3. Testa login, creazione viaggi, ecc.

## 🔒 Sicurezza Post-Installazione

1. **Rimuovi file sensibili:**
```bash
rm -f README.md
rm -f .env.example
rm -f database/schema.sql  # dopo averlo importato
```

2. **Proteggi cartelle:**
```bash
# Nega accesso a config, src, includes
# (già protetto da .htaccess in public/)
```

3. **Backup automatici:**
```bash
# Configura cron per backup database
0 2 * * * mysqldump -u compagni_user -p'password' compagni_di_viaggi | gzip > /backup/db-$(date +\%Y\%m\%d).sql.gz
```

4. **Monitora i log:**
```bash
tail -f logs/php-errors.log
tail -f /var/log/apache2/compagni-error.log
```

## 🐛 Troubleshooting

**Pagina bianca:**
- Controlla `logs/php-errors.log`
- Verifica permessi su `uploads/` e `logs/`

**Errore database:**
- Verifica credenziali in `config/database.php`
- Controlla che il database esista e sia importato

**CSS/JS non caricano:**
- Verifica che il document root punti a `public/`
- Controlla permessi file CSS/JS (644)

**403 Forbidden:**
- Verifica permessi cartelle (755)
- Controlla configurazione Apache/Nginx
- Verifica `.htaccess` presente in `public/`

## 📞 Supporto

Per problemi tecnici, controlla:
- Repository: https://github.com/max74vr/compagni-di-viaggi
- Documentazione: README.md

---

**Ultima revisione:** Novembre 2025
