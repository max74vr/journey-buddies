# 🚀 Compagni di Viaggi - Travel Importer

Plugin WordPress per importare viaggi da file JSON nel sistema Compagni di Viaggi.

## 📋 Caratteristiche

- ✅ Import viaggi da file JSON
- ✅ Validazione automatica dati
- ✅ Download immagini da Unsplash
- ✅ Scelta autore e stato pubblicazione
- ✅ Progress bar real-time
- ✅ Report dettagliato con errori
- ✅ Eliminazione bulk viaggi importati
- ✅ Plugin standalone (attiva/disattiva quando serve)

## 🔧 Installazione

1. **Assicurati che il plugin principale "Compagni di Viaggi" sia attivo**

2. **Attiva questo plugin:**
   - Dashboard → Plugin → Plugin installati
   - Trova "Compagni di Viaggi - Travel Importer"
   - Clicca "Attiva"

3. **Accedi all'importer:**
   - Dashboard → Strumenti → Import Viaggi JSON

## 📝 Come Usare

### Step 1: Genera il JSON

Usa ChatGPT con il prompt in `/TRAVEL_DATA_SCHEMA.md`:

```
1. Apri ChatGPT
2. Copia il prompt da TRAVEL_DATA_SCHEMA.md
3. ChatGPT genererà un JSON con 10 viaggi
4. Salva il JSON in un file (es: viaggi.json)
```

### Step 2: Importa i Viaggi

1. **Vai su:** Strumenti → Import Viaggi JSON

2. **Seleziona il file JSON**

3. **Configura le opzioni:**
   - ✅ Scarica immagini da Unsplash (opzionale)
   - Scegli autore (organizzatore viaggi)
   - Scegli stato post (publish/pending/draft)

4. **Clicca "Importa Viaggi"**

5. **Attendi il completamento** (progress bar)

6. **Vedi i risultati:**
   - Viaggi importati con successo
   - Eventuali errori

### Step 3: Verifica i Viaggi

- Vai su: Viaggi → Tutti i viaggi
- Controlla che i dati siano corretti
- I viaggi importati hanno il meta `_cdv_imported = 1`

### Step 4: Pulizia (Opzionale)

Se vuoi eliminare tutti i viaggi importati:

1. Vai su: Strumenti → Import Viaggi JSON
2. Clicca "🗑️ Elimina tutti i viaggi importati"
3. Conferma l'eliminazione

**Attenzione:** Elimina SOLO i viaggi con meta `_cdv_imported = 1`

## 📄 Formato JSON

Il file JSON deve contenere un array di oggetti viaggio:

```json
[
  {
    "title": "Weekend a Barcellona: Gaudì, Tapas e Movida",
    "content": "Esploreremo la magica Barcellona in tre giorni...",
    "start_date": "2025-06-15",
    "end_date": "2025-06-17",
    "destination": "Barcellona",
    "country": "Spagna",
    "budget": 450,
    "max_participants": 8,
    "tipo_viaggio": ["Città d'Arte", "Food & Wine"],
    "image_search": "barcelona sagrada familia"
  },
  {
    "title": "Trekking in Marocco: 7 giorni nel deserto del Sahara",
    "content": "Un'avventura indimenticabile tra le dune...",
    "start_date": "2025-09-10",
    "end_date": "2025-09-16",
    "destination": "Merzouga",
    "country": "Marocco",
    "budget": 890,
    "max_participants": 10,
    "tipo_viaggio": ["Avventura", "Zaino in Spalla"],
    "image_search": "morocco sahara desert dunes"
  }
]
```

### Campi Obbligatori

- `title` - Titolo del viaggio
- `content` - Descrizione completa (min 200 caratteri)
- `start_date` - Data inizio (formato YYYY-MM-DD)
- `end_date` - Data fine (formato YYYY-MM-DD)
- `destination` - Città o località principale
- `country` - Paese

### Campi Opzionali

- `budget` - Budget stimato in euro (numero intero)
- `max_participants` - Numero massimo partecipanti (2-20)
- `tipo_viaggio` - Array di tipi (es: ["Avventura", "Mare"])
- `image_search` - Query per cercare immagine su Unsplash

## 🎨 Download Immagini

Se attivi "Scarica immagini da Unsplash":

- Il plugin cerca su Unsplash usando `image_search`
- Scarica l'immagine (1200x600)
- La imposta come featured image del viaggio
- Se fallisce, continua senza bloccarsi

**Nota:** Download immagini richiede 5-10 secondi per immagine.

## ⚙️ Opzioni Avanzate

### Autore Viaggi

Scegli quale utente risulterà come organizzatore:
- Di default: Admin (ID 1)
- Puoi selezionare qualsiasi admin o viaggiatore

### Stato Pubblicazione

- **Pubblicato (publish):** Viaggi visibili immediatamente
- **In attesa (pending):** Richiede approvazione admin
- **Bozza (draft):** Salvati come bozze

## 🐛 Troubleshooting

### Errore "Plugin principale non attivo"

**Soluzione:** Attiva prima il plugin "Compagni di Viaggi"

### Errore parsing JSON

**Soluzione:**
- Valida il JSON su jsonlint.com
- Controlla virgole e parentesi
- Assicurati che sia un array valido

### Immagini non scaricate

**Possibili cause:**
- Problema connessione Unsplash
- `allow_url_fopen` disabilitato su server
- Timeout PHP troppo basso

**Soluzione:** Disattiva "Scarica immagini" e caricale manualmente

### Import lento

**Cause:**
- Download immagini attivo
- Molti viaggi (>20)
- Server lento

**Soluzione:**
- Disattiva download immagini
- Importa in batch più piccoli (5-10 viaggi)
- Aumenta timeout PHP in wp-config.php:
  ```php
  set_time_limit(300);
  ```

## 🔒 Sicurezza

- ✅ Validazione nonce AJAX
- ✅ Capability check (solo admin)
- ✅ Sanitizzazione input
- ✅ Validazione campi obbligatori
- ✅ SQL injection prevention (meta API)

## 🗑️ Disinstallazione

1. **Elimina viaggi importati:** Usa il pulsante nell'admin
2. **Disattiva plugin:** Dashboard → Plugin → Disattiva
3. **Elimina plugin:** Dashboard → Plugin → Elimina

**Nota:** I viaggi importati rimarranno nel database se non li elimini prima.

## 📊 Metadati Viaggi Importati

Ogni viaggio importato ha:

```
_cdv_imported = '1'        // Flag importato
_cdv_import_date = 'datetime'  // Data import
```

Questi meta fields permettono di:
- Identificare viaggi importati
- Eliminarli in bulk
- Statistiche import

## 🤝 Supporto

Per problemi o domande:
- Apri issue su GitHub
- Email: (la tua email)

## 📝 Changelog

### 1.0.0 - 2025-01-08
- Release iniziale
- Import JSON
- Download immagini Unsplash
- Eliminazione bulk
- Validazione dati
- Progress bar
- Report dettagliati

## 📄 Licenza

GPL v2 or later
