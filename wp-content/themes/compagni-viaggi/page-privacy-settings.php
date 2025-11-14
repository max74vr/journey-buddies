<?php
/**
 * Template Name: Impostazioni Privacy
 * Template for user privacy and GDPR settings
 */

// Require login
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$user = get_userdata($user_id);

// Get user consent data
$cookie_consent = get_user_meta($user_id, 'cdv_cookie_consent', true);
?>

<main class="site-main privacy-settings-page">
    <div class="page-header">
        <div class="container">
            <h1>🔒 Impostazioni Privacy e Dati</h1>
            <p>Gestisci le tue preferenze sulla privacy e i tuoi dati personali</p>
        </div>
    </div>

    <div class="container">
        <!-- Cookie Consent Section -->
        <div class="privacy-settings-section">
            <h2>🍪 Gestione Cookie</h2>

            <?php if ($cookie_consent) : ?>
                <div class="gdpr-notice">
                    <h4>Stato del Consenso</h4>
                    <p>
                        <strong>Ultima modifica:</strong> <?php echo esc_html(date('d/m/Y H:i', strtotime($cookie_consent['timestamp']))); ?><br>
                        <strong>Cookie Analitici:</strong> <?php echo $cookie_consent['analytics'] ? '✓ Accettati' : '✗ Rifiutati'; ?><br>
                        <strong>Cookie Marketing:</strong> <?php echo $cookie_consent['marketing'] ? '✓ Accettati' : '✗ Rifiutati'; ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="privacy-setting-item">
                <h3>Cookie Essenziali</h3>
                <p>Necessari per il funzionamento del sito (autenticazione, carrello, preferenze). Sempre attivi.</p>
                <label style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <input type="checkbox" checked disabled style="width: 20px; height: 20px;">
                    <strong>Abilitati (obbligatori)</strong>
                </label>
            </div>

            <div class="privacy-setting-item">
                <h3>Cookie Analitici</h3>
                <p>Ci aiutano a capire come utilizzi il sito per migliorare la tua esperienza.</p>
                <label style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <input type="checkbox" class="gdpr-consent-checkbox" data-consent-type="analytics"
                           <?php echo (isset($cookie_consent['analytics']) && $cookie_consent['analytics']) ? 'checked' : ''; ?>
                           style="width: 20px; height: 20px;">
                    <strong>Abilita Cookie Analitici</strong>
                </label>
            </div>

            <div class="privacy-setting-item">
                <h3>Cookie di Marketing</h3>
                <p>Utilizzati per mostrare contenuti pubblicitari personalizzati.</p>
                <label style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <input type="checkbox" class="gdpr-consent-checkbox" data-consent-type="marketing"
                           <?php echo (isset($cookie_consent['marketing']) && $cookie_consent['marketing']) ? 'checked' : ''; ?>
                           style="width: 20px; height: 20px;">
                    <strong>Abilita Cookie Marketing</strong>
                </label>
            </div>
        </div>

        <!-- Data Export Section -->
        <div class="privacy-settings-section">
            <h2>📦 Esporta i Tuoi Dati</h2>
            <div class="privacy-setting-item">
                <h3>Richiedi una Copia dei Tuoi Dati</h3>
                <p>
                    Ai sensi del GDPR, hai il diritto di ricevere una copia di tutti i dati personali che conserviamo su di te.
                    Questo include il tuo profilo, viaggi, messaggi, recensioni e altro ancora.
                </p>
                <p>
                    I dati saranno forniti in formato JSON, facilmente leggibile e portabile.
                </p>
                <div class="privacy-actions">
                    <button id="cdv-export-data" class="btn btn-primary">
                        📥 Scarica i Tuoi Dati
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Retention Section -->
        <div class="privacy-settings-section">
            <h2>⏱️ Conservazione dei Dati</h2>
            <div class="privacy-setting-item">
                <h3>Politica di Conservazione</h3>
                <p>
                    I tuoi dati personali vengono conservati per i seguenti periodi:
                </p>
                <ul style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                    <li><strong>Profilo utente:</strong> Fino alla cancellazione dell'account</li>
                    <li><strong>Viaggi pubblicati:</strong> Fino alla loro eliminazione manuale</li>
                    <li><strong>Messaggi:</strong> 2 anni dalla data di invio (poi eliminati automaticamente)</li>
                    <li><strong>Recensioni:</strong> Permanenti (anonimizzate alla cancellazione account)</li>
                    <li><strong>Log di sicurezza:</strong> 90 giorni</li>
                </ul>
            </div>
        </div>

        <!-- Data Processing Section -->
        <div class="privacy-settings-section">
            <h2>⚙️ Come Utilizziamo i Tuoi Dati</h2>
            <div class="privacy-setting-item">
                <h3>Finalità del Trattamento</h3>
                <p>Trattiamo i tuoi dati personali per le seguenti finalità:</p>
                <ul style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                    <li>✓ Fornire e gestire il servizio di ricerca compagni di viaggio</li>
                    <li>✓ Facilitare la comunicazione tra utenti</li>
                    <li>✓ Garantire la sicurezza della piattaforma</li>
                    <li>✓ Migliorare la qualità del servizio</li>
                    <li>✓ Inviare notifiche relative ai tuoi viaggi</li>
                    <li>✓ Rispettare obblighi legali</li>
                </ul>
                <p style="margin-top: 15px;">
                    <strong>Base giuridica:</strong> Esecuzione del contratto, legittimo interesse, consenso (per cookie non essenziali)
                </p>
            </div>
        </div>

        <!-- Account Deletion Section -->
        <div class="privacy-settings-section" style="border: 2px solid #f56565;">
            <h2 style="color: #c53030;">🗑️ Cancellazione Account e Dati</h2>
            <div class="privacy-setting-item">
                <h3>Diritto alla Cancellazione (Right to be Forgotten)</h3>
                <p>
                    Ai sensi del GDPR, hai il diritto di richiedere la cancellazione completa del tuo account e di tutti i dati associati.
                </p>
                <p>
                    <strong>Cosa succede quando richiedi la cancellazione:</strong>
                </p>
                <ul style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                    <li>Riceverai una conferma via email</li>
                    <li>Il tuo account verrà disattivato immediatamente</li>
                    <li>I tuoi dati personali verranno eliminati o anonimizzati entro 30 giorni</li>
                    <li>I viaggi pubblicati verranno rimossi</li>
                    <li>Le recensioni verranno anonimizzate (mantenute per trasparenza della community)</li>
                </ul>

                <div class="gdpr-notice" style="background: #fff5f5; border-color: #f56565; margin: 20px 0;">
                    <h4 style="color: #c53030;">⚠️ Attenzione</h4>
                    <p>
                        La cancellazione dell'account è <strong>irreversibile</strong>.
                        Una volta completata, non sarà possibile recuperare i tuoi dati.
                    </p>
                </div>

                <div class="privacy-actions">
                    <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" class="btn btn-secondary" target="_blank">
                        📄 Leggi la Privacy Policy
                    </a>
                    <button id="cdv-request-deletion" class="btn btn-danger">
                        🗑️ Richiedi Cancellazione Account
                    </button>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="privacy-settings-section">
            <h2>📧 Contatta il Data Protection Officer</h2>
            <div class="privacy-setting-item">
                <p>
                    Per qualsiasi domanda sulla privacy o sui tuoi dati personali, contatta il nostro Data Protection Officer:
                </p>
                <p style="margin-top: 15px;">
                    <strong>Email:</strong> <a href="mailto:privacy@compagnidiviaggi.com">privacy@compagnidiviaggi.com</a><br>
                    <strong>Risposta entro:</strong> 48 ore lavorative
                </p>
            </div>
        </div>
    </div>
</main>

<style>
.privacy-settings-page {
    padding: 2rem 0 4rem;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.page-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 3rem 0;
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    color: white;
    margin-bottom: 0.5rem;
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.95;
}

.privacy-settings-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.privacy-settings-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2d3748;
    font-size: 1.5rem;
}

.privacy-setting-item {
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 15px;
}

.privacy-setting-item:last-child {
    margin-bottom: 0;
}

.privacy-setting-item h3 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
    color: #2d3748;
}

.privacy-setting-item p {
    margin: 0 0 10px 0;
    color: #4a5568;
    font-size: 0.95rem;
    line-height: 1.6;
}

.privacy-setting-item p:last-child {
    margin-bottom: 0;
}

.privacy-setting-item ul {
    color: #4a5568;
    font-size: 0.95rem;
}

.privacy-actions {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.gdpr-notice {
    background: #eef2ff;
    border-left: 4px solid #667eea;
    padding: 15px 20px;
    border-radius: 6px;
    margin: 20px 0;
}

.gdpr-notice h4 {
    margin: 0 0 10px 0;
    color: #434190;
    font-size: 1rem;
}

.gdpr-notice p {
    margin: 0;
    color: #4a5568;
    font-size: 0.9rem;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .privacy-settings-section {
        padding: 20px;
    }

    .privacy-actions {
        flex-direction: column;
    }

    .privacy-actions .btn {
        width: 100%;
    }
}
</style>

<?php
get_footer();
