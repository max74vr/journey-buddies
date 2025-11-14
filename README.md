# Compagni di Viaggi ✈️

Una piattaforma web per trovare compagni di viaggio e organizzare avventure insieme. Non solo "cerco compagno di viaggio", ma una vera community viva di viaggiatori.

## 🌟 Caratteristiche Principali

### Per gli Utenti
- **Profili Viaggiatori**: Crea il tuo profilo con foto, bio, stili di viaggio, lingue parlate e preferenze
- **Sistema di Verifica**: Verifica la tua identità per aumentare la fiducia nella community
- **Badge e Reputazione**: Ottieni badge e costruisci la tua reputazione tramite recensioni
- **Ricerca Avanzata**: Trova viaggi per destinazione, date, tipo di viaggio e budget

### Bacheca Viaggi
- **Crea Viaggi**: Pubblica i tuoi piani di viaggio con tutti i dettagli
- **Unisciti ai Viaggi**: Partecipa a viaggi già pianificati da altri utenti
- **Filtri Intelligenti**: Cerca per destinazione, periodo, budget, tipo di viaggio
- **Gestione Partecipanti**: Sistema di richieste e accettazioni per mantenere il controllo

### Sistema di Recensioni
- **Valutazioni a 4 Dimensioni**:
  - Puntualità
  - Spirito di gruppo
  - Rispetto degli altri
  - Capacità di adattamento
- **Commenti Opzionali**: Aggiungi feedback dettagliati
- **Reputazione Automatica**: Il punteggio viene calcolato automaticamente

### Chat Integrata
- **Chat di Gruppo**: Ogni viaggio ha la sua chat dedicata
- **Messaggi in Tempo Reale**: Sistema di polling per aggiornamenti automatici
- **Anti-Spam**: Protezione contro messaggi eccessivi
- **Notifiche**: Badge per messaggi non letti

### Homepage Dinamica
- **Ricerca Veloce**: Cerca viaggi direttamente dalla homepage
- **Storie in Evidenza**: Scopri esperienze di altri viaggiatori
- **Profili Consigliati**: Trova viaggiatori con interessi simili
- **Viaggi Recenti**: Visualizza le ultime opportunità di viaggio

## 🛠 Tecnologie Utilizzate

- **Backend**: PHP 7.4+ (Pattern MVC)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3 (Flexbox/Grid), JavaScript (Vanilla)
- **Design**: Responsive, Mobile-First
- **Sicurezza**: Password hashing (bcrypt), CSRF protection, SQL injection prevention

## 📋 Requisiti di Sistema

- PHP 7.4 o superiore
- MySQL 5.7 o superiore
- Apache 2.4+ con mod_rewrite abilitato
- PHP Extensions richieste:
  - PDO
  - pdo_mysql
  - mbstring
  - fileinfo
  - gd (per la manipolazione immagini)

## 🚀 Installazione

### 1. Clona il Repository

```bash
git clone https://github.com/max74vr/compagni-di-viaggi.git
cd compagni-di-viaggi
```

### 2. Configura il Database

Crea un database MySQL:

```sql
CREATE DATABASE compagni_di_viaggi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importa lo schema del database:

```bash
mysql -u root -p compagni_di_viaggi < database/schema.sql
```

### 3. Configurazione

Copia e modifica il file di configurazione:

```bash
# Modifica config/database.php con le tue credenziali MySQL
```

Oppure usa le variabili d'ambiente:

```bash
export DB_HOST=localhost
export DB_NAME=compagni_di_viaggi
export DB_USER=your_username
export DB_PASS=your_password
export SITE_URL=http://localhost
```

### 4. Permessi

Assicurati che la cartella uploads sia scrivibile:

```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

### 5. Configurazione Apache

Il file `.htaccess` è già presente nella cartella `public/`. Assicurati che `mod_rewrite` sia abilitato:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Configura il VirtualHost per puntare alla cartella `public/`:

```apache
<VirtualHost *:80>
    ServerName compagni-di-viaggi.local
    DocumentRoot /path/to/compagni-di-viaggi/public

    <Directory /path/to/compagni-di-viaggi/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Avvia l'Applicazione

Apri il browser e visita:
```
http://localhost/compagni-di-viaggi/public/
```

Oppure con il tuo VirtualHost:
```
http://compagni-di-viaggi.local/
```

## 📁 Struttura del Progetto

```
compagni-di-viaggi/
├── config/                 # Configurazione
│   ├── config.php         # Configurazione generale
│   └── database.php       # Connessione database
├── database/              # Database
│   └── schema.sql         # Schema SQL
├── includes/              # File comuni
│   └── helpers.php        # Funzioni helper
├── public/                # Documenti pubblici (Document Root)
│   ├── css/              # Fogli di stile
│   │   └── style.css
│   ├── js/               # JavaScript
│   │   └── main.js
│   ├── index.php         # Homepage
│   ├── login.php         # Login
│   ├── register.php      # Registrazione
│   ├── dashboard.php     # Dashboard utente
│   ├── travels.php       # Lista viaggi
│   ├── travel.php        # Dettaglio viaggio
│   ├── profile.php       # Profilo utente
│   ├── chats.php         # Lista chat
│   └── .htaccess         # Configurazione Apache
├── src/                   # Codice sorgente
│   ├── Controllers/       # Controller MVC
│   │   ├── AuthController.php
│   │   ├── TravelController.php
│   │   ├── ProfileController.php
│   │   ├── ReviewController.php
│   │   └── ChatController.php
│   ├── Models/            # Modelli database
│   │   ├── User.php
│   │   ├── TravelPost.php
│   │   ├── Review.php
│   │   └── Chat.php
│   └── Views/             # Template HTML
│       ├── layouts/
│       │   ├── header.php
│       │   └── footer.php
│       └── auth/
│           ├── login.php
│           └── register.php
├── uploads/               # File caricati
│   ├── profiles/         # Foto profilo
│   ├── travels/          # Foto viaggi
│   └── verifications/    # Documenti verifica
└── README.md             # Questo file
```

## 🎯 Come Usare l'Applicazione

### Registrazione e Login

1. Vai su `/register.php`
2. Compila il form di registrazione (minimo 18 anni)
3. Riceverai automaticamente il badge "Early Adopter"
4. Completa il tuo profilo aggiungendo:
   - Bio personale
   - Stili di viaggio preferiti
   - Lingue parlate
   - Preferenze di viaggio

### Creare un Viaggio

1. Accedi al tuo account
2. Clicca su "Crea viaggio"
3. Compila tutti i dettagli:
   - Destinazione e paese
   - Date (inizio e fine)
   - Tipo di viaggio (avventura, mare, città, ecc.)
   - Budget stimato
   - Numero massimo di partecipanti
   - Descrizione dettagliata
4. Carica una foto di copertina (opzionale)
5. Pubblica il viaggio

### Unirsi a un Viaggio

1. Naviga su `/travels.php`
2. Usa i filtri per trovare il viaggio ideale
3. Clicca su "Vedi dettagli"
4. Leggi la descrizione e visualizza il profilo dell'organizzatore
5. Clicca su "Richiedi di partecipare"
6. Scrivi un messaggio di presentazione
7. Attendi l'approvazione dell'organizzatore

### Chat di Gruppo

1. Una volta accettato in un viaggio, accedi alla chat
2. Comunica con gli altri partecipanti
3. Organizza i dettagli del viaggio
4. Il sistema previene lo spam automaticamente

### Lasciare Recensioni

1. Al termine del viaggio, l'organizzatore cambia lo stato in "Completato"
2. Vai su "Recensioni da fare" nel menu
3. Valuta ogni partecipante su 4 criteri (1-5 stelle):
   - Puntualità
   - Spirito di gruppo
   - Rispetto degli altri
   - Capacità di adattamento
4. Aggiungi un commento (opzionale)
5. La reputazione viene aggiornata automaticamente

## 🔒 Sicurezza

- **Password**: Hash con bcrypt
- **SQL Injection**: Prepared statements PDO
- **XSS**: Sanitizzazione input e output
- **CSRF**: Token di protezione per form critici
- **File Upload**: Validazione tipo e dimensione
- **Session**: Gestione sicura delle sessioni
- **Headers**: Security headers configurati

## 🎨 Personalizzazione

### Colori

Modifica le variabili CSS in `public/css/style.css`:

```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --success-color: #48bb78;
    --error-color: #f56565;
}
```

### Upload Limits

Modifica in `config/config.php`:

```php
define('ITEMS_PER_PAGE', 12);
define('MAX_MESSAGE_LENGTH', 2000);
define('SPAM_THRESHOLD', 10);
```

## 📊 Database Schema

Il database è composto da 13 tabelle principali:

- **users**: Profili utenti
- **user_preferences**: Preferenze di viaggio
- **user_languages**: Lingue parlate
- **user_badges**: Badge ottenuti
- **travel_posts**: Post dei viaggi
- **travel_participants**: Partecipanti ai viaggi
- **reviews**: Recensioni reciproche
- **chat_groups**: Gruppi chat
- **chat_group_members**: Membri dei gruppi
- **chat_messages**: Messaggi
- **featured_stories**: Storie in evidenza

Vedi `database/schema.sql` per i dettagli completi.

## 🐛 Troubleshooting

### Errore di connessione al database

Verifica le credenziali in `config/database.php` o le variabili d'ambiente.

### Errore 404 sulle pagine

Assicurati che `mod_rewrite` sia abilitato e che `.htaccess` sia presente in `public/`.

### Upload falliti

Verifica i permessi della cartella `uploads/`:
```bash
chmod -R 755 uploads/
```

### Sessione non funziona

Verifica che PHP possa scrivere nella directory delle sessioni:
```bash
sudo chmod 1733 /var/lib/php/sessions
```

## 🤝 Contribuire

Questo è un progetto di esempio. Per contribuire:

1. Fork il repository
2. Crea un branch per la tua feature (`git checkout -b feature/AmazingFeature`)
3. Commit le modifiche (`git commit -m 'Add some AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

## 📝 To-Do / Funzionalità Future

- [ ] Sistema di notifiche push
- [ ] App mobile (React Native / Flutter)
- [ ] Integrazione con API di prenotazione voli/hotel
- [ ] Sistema di pagamento per viaggi organizzati
- [ ] Foto gallery per ogni viaggio
- [ ] Blog/storie di viaggio
- [ ] Mappa interattiva delle destinazioni
- [ ] Integrazione social login (Google, Facebook)
- [ ] Sistema di referral/inviti
- [ ] Admin panel per moderazione

## 📄 Licenza

Questo progetto è open source. Sentiti libero di usarlo, modificarlo e distribuirlo.

## 👥 Autori

- **Max74vr** - *Initial work* - [GitHub](https://github.com/max74vr)

## 📧 Supporto

Per domande o supporto, apri un issue su GitHub.

## 🙏 Ringraziamenti

- Grazie alla community dei viaggiatori per l'ispirazione
- Font: Google Fonts (Poppins)
- Icone: Emoji Unicode

---

**Buon viaggio! ✈️🌍**
