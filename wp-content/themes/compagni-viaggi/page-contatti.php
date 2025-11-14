<?php
/**
 * Template Name: Contatti
 *
 * Contact page with CF7 support and general info
 */

get_header();
?>

<main class="site-main contact-page">
    <div class="page-header">
        <div class="container">
            <h1>📧 Contattaci</h1>
            <p>Siamo qui per aiutarti! Scrivici per qualsiasi domanda o suggerimento</p>
        </div>
    </div>

    <div class="container">
        <div class="contact-layout">

            <!-- Contact Form Section -->
            <div class="contact-form-section">
                <div class="section-card">
                    <h2>Invia un Messaggio</h2>
                    <p>Compila il form qui sotto e ti risponderemo il prima possibile</p>

                    <?php
                    // Display page content (where admin can add CF7 shortcode)
                    if (have_posts()) :
                        while (have_posts()) : the_post();
                            the_content();
                        endwhile;
                    else :
                        // Fallback if no CF7 shortcode is added
                        ?>
                        <div class="no-form-message">
                            <p><strong>📝 Nessun form di contatto configurato</strong></p>
                            <p>L'amministratore può aggiungere un form Contact Form 7 modificando questa pagina e inserendo lo shortcode del form nel contenuto.</p>
                            <p>Esempio: <code>[contact-form-7 id="123" title="Contact form 1"]</code></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Info Sidebar -->
            <aside class="contact-sidebar">

                <!-- Contact Info Card -->
                <div class="sidebar-card">
                    <h3>📍 Informazioni di Contatto</h3>

                    <div class="contact-info-list">
                        <div class="contact-info-item">
                            <span class="info-icon">📧</span>
                            <div class="info-content">
                                <strong>Email</strong>
                                <a href="mailto:info@compagnidiviaggi.com">info@compagnidiviaggi.com</a>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <span class="info-icon">🌐</span>
                            <div class="info-content">
                                <strong>Sito Web</strong>
                                <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank">
                                    <?php echo esc_html(parse_url(home_url('/'), PHP_URL_HOST)); ?>
                                </a>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <span class="info-icon">🕐</span>
                            <div class="info-content">
                                <strong>Orari Risposta</strong>
                                <p>Lun-Ven: 9:00 - 18:00<br>Sab-Dom: Chiuso</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Card -->
                <div class="sidebar-card">
                    <h3>❓ Domande Frequenti</h3>

                    <div class="faq-list">
                        <details class="faq-item">
                            <summary>Come funziona la piattaforma?</summary>
                            <p>Compagni di Viaggi è una piattaforma che mette in contatto persone che vogliono viaggiare insieme. Gli organizzatori pubblicano i loro viaggi e i viaggiatori interessati possono richiedere di partecipare.</p>
                        </details>

                        <details class="faq-item">
                            <summary>È sicuro viaggiare con sconosciuti?</summary>
                            <p>La piattaforma facilita l'incontro ma la responsabilità di organizzare e partecipare ai viaggi è degli utenti. Ti consigliamo di verificare sempre l'identità e l'affidabilità delle persone prima di impegnarti.</p>
                        </details>

                        <details class="faq-item">
                            <summary>Quanto costa usare la piattaforma?</summary>
                            <p>La registrazione e l'utilizzo base della piattaforma sono completamente gratuiti. Gli eventuali costi sono relativi ai viaggi stessi (trasporti, alloggi, etc).</p>
                        </details>

                        <details class="faq-item">
                            <summary>Come posso segnalare un problema?</summary>
                            <p>Puoi usare questo form di contatto oppure segnalare contenuti inappropriati direttamente dalla pagina del viaggio usando i pulsanti di moderazione.</p>
                        </details>
                    </div>
                </div>

                <!-- Quick Links Card -->
                <div class="sidebar-card">
                    <h3>🔗 Link Utili</h3>

                    <ul class="quick-links">
                        <li><a href="<?php echo esc_url(home_url('/viaggi')); ?>">✈️ Esplora Viaggi</a></li>
                        <li><a href="<?php echo esc_url(home_url('/registrazione')); ?>">✨ Registrati</a></li>
                        <li><a href="<?php echo esc_url(home_url('/privacy-policy')); ?>">🔒 Privacy Policy</a></li>
                        <li><a href="<?php echo esc_url(home_url('/termini-condizioni')); ?>">📜 Termini e Condizioni</a></li>
                    </ul>
                </div>

            </aside>

        </div>
    </div>
</main>

<style>
.contact-page {
    background: var(--bg-light);
    min-height: 80vh;
    padding-bottom: calc(var(--spacing-unit) * 8);
}

.page-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: calc(var(--spacing-unit) * 6) 0;
    text-align: center;
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.page-header h1 {
    color: white;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.95;
}

.contact-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: calc(var(--spacing-unit) * 4);
    align-items: start;
}

.section-card {
    background: white;
    padding: calc(var(--spacing-unit) * 4);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
}

.section-card h2 {
    margin-bottom: calc(var(--spacing-unit) * 2);
    color: var(--primary-color);
}

.section-card > p {
    color: var(--text-medium);
    margin-bottom: calc(var(--spacing-unit) * 4);
}

.no-form-message {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: 8px;
}

.no-form-message code {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.9em;
}

.sidebar-card {
    background: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    margin-bottom: calc(var(--spacing-unit) * 3);
}

.sidebar-card h3 {
    margin-bottom: calc(var(--spacing-unit) * 3);
    padding-bottom: calc(var(--spacing-unit) * 2);
    border-bottom: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.contact-info-list {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 3);
}

.contact-info-item {
    display: flex;
    gap: calc(var(--spacing-unit) * 2);
    align-items: flex-start;
}

.info-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-content strong {
    display: block;
    margin-bottom: calc(var(--spacing-unit) * 0.5);
    color: var(--text-dark);
}

.info-content a {
    color: var(--primary-color);
    text-decoration: none;
}

.info-content a:hover {
    text-decoration: underline;
}

.info-content p {
    color: var(--text-medium);
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.6;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 2);
}

.faq-item {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: calc(var(--spacing-unit) * 2);
    cursor: pointer;
    transition: all 0.3s;
}

.faq-item:hover {
    border-color: var(--primary-color);
    background: rgba(var(--primary-rgb), 0.02);
}

.faq-item summary {
    font-weight: 600;
    color: var(--text-dark);
    cursor: pointer;
    user-select: none;
}

.faq-item p {
    margin-top: calc(var(--spacing-unit) * 2);
    color: var(--text-medium);
    line-height: 1.6;
    font-size: 0.95rem;
}

.faq-item[open] {
    background: rgba(var(--primary-rgb), 0.05);
    border-color: var(--primary-color);
}

.quick-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 1.5);
}

.quick-links li a {
    display: block;
    padding: calc(var(--spacing-unit) * 1.5);
    background: var(--bg-light);
    border-radius: 8px;
    color: var(--text-dark);
    text-decoration: none;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.quick-links li a:hover {
    background: rgba(var(--primary-rgb), 0.1);
    border-left-color: var(--primary-color);
    transform: translateX(5px);
}

/* CF7 Form Styling */
.wpcf7-form {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 2.5);
}

.wpcf7-form p {
    margin: 0;
}

.wpcf7-form label {
    display: block;
    margin-bottom: calc(var(--spacing-unit) * 1);
    font-weight: 500;
    color: var(--text-dark);
}

.wpcf7-form input[type="text"],
.wpcf7-form input[type="email"],
.wpcf7-form input[type="tel"],
.wpcf7-form textarea {
    width: 100%;
    padding: calc(var(--spacing-unit) * 1.5);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.3s;
}

.wpcf7-form input:focus,
.wpcf7-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
}

.wpcf7-form textarea {
    min-height: 150px;
    resize: vertical;
}

.wpcf7-form input[type="submit"] {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: calc(var(--spacing-unit) * 2) calc(var(--spacing-unit) * 4);
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.wpcf7-form input[type="submit"]:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.wpcf7-not-valid-tip {
    color: var(--error-color);
    font-size: 0.875rem;
    margin-top: calc(var(--spacing-unit) * 0.5);
}

.wpcf7-response-output {
    margin-top: calc(var(--spacing-unit) * 3);
    padding: calc(var(--spacing-unit) * 2);
    border-radius: 8px;
    border: 2px solid;
}

.wpcf7-mail-sent-ok {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.wpcf7-validation-errors,
.wpcf7-mail-sent-ng {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

@media (max-width: 968px) {
    .contact-layout {
        grid-template-columns: 1fr;
    }

    .section-card {
        padding: calc(var(--spacing-unit) * 3);
    }
}
</style>

<?php get_footer(); ?>
