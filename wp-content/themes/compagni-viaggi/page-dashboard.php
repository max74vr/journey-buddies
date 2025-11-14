<?php
/**
 * Template Name: Dashboard
 * Template Gestione Dashboard Viaggiatore
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/registrazione/'));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_approved = get_user_meta($current_user->ID, 'cdv_user_approved', true);

// Users are now auto-approved (value is '1'), no need to check
// if ($user_approved !== '1' && $user_approved !== 'approved') {
//     wp_redirect(home_url('/profilo-in-attesa/'));
//     exit;
// }

// Query viaggi organizzati dall'utente
$my_travels = new WP_Query(array(
    'post_type' => 'viaggio',
    'author' => $current_user->ID,
    'post_status' => array('publish', 'pending', 'draft'),
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
));

// Count user's travels for statistics tab visibility
$user_travels_count = $my_travels->post_count;

// Query viaggi a cui partecipo
global $wpdb;
$participants_table = $wpdb->prefix . 'cdv_travel_participants';
$participated_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT travel_id FROM $participants_table WHERE user_id = %d AND status = 'accepted'",
    $current_user->ID
));

$participated_travels = null;
if (!empty($participated_ids)) {
    $participated_travels = new WP_Query(array(
        'post_type' => 'viaggio',
        'post__in' => $participated_ids,
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ));
}

// Conta richieste pendenti RICEVUTE (per organizzatori)
$pending_requests = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, t.post_title, u.display_name, u.user_login
    FROM $participants_table p
    LEFT JOIN {$wpdb->posts} t ON p.travel_id = t.ID
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE t.post_author = %d AND p.status = 'pending' AND t.post_status = 'publish'
    ORDER BY p.requested_at DESC",
    $current_user->ID
));

// Richieste pendenti INVIATE (per viaggiatori)
$my_pending_requests = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, t.post_title, t.post_author, u.display_name as organizer_name
    FROM $participants_table p
    LEFT JOIN {$wpdb->posts} t ON p.travel_id = t.ID
    LEFT JOIN {$wpdb->users} u ON t.post_author = u.ID
    WHERE p.user_id = %d AND p.status = 'pending' AND t.post_status = 'publish'
    ORDER BY p.requested_at DESC",
    $current_user->ID
));

// Query racconti dell'utente
$my_stories = new WP_Query(array(
    'post_type' => 'racconto',
    'author' => $current_user->ID,
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
));

// Get unread messages count
$unread_messages_count = CDV_Private_Messages::get_unread_count($current_user->ID);

// Get pending reviews
$pending_reviews = CDV_Reviews::get_pending_reviews($current_user->ID);
$pending_reviews_count = count($pending_reviews);

// Get received reviews
$received_reviews = CDV_Reviews::get_user_reviews($current_user->ID, 20);
?>

<main class="site-main dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>Benvenuto, <?php echo esc_html($current_user->user_login); ?>!</h1>
                <p>Gestisci i tuoi viaggi e le richieste di partecipazione</p>
            </div>
            <a href="<?php echo CDV_User_Profiles::get_profile_url($current_user->ID); ?>" class="btn btn-secondary">
                Vedi Profilo Pubblico
            </a>
        </div>

        <!-- Tab Navigation -->
        <div class="dashboard-tabs">
            <button class="tab-button active" data-tab="my-travels">
                I Miei Viaggi (<?php echo $my_travels->post_count; ?>)
            </button>
            <button class="tab-button" data-tab="requests">
                Richieste di Partecipazione
                <?php
                $total_requests = count($pending_requests) + count($my_pending_requests);
                if ($total_requests > 0) : ?>
                    <span class="badge-count"><?php echo $total_requests; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button" data-tab="participating">
                Viaggi a cui Partecipo
                <?php if ($participated_travels) : ?>
                    (<?php echo $participated_travels->post_count; ?>)
                <?php endif; ?>
            </button>
            <button class="tab-button" data-tab="my-stories">
                I Miei Racconti (<?php echo $my_stories->post_count; ?>)
            </button>
            <button class="tab-button" data-tab="messages">
                Messaggi
                <?php if ($unread_messages_count > 0) : ?>
                    <span class="badge-count"><?php echo $unread_messages_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button" data-tab="reviews">
                Recensioni
                <?php if ($pending_reviews_count > 0) : ?>
                    <span class="badge-count"><?php echo $pending_reviews_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button" data-tab="notifications">
                🔔 Notifiche
                <?php
                if (class_exists('CDV_Notifications')) {
                    $notifications_count = CDV_Notifications::get_unread_count(get_current_user_id());
                    if ($notifications_count > 0) : ?>
                        <span class="badge-count"><?php echo $notifications_count; ?></span>
                    <?php endif;
                }
                ?>
            </button>
            <button class="tab-button" data-tab="wishlist">
                💝 Wishlist
                <?php
                $wishlist_count = CDV_Wishlist::get_wishlist_count(get_current_user_id());
                if ($wishlist_count > 0) : ?>
                    <span class="badge-count"><?php echo $wishlist_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button" data-tab="referral">
                🎁 Invita Amici
            </button>
            <?php if ($user_travels_count > 0) : ?>
                <button class="tab-button" data-tab="statistics">
                    📊 Statistiche
                </button>
            <?php endif; ?>
            <button class="tab-button" data-tab="settings">Impostazioni</button>
        </div>

        <!-- Tab: I Miei Viaggi -->
        <div class="tab-content active" id="tab-my-travels">
            <div class="section-header">
                <h2>I Miei Viaggi</h2>
                <a href="<?php echo esc_url(home_url('/crea-viaggio')); ?>" class="btn btn-primary" id="btn-new-travel">
                    <i class="icon-plus"></i> Nuovo Viaggio
                </a>
            </div>

            <?php if ($my_travels->have_posts()) : ?>
                <div class="travels-list">
                    <?php while ($my_travels->have_posts()) : $my_travels->the_post(); ?>
                        <?php
                        $travel_id = get_the_ID();
                        $participants = CDV_Participants::get_participants($travel_id, 'accepted');
                        $pending = CDV_Participants::get_participants($travel_id, 'pending');
                        $max_participants = get_post_meta($travel_id, 'cdv_max_participants', true);
                        $travel_status = get_post_meta($travel_id, 'cdv_travel_status', true);
                        $post_status = get_post_status();
                        ?>
                        <div class="travel-item" data-travel-id="<?php echo $travel_id; ?>">
                            <div class="travel-item-header">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="travel-thumb">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="travel-item-info">
                                    <h3>
                                        <a href="<?php the_permalink(); ?>" target="_blank">
                                            <?php the_title(); ?>
                                        </a>
                                    </h3>
                                    <p class="travel-meta">
                                        <span class="status-badge status-<?php echo $post_status; ?>">
                                            <?php
                                            echo $post_status === 'publish' ? 'Pubblicato' :
                                                ($post_status === 'pending' ? 'In Attesa di Approvazione' : 'Bozza');
                                            ?>
                                        </span>
                                        <?php if ($post_status === 'publish') : ?>
                                            <span class="travel-status-badge travel-<?php echo $travel_status; ?>">
                                                <?php echo ucfirst($travel_status); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span><i class="icon-users"></i> <?php echo count($participants); ?>/<?php echo $max_participants; ?> partecipanti</span>
                                        <?php if (count($pending) > 0) : ?>
                                            <span class="pending-requests">
                                                <i class="icon-alert"></i> <?php echo count($pending); ?> richieste
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="travel-item-actions">
                                <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="icon-eye"></i> Visualizza
                                </a>

                                <?php if ($post_status === 'publish') : ?>
                                    <select class="travel-status-select" data-travel-id="<?php echo $travel_id; ?>">
                                        <option value="open" <?php selected($travel_status, 'open'); ?>>Aperto</option>
                                        <option value="full" <?php selected($travel_status, 'full'); ?>>Completo</option>
                                        <option value="closed" <?php selected($travel_status, 'closed'); ?>>Chiuso</option>
                                        <option value="completed" <?php selected($travel_status, 'completed'); ?>>Completato</option>
                                    </select>
                                <?php endif; ?>

                                <button class="btn btn-sm btn-danger delete-travel" data-travel-id="<?php echo $travel_id; ?>">
                                    <i class="icon-trash"></i> Elimina
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="no-content">Non hai ancora creato nessun viaggio. <a href="<?php echo esc_url(home_url('/crea-viaggio')); ?>" id="link-new-travel">Crea il tuo primo viaggio!</a></p>
            <?php endif; ?>
        </div>

        <!-- Tab: Richieste di Partecipazione -->
        <div class="tab-content" id="tab-requests">
            <h2>Richieste di Partecipazione</h2>

            <!-- Richieste Inviate (viaggiatore) -->
            <?php if (!empty($my_pending_requests)) : ?>
                <div class="requests-section">
                    <h3>Le Mie Richieste Inviate</h3>
                    <div class="requests-list">
                        <?php foreach ($my_pending_requests as $request) : ?>
                            <div class="request-item sent-request" data-request-id="<?php echo $request->id; ?>">
                                <div class="request-user">
                                    <?php echo get_avatar($request->post_author, 60); ?>
                                    <div class="request-user-info">
                                        <h4>
                                            <span class="request-label">Organizzatore:</span>
                                            <a href="<?php echo CDV_User_Profiles::get_profile_url($request->post_author); ?>" target="_blank">
                                                <?php echo esc_html($request->organizer_name); ?>
                                            </a>
                                        </h4>
                                        <p class="request-travel">Viaggio: <strong><?php echo esc_html($request->post_title); ?></strong></p>
                                        <p class="request-date">
                                            <i class="icon-clock"></i>
                                            Inviata <?php echo human_time_diff(strtotime($request->requested_at), current_time('timestamp')); ?> fa
                                        </p>
                                        <?php if (!empty($request->message)) : ?>
                                            <p class="request-message">"<?php echo esc_html($request->message); ?>"</p>
                                        <?php endif; ?>
                                        <p class="request-status">
                                            <i class="icon-info"></i> In attesa di approvazione
                                        </p>
                                    </div>
                                </div>

                                <div class="request-actions">
                                    <a href="<?php echo home_url('/dashboard?tab=messages&user_id=' . $request->post_author . '&travel_id=' . $request->travel_id); ?>" class="btn btn-secondary">
                                        <i class="icon-message"></i> Messaggia
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Richieste Ricevute (organizzatore) -->
            <?php if (!empty($pending_requests)) : ?>
                <div class="requests-section">
                    <h3>Richieste Ricevute</h3>
                    <div class="requests-list">
                        <?php foreach ($pending_requests as $request) : ?>
                            <div class="request-item" data-request-id="<?php echo $request->id; ?>">
                                <div class="request-user">
                                    <?php echo get_avatar($request->user_id, 60); ?>
                                    <div class="request-user-info">
                                        <h4>
                                            <a href="<?php echo CDV_User_Profiles::get_profile_url($request->user_id); ?>" target="_blank">
                                                <?php echo esc_html($request->user_login); ?>
                                            </a>
                                        </h4>
                                        <p class="request-travel">Viaggio: <strong><?php echo esc_html($request->post_title); ?></strong></p>
                                        <p class="request-date">
                                            <i class="icon-clock"></i>
                                            <?php echo human_time_diff(strtotime($request->requested_at), current_time('timestamp')); ?> fa
                                        </p>
                                        <?php if (!empty($request->message)) : ?>
                                            <p class="request-message">"<?php echo esc_html($request->message); ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="request-actions">
                                    <a href="<?php echo home_url('/dashboard?tab=messages&user_id=' . $request->user_id . '&travel_id=' . $request->travel_id); ?>" class="btn btn-sm btn-secondary">
                                        <i class="icon-message"></i> Rispondi
                                    </a>
                                    <button class="btn btn-success approve-request" data-request-id="<?php echo $request->id; ?>" data-travel-id="<?php echo $request->travel_id; ?>" data-user-id="<?php echo $request->user_id; ?>">
                                        <i class="icon-check"></i> Approva
                                    </button>
                                    <button class="btn btn-danger reject-request" data-request-id="<?php echo $request->id; ?>" data-travel-id="<?php echo $request->travel_id; ?>" data-user-id="<?php echo $request->user_id; ?>">
                                        <i class="icon-x"></i> Rifiuta
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Messaggio se non ci sono richieste -->
            <?php if (empty($pending_requests) && empty($my_pending_requests)) : ?>
                <p class="no-content">Nessuna richiesta di partecipazione in attesa.</p>
            <?php endif; ?>
        </div>

        <!-- Tab: Viaggi a cui Partecipo -->
        <div class="tab-content" id="tab-participating">
            <h2>Viaggi a cui Partecipo</h2>

            <?php if ($participated_travels && $participated_travels->have_posts()) : ?>
                <div class="travels-grid">
                    <?php while ($participated_travels->have_posts()) : $participated_travels->the_post(); ?>
                        <?php get_template_part('template-parts/content', 'travel-card'); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="no-content">Non stai partecipando a nessun viaggio. <a href="<?php echo get_post_type_archive_link('viaggio'); ?>">Cerca un viaggio!</a></p>
            <?php endif; ?>
        </div>

        <!-- Tab: I Miei Racconti -->
        <div class="tab-content" id="tab-my-stories">
            <div class="section-header">
                <h2>I Miei Racconti</h2>
                <a href="<?php echo esc_url(home_url('/racconta-viaggio')); ?>" class="btn btn-primary">
                    <i class="icon-plus"></i> Nuovo Racconto
                </a>
            </div>

            <?php if ($my_stories->have_posts()) : ?>
                <div class="stories-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                    <?php while ($my_stories->have_posts()) : $my_stories->the_post(); ?>
                        <?php
                        $story_stats = CDV_Travel_Stories::get_story_stats(get_the_ID());
                        ?>
                        <div class="story-item" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="story-image" style="aspect-ratio: 4/3; overflow: hidden;">
                                    <?php the_post_thumbnail('medium', array('style' => 'width: 100%; height: 100%; object-fit: cover;')); ?>
                                </div>
                            <?php endif; ?>

                            <div class="story-item-content" style="padding: 1.5rem;">
                                <h3 style="margin: 0 0 1rem 0;">
                                    <a href="<?php the_permalink(); ?>" target="_blank" style="color: #333; text-decoration: none;">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>

                                <div class="story-meta" style="display: flex; gap: 1rem; margin-bottom: 1rem; font-size: 0.875rem; color: #666;">
                                    <span>👁 <?php echo number_format_i18n($story_stats['views']); ?> visualizzazioni</span>
                                    <span>💬 <?php echo number_format_i18n($story_stats['comments']); ?> commenti</span>
                                </div>

                                <div class="story-excerpt" style="color: #666; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem;">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                </div>

                                <div class="story-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="<?php echo esc_url(add_query_arg('story_id', get_the_ID(), home_url('/racconta-viaggio'))); ?>" class="btn btn-secondary btn-sm">
                                        Modifica
                                    </a>
                                    <a href="<?php the_permalink(); ?>" class="btn btn-secondary btn-sm" target="_blank">
                                        Visualizza
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <div class="no-content">
                    <p>Non hai ancora pubblicato nessun racconto.</p>
                    <a href="<?php echo esc_url(home_url('/racconta-viaggio')); ?>" class="btn btn-primary" style="margin-top: 1rem;">
                        Racconta il Tuo Primo Viaggio
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Messaggi -->
        <div class="tab-content" id="tab-messages">
            <h2>Messaggi</h2>

            <div class="messages-container">
                <div class="conversations-list">
                    <h3>Conversazioni</h3>
                    <div id="conversations-list-content">
                        <p class="loading-message">Caricamento conversazioni...</p>
                    </div>
                </div>

                <div class="message-thread">
                    <div class="no-conversation-selected">
                        <p>Seleziona una conversazione per visualizzare i messaggi</p>
                    </div>

                    <div class="conversation-view" style="display: none;">
                        <div class="conversation-header">
                            <div class="conversation-info">
                                <h3 id="conversation-user-name"></h3>
                                <p id="conversation-travel-title"></p>
                            </div>
                            <div class="conversation-actions">
                                <button class="btn btn-sm btn-danger" id="block-conversation-btn">
                                    Blocca Conversazione
                                </button>
                            </div>
                        </div>

                        <div class="messages-list" id="messages-list">
                            <!-- Messages will be loaded here -->
                        </div>

                        <div class="message-compose">
                            <textarea id="message-input" placeholder="Scrivi un messaggio..." rows="3"></textarea>
                            <button class="btn btn-primary" id="send-message-btn">Invia</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Recensioni -->
        <div class="tab-content" id="tab-reviews">
            <h2>Recensioni</h2>

            <!-- Pending Reviews Section -->
            <?php if (!empty($pending_reviews)) : ?>
                <div class="reviews-section">
                    <div class="section-header">
                        <h3>Recensioni da Lasciare (<?php echo $pending_reviews_count; ?>)</h3>
                        <p style="color: #6c757d; margin: 10px 0;">Lascia una recensione per i compagni di viaggio dei tuoi viaggi completati.</p>
                    </div>

                    <div class="pending-reviews-list">
                        <?php foreach ($pending_reviews as $pending_review) :
                            $travel = get_post($pending_review['travel_id']);
                            $reviewed_user = get_userdata($pending_review['user_id']);
                            if (!$travel || !$reviewed_user) continue;
                        ?>
                            <div class="pending-review-item">
                                <div class="review-item-header">
                                    <div class="user-avatar">
                                        <?php echo get_avatar($reviewed_user->ID, 50); ?>
                                    </div>
                                    <div class="review-item-info">
                                        <h4><?php echo esc_html($reviewed_user->display_name); ?></h4>
                                        <p class="travel-title">
                                            <i class="icon-map"></i>
                                            <a href="<?php echo get_permalink($travel->ID); ?>" target="_blank">
                                                <?php echo esc_html($travel->post_title); ?>
                                            </a>
                                        </p>
                                        <p class="travel-dates">
                                            <?php
                                            $start_date = get_post_meta($travel->ID, 'cdv_start_date', true);
                                            $end_date = get_post_meta($travel->ID, 'cdv_end_date', true);
                                            if ($start_date && $end_date) {
                                                echo date_i18n('d/m/Y', strtotime($start_date)) . ' - ' . date_i18n('d/m/Y', strtotime($end_date));
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="review-item-actions">
                                    <button class="btn btn-primary btn-write-review"
                                            data-travel-id="<?php echo $pending_review['travel_id']; ?>"
                                            data-user-id="<?php echo $pending_review['user_id']; ?>"
                                            data-user-name="<?php echo esc_attr($reviewed_user->display_name); ?>"
                                            data-travel-title="<?php echo esc_attr($travel->post_title); ?>">
                                        Scrivi Recensione
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <p>✅ Non hai recensioni in sospeso!</p>
                    <p style="color: #6c757d;">Le recensioni da lasciare appariranno qui dopo aver completato un viaggio.</p>
                </div>
            <?php endif; ?>

            <!-- Received Reviews Section -->
            <div class="reviews-section" style="margin-top: 40px;">
                <div class="section-header">
                    <h3>Recensioni Ricevute (<?php echo count($received_reviews); ?>)</h3>
                </div>

                <?php if (!empty($received_reviews)) : ?>
                    <div class="received-reviews-list">
                        <?php foreach ($received_reviews as $review) :
                            $reviewer = get_userdata($review->reviewer_id);
                            $travel = get_post($review->travel_id);
                            if (!$reviewer || !$travel) continue;

                            $avg_score = round(($review->punctuality + $review->group_spirit + $review->respect + $review->adaptability) / 4, 1);
                        ?>
                            <div class="received-review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <?php echo get_avatar($reviewer->ID, 40); ?>
                                        <div>
                                            <strong><?php echo esc_html($reviewer->display_name); ?></strong>
                                            <p class="review-date"><?php echo date_i18n('d/m/Y', strtotime($review->created_at)); ?></p>
                                        </div>
                                    </div>
                                    <div class="review-score">
                                        <div class="score-number"><?php echo $avg_score; ?>/5</div>
                                        <div class="score-stars">
                                            <?php
                                            $full_stars = floor($avg_score);
                                            $half_star = ($avg_score - $full_stars) >= 0.5;
                                            for ($i = 0; $i < 5; $i++) {
                                                if ($i < $full_stars) {
                                                    echo '<span class="star filled">★</span>';
                                                } elseif ($i == $full_stars && $half_star) {
                                                    echo '<span class="star half">★</span>';
                                                } else {
                                                    echo '<span class="star">☆</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="review-travel-info">
                                    <i class="icon-map"></i>
                                    <a href="<?php echo get_permalink($travel->ID); ?>">
                                        <?php echo esc_html($travel->post_title); ?>
                                    </a>
                                </div>

                                <div class="review-scores-detail">
                                    <div class="score-item">
                                        <span class="score-label">Puntualità:</span>
                                        <span class="score-value"><?php echo $review->punctuality; ?>/5</span>
                                    </div>
                                    <div class="score-item">
                                        <span class="score-label">Spirito di Gruppo:</span>
                                        <span class="score-value"><?php echo $review->group_spirit; ?>/5</span>
                                    </div>
                                    <div class="score-item">
                                        <span class="score-label">Rispetto:</span>
                                        <span class="score-value"><?php echo $review->respect; ?>/5</span>
                                    </div>
                                    <div class="score-item">
                                        <span class="score-label">Adattabilità:</span>
                                        <span class="score-value"><?php echo $review->adaptability; ?>/5</span>
                                    </div>
                                </div>

                                <?php if (!empty($review->comment)) : ?>
                                    <div class="review-comment">
                                        <p><?php echo esc_html($review->comment); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty-state">
                        <p>Non hai ancora ricevuto recensioni</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Notifiche -->
        <div class="tab-content" id="tab-notifications">
            <div class="section-header">
                <h2>🔔 Notifiche</h2>
                <button class="btn btn-sm btn-secondary" id="mark-all-read-btn">
                    Segna Tutte Come Lette
                </button>
            </div>

            <div id="notifications-container">
                <div class="loading-spinner" style="text-align: center; padding: 40px;">
                    <p>Caricamento notifiche...</p>
                </div>
            </div>
        </div>

        <!-- Tab: Wishlist -->
        <div class="tab-content" id="tab-wishlist">
            <div class="section-header">
                <h2>💝 La Mia Wishlist</h2>
                <p>I viaggi che hai salvato per dopo</p>
            </div>

            <?php
            $wishlist_travels = CDV_Wishlist::get_wishlist_travels($current_user->ID);

            if ($wishlist_travels && $wishlist_travels->have_posts()) : ?>
                <div class="wishlist-grid">
                    <?php while ($wishlist_travels->have_posts()) : $wishlist_travels->the_post();
                        $travel_id = get_the_ID();
                        $author_id = get_the_author_meta('ID');
                        $destination = get_post_meta($travel_id, 'cdv_destination', true);
                        $country = get_post_meta($travel_id, 'cdv_country', true);
                        $start_date = get_post_meta($travel_id, 'cdv_start_date', true);
                        $budget = get_post_meta($travel_id, 'cdv_budget', true);
                        $max_participants = get_post_meta($travel_id, 'cdv_max_participants', true);
                        $participants_count = CDV_Participants::get_participant_count($travel_id, 'accepted');
                    ?>
                        <div class="wishlist-card">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="wishlist-card-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                    <button class="wishlist-remove-btn" data-travel-id="<?php echo $travel_id; ?>" title="Rimuovi dalla wishlist">
                                        ❤️
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="wishlist-card-content">
                                <div class="wishlist-card-badges">
                                    <?php cdv_travel_type_badges(); ?>
                                    <?php echo cdv_get_travel_status_label(); ?>
                                </div>

                                <h3 class="wishlist-card-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <div class="wishlist-card-meta">
                                    <?php if ($destination) : ?>
                                        <span class="meta-item">
                                            <strong>📍</strong> <?php echo esc_html($destination); ?><?php echo $country ? ', ' . esc_html($country) : ''; ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($start_date) : ?>
                                        <span class="meta-item">
                                            <strong>📅</strong> <?php echo date_i18n('d M Y', strtotime($start_date)); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($budget) : ?>
                                        <span class="meta-item">
                                            <strong>💰</strong> €<?php echo number_format($budget, 0, ',', '.'); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($max_participants) : ?>
                                        <span class="meta-item">
                                            <strong>👥</strong> <?php echo $participants_count; ?>/<?php echo $max_participants; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="wishlist-card-footer">
                                    <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-sm">
                                        Vedi Dettagli
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <span class="empty-icon">💝</span>
                    <h3>La tua wishlist è vuota</h3>
                    <p>Non hai ancora salvato nessun viaggio nella tua wishlist.</p>
                    <p>Esplora i viaggi disponibili e salva quelli che ti interessano per trovarli facilmente!</p>
                    <a href="<?php echo get_post_type_archive_link('viaggio'); ?>" class="btn btn-primary">
                        Esplora Viaggi
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Invita Amici (Referral) -->
        <div class="tab-content" id="tab-referral">
            <div class="section-header">
                <h2>🎁 Invita Amici e Guadagna Punti</h2>
                <p>Condividi il tuo codice referral con gli amici e guadagna punti quando si iscrivono!</p>
            </div>

            <div class="referral-stats-container">
                <div class="stats-loading">
                    <p>Caricamento statistiche referral...</p>
                </div>
            </div>

            <div class="referral-code-section">
                <h3>📋 Il Tuo Codice Referral</h3>
                <div class="referral-code-box">
                    <div class="code-display">
                        <span id="referral-code" class="referral-code">Caricamento...</span>
                        <button id="copy-referral-code-btn" class="btn btn-sm btn-secondary" title="Copia codice">
                            📋 Copia
                        </button>
                    </div>
                </div>

                <div class="referral-link-box">
                    <label>🔗 Link di Invito:</label>
                    <div class="link-display">
                        <input type="text" id="referral-link" readonly value="Caricamento...">
                        <button id="copy-referral-link-btn" class="btn btn-sm btn-primary" title="Copia link">
                            📋 Copia Link
                        </button>
                        <button id="share-referral-btn" class="btn btn-sm btn-success" title="Condividi">
                            💬 Condividi
                        </button>
                    </div>
                </div>

                <div class="referral-share-buttons">
                    <p><strong>Condividi su:</strong></p>
                    <div class="social-share-ref"></div>
                </div>
            </div>

            <div class="referral-rewards-section">
                <h3>🏆 Come Funziona</h3>
                <div class="rewards-info">
                    <div class="reward-item">
                        <span class="reward-icon">🎯</span>
                        <div class="reward-details">
                            <h4>Invita un Amico</h4>
                            <p>Condividi il tuo codice referral con amici e conoscenti</p>
                        </div>
                    </div>
                    <div class="reward-item">
                        <span class="reward-icon">✅</span>
                        <div class="reward-details">
                            <h4>L'Amico si Registra</h4>
                            <p>Quando si iscrive usando il tuo codice, ottieni 20 punti</p>
                        </div>
                    </div>
                    <div class="reward-item">
                        <span class="reward-icon">🎁</span>
                        <div class="reward-details">
                            <h4>L'Amico Partecipa a un Viaggio</h4>
                            <p>Quando completa la sua prima partecipazione, guadagni altri 30 punti!</p>
                        </div>
                    </div>
                    <div class="reward-item">
                        <span class="reward-icon">⭐</span>
                        <div class="reward-details">
                            <h4>Migliora la Tua Reputazione</h4>
                            <p>I punti referral aumentano la tua reputazione e visibilità</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="referral-history-section">
                <h3>👥 I Tuoi Referral</h3>
                <div id="referral-history-container">
                    <p class="loading-text">Caricamento...</p>
                </div>
            </div>
        </div>

        <!-- Tab: Statistiche (Statistics for Organizers) -->
        <?php if ($user_travels_count > 0) : ?>
        <div class="tab-content" id="tab-statistics">
            <div class="section-header">
                <h2>📊 Le Tue Statistiche</h2>
                <p>Analisi dettagliata delle performance dei tuoi viaggi</p>
            </div>

            <div class="stats-loading-container">
                <p>Caricamento statistiche...</p>
            </div>

            <!-- Overview Stats (will be filled by JavaScript) -->
            <div id="overview-stats-container"></div>

            <!-- Monthly Trends Chart -->
            <div id="monthly-trends-container" style="margin: 30px 0;"></div>

            <!-- Travel Performance -->
            <div id="travel-performance-container" style="margin: 30px 0;"></div>

            <!-- Popular Destinations -->
            <div id="popular-destinations-container" style="margin: 30px 0;"></div>

            <!-- Participant Demographics -->
            <div id="demographics-container" style="margin: 30px 0;"></div>
        </div>
        <?php endif; ?>

        <!-- Tab: Impostazioni -->
        <div class="tab-content" id="tab-settings">
            <h2>Impostazioni Profilo</h2>

            <!-- Profile Image Upload -->
            <div class="settings-section">
                <h3>Foto Profilo</h3>
                <div class="profile-image-upload">
                    <div class="current-avatar">
                        <?php echo get_avatar($current_user->ID, 120); ?>
                    </div>
                    <div class="upload-controls">
                        <input type="file" id="dashboard_profile_image" name="profile_image" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" id="dashboard-upload-btn">Cambia Foto</button>
                            <?php
                            // Show remove button only if user has custom avatar
                            $custom_avatar = get_user_meta($current_user->ID, 'cdv_profile_image', true);
                            if (!empty($custom_avatar)) :
                            ?>
                                <button type="button" class="btn btn-danger" id="dashboard-remove-photo-btn">Rimuovi Foto</button>
                            <?php endif; ?>
                        </div>
                        <small>JPG o PNG, max 5MB</small>
                        <div id="upload-status" style="margin-top: 10px;"></div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="settings-section">
                <h3>Informazioni Personali</h3>
                <form id="edit-profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_display_name">Nome e Cognome</label>
                            <input type="text" id="edit_display_name" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_city">Città</label>
                            <input type="text" id="edit_city" name="city" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'cdv_city', true)); ?>">
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Telefono</label>
                            <input type="tel" id="edit_phone" name="phone" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'cdv_phone', true)); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_bio">Bio</label>
                        <textarea id="edit_bio" name="bio" rows="4"><?php echo esc_textarea(get_user_meta($current_user->ID, 'cdv_bio', true)); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="settings-section">
                <h3>Cambia Password</h3>
                <form id="change-password-form">
                    <div class="form-group">
                        <label for="current_password">Password Attuale</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nuova Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Conferma Nuova Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Cambia Password</button>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="settings-section danger-zone">
                <h3>Zona Pericolosa</h3>
                <p><strong>Elimina Account</strong> - Questa azione è irreversibile. Tutti i tuoi dati, viaggi e messaggi saranno eliminati permanentemente.</p>
                <button type="button" class="btn btn-danger" id="delete-account-btn">Elimina Account</button>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="review-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Scrivi una Recensione</h2>
            <p id="review-modal-subtitle" style="color: #6c757d; margin-bottom: 20px;"></p>

            <form id="review-form">
                <input type="hidden" id="review-travel-id" name="travel_id">
                <input type="hidden" id="review-user-id" name="reviewed_id">

                <div class="disclaimer-box" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 25px; border-radius: 4px;">
                    <p style="margin: 0; font-size: 14px;"><strong>⚠️ Importante:</strong> Le recensioni devono essere oneste, rispettose e basate sulla tua esperienza reale. Recensioni offensive o false possono portare alla rimozione del tuo account.</p>
                </div>

                <div class="form-group">
                    <label>Valuta il Compagno di Viaggio</label>
                    <p style="font-size: 14px; color: #6c757d; margin-bottom: 15px;">Assegna un punteggio da 1 a 5 per ciascuna categoria</p>

                    <div class="rating-group">
                        <label for="punctuality">Puntualità</label>
                        <div class="star-rating" data-field="punctuality">
                            <input type="hidden" id="punctuality" name="punctuality" value="0" required>
                            <span class="rating-star" data-value="1">★</span>
                            <span class="rating-star" data-value="2">★</span>
                            <span class="rating-star" data-value="3">★</span>
                            <span class="rating-star" data-value="4">★</span>
                            <span class="rating-star" data-value="5">★</span>
                        </div>
                    </div>

                    <div class="rating-group">
                        <label for="group_spirit">Spirito di Gruppo</label>
                        <div class="star-rating" data-field="group_spirit">
                            <input type="hidden" id="group_spirit" name="group_spirit" value="0" required>
                            <span class="rating-star" data-value="1">★</span>
                            <span class="rating-star" data-value="2">★</span>
                            <span class="rating-star" data-value="3">★</span>
                            <span class="rating-star" data-value="4">★</span>
                            <span class="rating-star" data-value="5">★</span>
                        </div>
                    </div>

                    <div class="rating-group">
                        <label for="respect">Rispetto</label>
                        <div class="star-rating" data-field="respect">
                            <input type="hidden" id="respect" name="respect" value="0" required>
                            <span class="rating-star" data-value="1">★</span>
                            <span class="rating-star" data-value="2">★</span>
                            <span class="rating-star" data-value="3">★</span>
                            <span class="rating-star" data-value="4">★</span>
                            <span class="rating-star" data-value="5">★</span>
                        </div>
                    </div>

                    <div class="rating-group">
                        <label for="adaptability">Adattabilità</label>
                        <div class="star-rating" data-field="adaptability">
                            <input type="hidden" id="adaptability" name="adaptability" value="0" required>
                            <span class="rating-star" data-value="1">★</span>
                            <span class="rating-star" data-value="2">★</span>
                            <span class="rating-star" data-value="3">★</span>
                            <span class="rating-star" data-value="4">★</span>
                            <span class="rating-star" data-value="5">★</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="review-comment">Commento (opzionale)</label>
                    <textarea id="review-comment" name="comment" rows="4" placeholder="Condividi la tua esperienza con questo compagno di viaggio..."></textarea>
                </div>

                <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary modal-close">Annulla</button>
                    <button type="submit" class="btn btn-primary">Invia Recensione</button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.dashboard {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.dashboard-header {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-header h1 {
    margin: 0 0 0.5rem 0;
}

.dashboard-header p {
    margin: 0;
    color: #666;
}

.dashboard-tabs {
    display: flex;
    gap: 1rem;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 2rem;
    background: white;
    padding: 0 2rem;
    border-radius: 12px 12px 0 0;
}

.tab-button {
    padding: 1rem 1rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 0.85rem;
    color: #666;
    transition: all 0.3s;
    position: relative;
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.badge-count {
    background: #dc3545;
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-header h2 {
    margin: 0;
}

.travels-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.travel-item {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.travel-item-header {
    display: flex;
    gap: 1rem;
    flex: 1;
    align-items: center;
}

.travel-thumb img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.travel-item-info {
    flex: 1;
}

.travel-item-info h3 {
    margin: 0 0 0.5rem 0;
}

.travel-item-info h3 a {
    color: #333;
    text-decoration: none;
}

.travel-item-info h3 a:hover {
    color: var(--primary-color);
}

.travel-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
    margin: 0;
    font-size: 0.875rem;
    color: #666;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
}

.status-publish {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-draft {
    background: #e2e3e5;
    color: #383d41;
}

.travel-status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
}

.travel-open {
    background: #d4edda;
    color: #155724;
}

.travel-full {
    background: #fff3cd;
    color: #856404;
}

.travel-closed, .travel-completed {
    background: #e2e3e5;
    color: #383d41;
}

.pending-requests {
    color: #dc3545;
    font-weight: bold;
}

.travel-item-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.travel-status-select {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
}

.requests-section {
    margin-bottom: 2rem;
}

.requests-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e0e0e0;
    color: #333;
}

.requests-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.request-item {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.request-item.sent-request {
    border-left: 4px solid var(--primary-color);
}

.request-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #999;
    font-weight: normal;
    display: block;
    margin-bottom: 0.25rem;
}

.request-status {
    color: var(--primary-color);
    font-weight: 500;
    font-size: 0.875rem;
}

.request-user {
    display: flex;
    gap: 1rem;
    flex: 1;
}

.request-user img {
    border-radius: 50%;
}

.request-user-info h4 {
    margin: 0 0 0.25rem 0;
}

.request-user-info h4 a {
    color: #333;
    text-decoration: none;
}

.request-user-info h4 a:hover {
    color: var(--primary-color);
}

.request-user-info p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #666;
}

.request-message {
    font-style: italic;
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: 4px;
    margin-top: 0.5rem !important;
}

.request-actions {
    display: flex;
    gap: 0.5rem;
}

.travels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.settings-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.no-content {
    background: white;
    padding: 3rem;
    border-radius: 12px;
    text-align: center;
    color: #999;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .travel-item, .request-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .travel-item-actions, .request-actions {
        width: 100%;
        justify-content: flex-end;
    }
}

/* Settings Forms */
.settings-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.settings-section h3 {
    margin-bottom: 1.5rem;
    color: #2c3e50;
}

.settings-section .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.settings-section .form-group {
    margin-bottom: 1.5rem;
}

.settings-section label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.settings-section input[type="text"],
.settings-section input[type="email"],
.settings-section input[type="tel"],
.settings-section input[type="password"],
.settings-section textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.settings-section textarea {
    resize: vertical;
    min-height: 100px;
}

.danger-zone {
    border: 2px solid #dc3545;
}

.danger-zone h3 {
    color: #dc3545;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
}

.btn-danger:hover {
    background: #c82333;
}

.profile-image-upload {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.current-avatar img {
    border-radius: 50%;
    border: 3px solid var(--border-color);
}

.upload-controls {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.upload-controls small {
    color: var(--text-medium);
}

@media (max-width: 768px) {
    .profile-image-upload {
        flex-direction: column;
        text-align: center;
    }

    .settings-section .form-row {
        grid-template-columns: 1fr;
    }
}

/* Messages Styles */
.messages-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
    background: white;
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: 600px;
    overflow: hidden;
}

.conversations-list {
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.conversations-list h3 {
    padding: 1.5rem;
    margin: 0;
    border-bottom: 1px solid #e0e0e0;
}

#conversations-list-content {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.conversation-item:hover {
    background: #f8f9fa;
}

.conversation-item.active {
    background: #e8f4f8;
    border-left: 3px solid var(--primary-color);
}

.conversation-item.unread {
    background: #f0f8ff;
}

.conversation-item img {
    border-radius: 50%;
    width: 50px;
    height: 50px;
}

.conversation-item-info {
    flex: 1;
    min-width: 0;
}

.conversation-item-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-item-info p {
    margin: 0;
    font-size: 0.875rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-item-meta {
    font-size: 0.75rem;
    color: #999;
}

.unread-badge {
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
}

.message-thread {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.no-conversation-selected {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
}

.conversation-view {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.conversation-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conversation-info h3 {
    margin: 0 0 0.25rem 0;
}

.conversation-info p {
    margin: 0;
    font-size: 0.875rem;
    color: #666;
}

.messages-list {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message-item {
    display: flex;
    gap: 0.75rem;
    max-width: 70%;
}

.message-item.sent {
    margin-left: auto;
    flex-direction: row-reverse;
}

.message-item img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.message-bubble {
    background: #f0f0f0;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    word-wrap: break-word;
}

.message-item.sent .message-bubble {
    background: var(--primary-color);
    color: white;
}

.message-text {
    margin: 0 0 0.25rem 0;
}

.message-time {
    font-size: 0.75rem;
    color: #999;
    margin: 0;
}

.message-item.sent .message-time {
    color: rgba(255,255,255,0.8);
}

.message-compose {
    padding: 1.5rem;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 1rem;
}

.message-compose textarea {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: none;
    font-family: inherit;
    font-size: 1rem;
}

.message-compose textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}

.loading-message {
    padding: 2rem;
    text-align: center;
    color: #999;
}

.blocked-conversation {
    padding: 2rem;
    text-align: center;
    background: #fff3cd;
    margin: 1rem;
    border-radius: 8px;
    color: #856404;
}

@media (max-width: 768px) {
    .messages-container {
        grid-template-columns: 1fr;
        height: auto;
    }

    .conversations-list {
        border-right: none;
        border-bottom: 1px solid #e0e0e0;
        height: 300px;
    }

    .message-thread {
        min-height: 400px;
    }

    .message-item {
        max-width: 85%;
    }
}

/* Reviews Section */
.reviews-section {
    margin-bottom: 30px;
}

.pending-reviews-list, .received-reviews-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.pending-review-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: box-shadow 0.3s;
}

.pending-review-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.review-item-header {
    display: flex;
    gap: 15px;
    flex: 1;
}

.user-avatar img {
    border-radius: 50%;
}

.review-item-info h4 {
    margin: 0 0 5px 0;
    color: #2d3748;
}

.review-item-info p {
    margin: 5px 0;
    font-size: 14px;
    color: #6c757d;
}

.review-item-info .travel-title a {
    color: #667eea;
    text-decoration: none;
}

.review-item-info .travel-title a:hover {
    text-decoration: underline;
}

.received-review-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.reviewer-info {
    display: flex;
    gap: 12px;
    align-items: center;
}

.reviewer-info img {
    border-radius: 50%;
}

.review-date {
    font-size: 13px;
    color: #6c757d;
    margin: 0;
}

.review-score {
    text-align: right;
}

.score-number {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}

.score-stars {
    color: #ffc107;
    font-size: 16px;
}

.score-stars .star.filled {
    color: #ffc107;
}

.score-stars .star {
    color: #ddd;
}

.review-travel-info {
    margin: 10px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 14px;
}

.review-travel-info a {
    color: #667eea;
    text-decoration: none;
}

.review-scores-detail {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin: 15px 0;
}

.score-item {
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.score-label {
    color: #6c757d;
}

.score-value {
    font-weight: bold;
    color: #667eea;
}

.review-comment {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-left: 3px solid #667eea;
    border-radius: 4px;
}

.review-comment p {
    margin: 0;
    font-style: italic;
    color: #4a5568;
}

/* Review Modal */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 30px;
    border: 1px solid #888;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    overflow-y: auto;
}

.modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.modal-close:hover,
.modal-close:focus {
    color: #000;
}

.rating-group {
    margin-bottom: 20px;
}

.rating-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #2d3748;
}

.star-rating {
    display: flex;
    gap: 5px;
    font-size: 32px;
}

.rating-star {
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
}

.rating-star:hover,
.rating-star.active {
    color: #ffc107;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state p:first-child {
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.empty-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: calc(var(--spacing-unit) * 3);
    opacity: 0.8;
}

/* Wishlist Tab Styles */
.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: calc(var(--spacing-unit) * 3);
    margin-top: calc(var(--spacing-unit) * 3);
}

.wishlist-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s;
}

.wishlist-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.wishlist-card-image {
    position: relative;
    width: 100%;
    aspect-ratio: 4/3;
    overflow: hidden;
}

.wishlist-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.wishlist-card:hover .wishlist-card-image img {
    transform: scale(1.05);
}

.wishlist-remove-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: all 0.3s;
    font-size: 1.3rem;
    z-index: 10;
}

.wishlist-remove-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}

.wishlist-card-content {
    padding: calc(var(--spacing-unit) * 2);
}

.wishlist-card-badges {
    display: flex;
    gap: calc(var(--spacing-unit) * 1);
    margin-bottom: calc(var(--spacing-unit) * 2);
    flex-wrap: wrap;
}

.wishlist-card-title {
    font-size: 1.1rem;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.wishlist-card-title a {
    color: var(--text-dark);
    text-decoration: none;
    transition: color 0.2s;
}

.wishlist-card-title a:hover {
    color: var(--primary-color);
}

.wishlist-card-meta {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 1);
    margin-bottom: calc(var(--spacing-unit) * 2);
    font-size: 0.9rem;
    color: var(--text-medium);
}

.wishlist-card-footer {
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .wishlist-grid {
        grid-template-columns: 1fr;
    }
}

/* Notifications Styles */
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 2);
    margin-top: calc(var(--spacing-unit) * 3);
}

.notification-item {
    display: flex;
    gap: calc(var(--spacing-unit) * 2);
    padding: calc(var(--spacing-unit) * 3);
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s;
    border-left: 4px solid transparent;
    text-decoration: none;
    color: inherit;
}

.notification-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateX(4px);
}

.notification-item.unread {
    background: #f0f7ff;
    border-left-color: var(--primary-color);
}

.notification-item.read {
    opacity: 0.7;
}

.notification-icon {
    font-size: 2rem;
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 50%;
}

.notification-item.unread .notification-icon {
    background: var(--primary-color);
    color: white;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: bold;
    font-size: 1.05rem;
    margin-bottom: calc(var(--spacing-unit) * 0.5);
    color: var(--text-dark);
}

.notification-message {
    color: var(--text-medium);
    margin-bottom: calc(var(--spacing-unit) * 1);
    line-height: 1.5;
}

.notification-time {
    font-size: 0.85rem;
    color: var(--text-light);
}

.loading-spinner {
    text-align: center;
    padding: 40px;
    color: var(--text-medium);
}

@media (max-width: 768px) {
    .notification-item {
        gap: calc(var(--spacing-unit) * 1.5);
        padding: calc(var(--spacing-unit) * 2);
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
    }
}

/* Referral System Styles */
.referral-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: calc(var(--spacing-unit) * 3);
    margin: calc(var(--spacing-unit) * 4) 0;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    text-align: center;
    color: white;
    box-shadow: var(--shadow-md);
}

.stat-card.stat-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.stat-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.stat-primary {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: calc(var(--spacing-unit) * 1);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: calc(var(--spacing-unit) * 0.5);
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.referral-code-section {
    background: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    margin: calc(var(--spacing-unit) * 3) 0;
}

.referral-code-box {
    margin: calc(var(--spacing-unit) * 2) 0;
}

.code-display {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 2);
    padding: calc(var(--spacing-unit) * 2);
    background: #f5f5f5;
    border-radius: var(--border-radius);
}

.referral-code {
    font-size: 1.5rem;
    font-weight: 700;
    font-family: monospace;
    letter-spacing: 2px;
    color: var(--primary-color);
}

.referral-link-box {
    margin: calc(var(--spacing-unit) * 3) 0;
}

.link-display {
    display: flex;
    gap: calc(var(--spacing-unit) * 1);
    margin-top: calc(var(--spacing-unit) * 1);
}

.link-display input {
    flex: 1;
    padding: calc(var(--spacing-unit) * 1.5);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-family: monospace;
    font-size: 0.9rem;
}

.referral-share-buttons {
    margin-top: calc(var(--spacing-unit) * 3);
}

.social-share-ref {
    display: flex;
    flex-wrap: wrap;
    gap: calc(var(--spacing-unit) * 1.5);
    margin-top: calc(var(--spacing-unit) * 1.5);
}

.referral-rewards-section {
    background: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    margin: calc(var(--spacing-unit) * 3) 0;
}

.rewards-info {
    display: grid;
    gap: calc(var(--spacing-unit) * 2);
    margin-top: calc(var(--spacing-unit) * 2);
}

.reward-item {
    display: flex;
    align-items: flex-start;
    gap: calc(var(--spacing-unit) * 2);
    padding: calc(var(--spacing-unit) * 2);
    background: #f9f9f9;
    border-radius: var(--border-radius);
}

.reward-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.reward-details h4 {
    margin: 0 0 calc(var(--spacing-unit) * 0.5) 0;
    color: var(--text-dark);
}

.reward-details p {
    margin: 0;
    color: var(--text-medium);
    font-size: 0.9rem;
}

.referral-history-section {
    background: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    margin: calc(var(--spacing-unit) * 3) 0;
}

.referral-list {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 2);
    margin-top: calc(var(--spacing-unit) * 2);
}

.referral-item {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 2);
    padding: calc(var(--spacing-unit) * 2);
    background: #f9f9f9;
    border-radius: var(--border-radius);
}

.referral-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    flex-shrink: 0;
}

.referral-details {
    flex: 1;
}

.referral-name {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: calc(var(--spacing-unit) * 0.5);
}

.referral-date {
    font-size: 0.85rem;
    color: var(--text-medium);
}

.referral-status {
    padding: calc(var(--spacing-unit) * 1) calc(var(--spacing-unit) * 2);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.referral-status.status-success {
    background: #d4edda;
    color: #155724;
}

.referral-status.status-pending {
    background: #fff3cd;
    color: #856404;
}

.referral-points {
    padding: calc(var(--spacing-unit) * 1) calc(var(--spacing-unit) * 2);
    background: var(--primary-color);
    color: white;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .referral-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .link-display {
        flex-direction: column;
    }

    .referral-item {
        flex-wrap: wrap;
    }
}

/* Organizer Statistics Styles */
.stats-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: calc(var(--spacing-unit) * 3);
    margin: calc(var(--spacing-unit) * 4) 0;
}

.stat-overview-card {
    background: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    text-align: center;
    box-shadow: var(--shadow-md);
    border-left: 4px solid var(--primary-color);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: calc(var(--spacing-unit) * 1);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: calc(var(--spacing-unit) * 0.5);
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-medium);
    font-weight: 500;
}

.travel-perf-list {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 2);
    margin-top: calc(var(--spacing-unit) * 2);
}

.travel-perf-item {
    background: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.travel-perf-title {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: calc(var(--spacing-unit) * 1.5);
    font-size: 1.1rem;
}

.travel-perf-stats {
    display: flex;
    flex-wrap: wrap;
    gap: calc(var(--spacing-unit) * 2);
    font-size: 0.9rem;
    color: var(--text-medium);
}

.destinations-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: calc(var(--spacing-unit) * 2);
    margin-top: calc(var(--spacing-unit) * 2);
}

.destination-item {
    background: white;
    padding: calc(var(--spacing-unit) * 2.5);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.dest-name {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: calc(var(--spacing-unit) * 1);
    font-size: 1.05rem;
}

.dest-stats {
    display: flex;
    gap: calc(var(--spacing-unit) * 1);
    font-size: 0.85rem;
    color: var(--text-medium);
}

.demographics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: calc(var(--spacing-unit) * 2);
    margin-top: calc(var(--spacing-unit) * 2);
}

.demo-card {
    background: white;
    padding: calc(var(--spacing-unit) * 2);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.stats-loading-container {
    text-align: center;
    padding: calc(var(--spacing-unit) * 4);
    color: var(--text-medium);
}

@media (max-width: 768px) {
    .stats-overview-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-number {
        font-size: 1.5rem;
    }

    .travel-perf-stats {
        flex-direction: column;
        gap: calc(var(--spacing-unit) * 1);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            this.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        });
    });

    // Change travel status
    document.querySelectorAll('.travel-status-select').forEach(select => {
        select.addEventListener('change', function() {
            const travelId = this.dataset.travelId;
            const newStatus = this.value;

            if (!confirm('Vuoi davvero cambiare lo stato di questo viaggio?')) {
                return;
            }

            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_change_travel_status',
                    travel_id: travelId,
                    status: newStatus,
                    nonce: cdvAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Stato del viaggio aggiornato con successo!');
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data);
                    }
                }
            });
        });
    });

    // Delete travel
    document.querySelectorAll('.delete-travel').forEach(button => {
        button.addEventListener('click', function() {
            const travelId = this.dataset.travelId;

            if (!confirm('Sei sicuro di voler eliminare questo viaggio? Questa azione non può essere annullata.')) {
                return;
            }

            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_delete_travel',
                    travel_id: travelId,
                    nonce: cdvAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Viaggio eliminato con successo!');
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data);
                    }
                }
            });
        });
    });

    // Approve request
    document.querySelectorAll('.approve-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const travelId = this.dataset.travelId;
            const userId = this.dataset.userId;

            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_approve_participant',
                    travel_id: travelId,
                    user_id: userId,
                    nonce: cdvAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Richiesta approvata!');
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data);
                    }
                }
            });
        });
    });

    // Reject request
    document.querySelectorAll('.reject-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const travelId = this.dataset.travelId;
            const userId = this.dataset.userId;

            if (!confirm('Sei sicuro di voler rifiutare questa richiesta?')) {
                return;
            }

            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_reject_participant',
                    travel_id: travelId,
                    user_id: userId,
                    nonce: cdvAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Richiesta rifiutata.');
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data);
                    }
                }
            });
        });
    });

    // Dashboard Profile Image Upload
    document.getElementById('dashboard-upload-btn')?.addEventListener('click', function() {
        document.getElementById('dashboard_profile_image').click();
    });

    document.getElementById('dashboard_profile_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            document.getElementById('upload-status').innerHTML = '<div class="error-message">Formato non valido. Usa JPG o PNG.</div>';
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            document.getElementById('upload-status').innerHTML = '<div class="error-message">File troppo grande. Massimo 5MB.</div>';
            return;
        }

        // Upload file
        const formData = new FormData();
        formData.append('profile_image', file);
        formData.append('action', 'cdv_upload_profile_image');
        formData.append('nonce', cdvAjax.nonce);

        document.getElementById('upload-status').innerHTML = '<div style="color: #666;">Caricamento in corso...</div>';

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    document.getElementById('upload-status').innerHTML = '<div class="success-message">Foto caricata con successo!</div>';
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    document.getElementById('upload-status').innerHTML = '<div class="error-message">' + (response.data.message || 'Errore durante il caricamento') + '</div>';
                }
            },
            error: function() {
                document.getElementById('upload-status').innerHTML = '<div class="error-message">Errore di connessione</div>';
            }
        });
    });

    // Remove Profile Photo
    document.getElementById('dashboard-remove-photo-btn')?.addEventListener('click', function() {
        if (!confirm('Sei sicuro di voler rimuovere la tua foto profilo? Verrà ripristinato il Gravatar predefinito.')) {
            return;
        }

        const $btn = jQuery(this);
        const originalText = $btn.text();

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_remove_profile_image',
                nonce: cdvAjax.nonce
            },
            beforeSend: function() {
                $btn.prop('disabled', true).text('Rimozione...');
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('upload-status').innerHTML = '<div class="success-message">Foto rimossa con successo!</div>';
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    document.getElementById('upload-status').innerHTML = '<div class="error-message">' + (response.data.message || 'Errore durante la rimozione') + '</div>';
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                document.getElementById('upload-status').innerHTML = '<div class="error-message">Errore di connessione</div>';
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Edit Profile Form
    document.getElementById('edit-profile-form')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'cdv_update_profile');
        formData.append('nonce', cdvAjax.nonce);

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Profilo aggiornato con successo!');
                    location.reload();
                } else {
                    alert(response.data.message || 'Errore durante l\'aggiornamento');
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    });

    // Change Password Form
    document.getElementById('change-password-form')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
            alert('Le nuove password non coincidono');
            return;
        }

        const formData = new FormData(this);
        formData.append('action', 'cdv_change_password');
        formData.append('nonce', cdvAjax.nonce);

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Password cambiata con successo!');
                    document.getElementById('change-password-form').reset();
                } else {
                    alert(response.data.message || 'Errore durante il cambio password');
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    });

    // Delete Account
    document.getElementById('delete-account-btn')?.addEventListener('click', function() {
        const confirmed = confirm('SEI SICURO? Questa azione è IRREVERSIBILE. Tutti i tuoi dati saranno eliminati permanentemente.');

        if (!confirmed) return;

        const doubleConfirm = prompt('Scrivi "ELIMINA" per confermare:');

        if (doubleConfirm !== 'ELIMINA') {
            alert('Eliminazione annullata');
            return;
        }

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_delete_account',
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Account eliminato. Arrivederci!');
                    window.location.href = '<?php echo home_url(); ?>';
                } else {
                    alert(response.data.message || 'Errore durante l\'eliminazione');
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    });

    // === MESSAGING FUNCTIONALITY ===
    let currentConversation = null;
    let messagesRefreshInterval = null;

    // Check URL parameters for direct message link
    const urlParams = new URLSearchParams(window.location.search);
    const urlTab = urlParams.get('tab');
    const urlUserId = urlParams.get('user_id');
    const urlTravelId = urlParams.get('travel_id');

    if (urlTab === 'messages' && urlUserId && urlTravelId) {
        // Switch to messages tab
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.querySelector('[data-tab="messages"]').classList.add('active');
        document.getElementById('tab-messages').classList.add('active');

        // Load conversations and open specific one
        loadConversations(function() {
            setTimeout(function() {
                loadConversation(urlUserId, urlTravelId);
            }, 500);
        });
    }

    // Load conversations when messages tab is opened
    document.querySelector('[data-tab="messages"]')?.addEventListener('click', function() {
        loadConversations();
    });

    function loadConversations(callback) {
        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_get_user_conversations',
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayConversations(response.data);
                    if (callback) callback();
                } else {
                    document.getElementById('conversations-list-content').innerHTML =
                        '<p class="loading-message">Nessuna conversazione disponibile</p>';
                }
            },
            error: function() {
                document.getElementById('conversations-list-content').innerHTML =
                    '<p class="loading-message">Errore nel caricamento delle conversazioni</p>';
            }
        });
    }

    function displayConversations(conversations) {
        const container = document.getElementById('conversations-list-content');

        if (!conversations || conversations.length === 0) {
            container.innerHTML = '<p class="loading-message">Nessuna conversazione</p>';
            return;
        }

        let html = '';
        conversations.forEach(conv => {
            const unreadBadge = conv.unread_count > 0 ?
                `<div class="unread-badge">${conv.unread_count}</div>` : '';
            const unreadClass = conv.unread_count > 0 ? 'unread' : '';

            html += `
                <div class="conversation-item ${unreadClass}"
                     data-user-id="${conv.other_user_id}"
                     data-travel-id="${conv.travel_id}">
                    ${conv.avatar}
                    <div class="conversation-item-info">
                        <h4>${conv.other_user_name}</h4>
                        <p>${conv.travel_title}</p>
                        <span class="conversation-item-meta">${conv.last_message_time}</span>
                    </div>
                    ${unreadBadge}
                </div>
            `;
        });

        container.innerHTML = html;

        // Add click handlers to conversation items
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const travelId = this.dataset.travelId;
                loadConversation(userId, travelId);

                // Update active state
                document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                this.classList.remove('unread');
            });
        });
    }

    function loadConversation(otherUserId, travelId) {
        currentConversation = { user_id: otherUserId, travel_id: travelId };

        // Clear any existing refresh interval
        if (messagesRefreshInterval) {
            clearInterval(messagesRefreshInterval);
        }

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_get_conversation',
                other_user_id: otherUserId,
                travel_id: travelId,
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayConversation(response.data);

                    // Refresh messages every 10 seconds
                    messagesRefreshInterval = setInterval(function() {
                        refreshMessages(otherUserId, travelId);
                    }, 10000);
                } else {
                    alert('Errore nel caricamento della conversazione: ' + (response.data?.message || 'Sconosciuto'));
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    }

    function displayConversation(data) {
        // Hide no-conversation message, show conversation view
        document.querySelector('.no-conversation-selected').style.display = 'none';
        document.querySelector('.conversation-view').style.display = 'flex';

        // Update header
        document.getElementById('conversation-user-name').textContent = data.other_user_name;
        document.getElementById('conversation-travel-title').textContent = 'Viaggio: ' + data.travel_title;

        // Update block button
        const blockBtn = document.getElementById('block-conversation-btn');
        if (data.is_blocked) {
            blockBtn.textContent = 'Sblocca Conversazione';
            blockBtn.classList.remove('btn-danger');
            blockBtn.classList.add('btn-secondary');
        } else {
            blockBtn.textContent = 'Blocca Conversazione';
            blockBtn.classList.remove('btn-secondary');
            blockBtn.classList.add('btn-danger');
        }

        // Display messages
        displayMessages(data.messages);
    }

    function displayMessages(messages) {
        const container = document.getElementById('messages-list');

        if (!messages || messages.length === 0) {
            container.innerHTML = '<p class="loading-message">Nessun messaggio. Inizia la conversazione!</p>';
            return;
        }

        let html = '';
        messages.forEach(msg => {
            const sentClass = msg.is_sent ? 'sent' : '';
            html += `
                <div class="message-item ${sentClass}">
                    ${msg.avatar}
                    <div class="message-bubble">
                        <p class="message-text">${msg.message}</p>
                        <p class="message-time">${msg.time_ago}</p>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    function refreshMessages(otherUserId, travelId) {
        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_get_conversation',
                other_user_id: otherUserId,
                travel_id: travelId,
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayMessages(response.data.messages);
                }
            }
        });
    }

    // Send message
    document.getElementById('send-message-btn')?.addEventListener('click', function() {
        sendMessage();
    });

    document.getElementById('message-input')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function sendMessage() {
        if (!currentConversation) return;

        const messageInput = document.getElementById('message-input');
        const message = messageInput.value.trim();

        if (!message) {
            alert('Scrivi un messaggio prima di inviare');
            return;
        }

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_send_message',
                receiver_id: currentConversation.user_id,
                travel_id: currentConversation.travel_id,
                message: message,
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    messageInput.value = '';
                    loadConversation(currentConversation.user_id, currentConversation.travel_id);
                } else {
                    alert('Errore nell\'invio del messaggio: ' + (response.data?.message || 'Sconosciuto'));
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    }

    // Block/Unblock conversation
    document.getElementById('block-conversation-btn')?.addEventListener('click', function() {
        if (!currentConversation) return;

        const isBlocking = this.textContent.includes('Blocca');
        const confirmMsg = isBlocking ?
            'Sei sicuro di voler bloccare questa conversazione? Non riceverai più messaggi da questo utente.' :
            'Vuoi sbloccare questa conversazione?';

        if (!confirm(confirmMsg)) return;

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_block_conversation',
                other_user_id: currentConversation.user_id,
                travel_id: currentConversation.travel_id,
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(isBlocking ? 'Conversazione bloccata' : 'Conversazione sbloccata');
                    loadConversation(currentConversation.user_id, currentConversation.travel_id);
                    loadConversations(); // Refresh conversation list
                } else {
                    alert('Errore: ' + (response.data?.message || 'Sconosciuto'));
                }
            },
            error: function() {
                alert('Errore di connessione');
            }
        });
    });

    // Review Modal Handling
    const reviewModal = document.getElementById('review-modal');
    const reviewForm = document.getElementById('review-form');
    const modalCloseButtons = document.querySelectorAll('.modal-close');

    // Open review modal
    document.querySelectorAll('.btn-write-review').forEach(button => {
        button.addEventListener('click', function() {
            const travelId = this.getAttribute('data-travel-id');
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const travelTitle = this.getAttribute('data-travel-title');

            document.getElementById('review-travel-id').value = travelId;
            document.getElementById('review-user-id').value = userId;
            document.getElementById('review-modal-subtitle').textContent =
                `Recensisci ${userName} per il viaggio: ${travelTitle}`;

            // Reset form
            reviewForm.reset();
            document.querySelectorAll('.rating-star').forEach(star => {
                star.classList.remove('active');
            });

            reviewModal.style.display = 'block';
        });
    });

    // Close modal
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            reviewModal.style.display = 'none';
        });
    });

    // Close modal on outside click
    window.addEventListener('click', function(event) {
        if (event.target === reviewModal) {
            reviewModal.style.display = 'none';
        }
    });

    // Star rating interaction
    document.querySelectorAll('.star-rating').forEach(ratingGroup => {
        const stars = ratingGroup.querySelectorAll('.rating-star');
        const field = ratingGroup.getAttribute('data-field');
        const hiddenInput = document.getElementById(field);

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.getAttribute('data-value'));
                hiddenInput.value = value;

                // Update visual state
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            // Hover effect
            star.addEventListener('mouseenter', function() {
                const value = parseInt(this.getAttribute('data-value'));
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        // Reset on mouse leave
        ratingGroup.addEventListener('mouseleave', function() {
            const currentValue = parseInt(hiddenInput.value);
            stars.forEach((s, index) => {
                if (index < currentValue) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });

    // Submit review
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const travelId = document.getElementById('review-travel-id').value;
        const reviewedId = document.getElementById('review-user-id').value;
        const punctuality = parseInt(document.getElementById('punctuality').value);
        const groupSpirit = parseInt(document.getElementById('group_spirit').value);
        const respect = parseInt(document.getElementById('respect').value);
        const adaptability = parseInt(document.getElementById('adaptability').value);
        const comment = document.getElementById('review-comment').value;

        // Validate all ratings are set
        if (!punctuality || !groupSpirit || !respect || !adaptability) {
            alert('Per favore, assegna un punteggio per tutte e 4 le categorie');
            return;
        }

        if (punctuality < 1 || groupSpirit < 1 || respect < 1 || adaptability < 1) {
            alert('Per favore, assegna un punteggio per tutte e 4 le categorie');
            return;
        }

        const submitButton = reviewForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Invio...';

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_add_review',
                nonce: cdvAjax.nonce,
                travel_id: travelId,
                reviewed_id: reviewedId,
                punctuality: punctuality,
                group_spirit: groupSpirit,
                respect: respect,
                adaptability: adaptability,
                comment: comment
            },
            success: function(response) {
                if (response.success) {
                    alert('Recensione inviata con successo! Grazie per il tuo feedback.');
                    reviewModal.style.display = 'none';
                    // Reload page to update reviews list
                    location.reload();
                } else {
                    alert('Errore: ' + (response.data?.message || 'Si è verificato un errore'));
                    submitButton.disabled = false;
                    submitButton.textContent = 'Invia Recensione';
                }
            },
            error: function() {
                alert('Errore di connessione. Riprova più tardi.');
                submitButton.disabled = false;
                submitButton.textContent = 'Invia Recensione';
            }
        });
    });

    // Wishlist remove button handler
    const travelsArchiveUrl = '<?php echo esc_js(get_post_type_archive_link('viaggio')); ?>';

    document.addEventListener('click', function(e) {
        if (e.target.closest('.wishlist-remove-btn')) {
            e.preventDefault();
            e.stopPropagation();

            const btn = e.target.closest('.wishlist-remove-btn');
            const travelId = btn.dataset.travelId;
            const card = btn.closest('.wishlist-card');

            if (!confirm('Rimuovere questo viaggio dalla wishlist?')) {
                return;
            }

            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_toggle_wishlist',
                    nonce: cdvAjax.nonce,
                    travel_id: travelId
                },
                beforeSend: function() {
                    btn.disabled = true;
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove the card
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';

                        setTimeout(function() {
                            card.remove();

                            // Update badge count
                            const wishlistTab = document.querySelector('[data-tab="wishlist"]');
                            const badge = wishlistTab.querySelector('.badge-count');
                            const currentCount = badge ? parseInt(badge.textContent) : 0;
                            const newCount = currentCount - 1;

                            if (newCount > 0) {
                                if (badge) {
                                    badge.textContent = newCount;
                                } else {
                                    const newBadge = document.createElement('span');
                                    newBadge.className = 'badge-count';
                                    newBadge.textContent = newCount;
                                    wishlistTab.appendChild(newBadge);
                                }
                            } else if (badge) {
                                badge.remove();
                            }

                            // Check if wishlist is now empty
                            const remainingCards = document.querySelectorAll('.wishlist-card');
                            if (remainingCards.length === 0) {
                                const wishlistContent = document.getElementById('tab-wishlist');
                                wishlistContent.innerHTML = `
                                    <div class="section-header">
                                        <h2>💝 La Mia Wishlist</h2>
                                        <p>I viaggi che hai salvato per dopo</p>
                                    </div>
                                    <div class="empty-state">
                                        <span class="empty-icon">💝</span>
                                        <h3>La tua wishlist è vuota</h3>
                                        <p>Non hai ancora salvato nessun viaggio nella tua wishlist.</p>
                                        <p>Esplora i viaggi disponibili e salva quelli che ti interessano per trovarli facilmente!</p>
                                        <a href="${travelsArchiveUrl}" class="btn btn-primary">Esplora Viaggi</a>
                                    </div>
                                `;
                            }
                        }, 300);
                    }
                },
                error: function() {
                    alert('Errore durante la rimozione dalla wishlist');
                    btn.disabled = false;
                }
            });
        }
    });

    // Notifications functionality
    function loadNotifications() {
        const container = document.getElementById('notifications-container');
        if (!container) return;

        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_get_notifications',
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const notifications = response.data.notifications;
                    if (notifications.length === 0) {
                        container.innerHTML = `
                            <div class="empty-state">
                                <span class="empty-icon">🔔</span>
                                <h3>Nessuna notifica</h3>
                                <p>Al momento non hai notifiche.</p>
                            </div>
                        `;
                    } else {
                        let html = '<div class="notifications-list">';
                        notifications.forEach(function(notif) {
                            const readClass = notif.is_read ? 'read' : 'unread';
                            const linkStart = notif.link ? `<a href="${notif.link}" class="notification-item ${readClass}" data-id="${notif.id}">` : `<div class="notification-item ${readClass}" data-id="${notif.id}">`;
                            const linkEnd = notif.link ? '</a>' : '</div>';

                            html += linkStart;
                            html += `
                                <div class="notification-icon">${notif.icon}</div>
                                <div class="notification-content">
                                    <div class="notification-title">${notif.title}</div>
                                    <div class="notification-message">${notif.message}</div>
                                    <div class="notification-time">${notif.time_ago}</div>
                                </div>
                            `;
                            html += linkEnd;
                        });
                        html += '</div>';
                        container.innerHTML = html;
                    }
                } else {
                    container.innerHTML = '<div class="error-message">Errore nel caricamento delle notifiche</div>';
                }
            },
            error: function() {
                container.innerHTML = '<div class="error-message">Errore di connessione</div>';
            }
        });
    }

    // Load notifications when tab is opened
    document.addEventListener('click', function(e) {
        const tabButton = e.target.closest('[data-tab="notifications"]');
        if (tabButton) {
            loadNotifications();
        }

        // Mark notification as read when clicked
        const notificationItem = e.target.closest('.notification-item.unread');
        if (notificationItem) {
            const notifId = notificationItem.dataset.id;
            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_mark_notification_read',
                    nonce: cdvAjax.nonce,
                    notification_id: notifId
                },
                success: function(response) {
                    if (response.success) {
                        notificationItem.classList.remove('unread');
                        notificationItem.classList.add('read');

                        // Update badges
                        updateNotificationBadges(response.data.unread_count);
                    }
                }
            });
        }
    });

    // Mark all as read
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_mark_all_notifications_read',
                    nonce: cdvAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Mark all as read visually
                        document.querySelectorAll('.notification-item.unread').forEach(function(item) {
                            item.classList.remove('unread');
                            item.classList.add('read');
                        });

                        // Update badges
                        updateNotificationBadges(0);
                    }
                }
            });
        });
    }

    function updateNotificationBadges(count) {
        // Update tab badge
        const tabBadge = document.querySelector('[data-tab="notifications"] .badge-count');
        if (count > 0) {
            if (tabBadge) {
                tabBadge.textContent = count;
            } else {
                const tab = document.querySelector('[data-tab="notifications"]');
                const newBadge = document.createElement('span');
                newBadge.className = 'badge-count';
                newBadge.textContent = count;
                tab.appendChild(newBadge);
            }
        } else if (tabBadge) {
            tabBadge.remove();
        }

        // Update header badge
        const headerBadge = document.querySelector('.notifications-badge');
        if (count > 0) {
            if (headerBadge) {
                headerBadge.textContent = count;
            }
        } else if (headerBadge) {
            headerBadge.remove();
        }
    }

    // Check if we should open specific tab on page load
    if (urlParams.get('tab') === 'notifications') {
        const notifTab = document.querySelector('[data-tab="notifications"]');
        if (notifTab) {
            notifTab.click();
        }
    }
    if (urlParams.get('tab') === 'referral') {
        const referralTab = document.querySelector('[data-tab="referral"]');
        if (referralTab) {
            referralTab.click();
        }
    }

    // Referral System
    function loadReferralStats() {
        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_get_referral_stats',
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data;

                    // Update code and link
                    document.getElementById('referral-code').textContent = stats.code;
                    document.getElementById('referral-link').value = stats.link;

                    // Build stats HTML
                    const statsHTML = `
                        <div class="referral-stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">👥</div>
                                <div class="stat-value">${stats.total}</div>
                                <div class="stat-label">Referral Totali</div>
                            </div>
                            <div class="stat-card stat-success">
                                <div class="stat-icon">✅</div>
                                <div class="stat-value">${stats.completed}</div>
                                <div class="stat-label">Completati</div>
                            </div>
                            <div class="stat-card stat-warning">
                                <div class="stat-icon">⏳</div>
                                <div class="stat-value">${stats.pending}</div>
                                <div class="stat-label">In Attesa</div>
                            </div>
                            <div class="stat-card stat-primary">
                                <div class="stat-icon">🏆</div>
                                <div class="stat-value">${stats.current_points}</div>
                                <div class="stat-label">Punti Totali</div>
                            </div>
                        </div>
                    `;

                    document.querySelector('.referral-stats-container').innerHTML = statsHTML;

                    // Build referral history
                    if (stats.recent && stats.recent.length > 0) {
                        let historyHTML = '<div class="referral-list">';
                        stats.recent.forEach(ref => {
                            const statusClass = ref.status === 'completed' ? 'success' : 'pending';
                            const statusIcon = ref.status === 'completed' ? '✅' : '⏳';
                            const statusText = ref.status === 'completed' ? 'Completato' : 'In Attesa';
                            const date = new Date(ref.created_at).toLocaleDateString('it-IT');

                            historyHTML += `
                                <div class="referral-item">
                                    <div class="referral-avatar">
                                        ${ref.display_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="referral-details">
                                        <div class="referral-name">${ref.display_name}</div>
                                        <div class="referral-date">Registrato il ${date}</div>
                                    </div>
                                    <div class="referral-status status-${statusClass}">
                                        ${statusIcon} ${statusText}
                                    </div>
                                    ${ref.reward_given ? '<div class="referral-points">+' + ref.reward_points + ' punti</div>' : ''}
                                </div>
                            `;
                        });
                        historyHTML += '</div>';
                        document.getElementById('referral-history-container').innerHTML = historyHTML;
                    } else {
                        document.getElementById('referral-history-container').innerHTML = `
                            <div class="empty-state">
                                <p>Non hai ancora invitato nessuno. Condividi il tuo link!</p>
                            </div>
                        `;
                    }

                    // Build social share buttons
                    const shareHTML = `
                        <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(stats.link)}"
                           target="_blank" class="btn btn-sm" style="background: #1877f2; color: white;">
                            Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(stats.link)}&text=${encodeURIComponent('Unisciti a Compagni di Viaggi!')}"
                           target="_blank" class="btn btn-sm" style="background: #1da1f2; color: white;">
                            Twitter
                        </a>
                        <a href="https://wa.me/?text=${encodeURIComponent('Unisciti a Compagni di Viaggi! ' + stats.link)}"
                           target="_blank" class="btn btn-sm" style="background: #25d366; color: white;">
                            WhatsApp
                        </a>
                        <a href="mailto:?subject=${encodeURIComponent('Unisciti a Compagni di Viaggi!')}&body=${encodeURIComponent('Ho trovato questa fantastica piattaforma per trovare compagni di viaggio! Iscriviti usando il mio link: ' + stats.link)}"
                           class="btn btn-sm" style="background: #ea4335; color: white;">
                            Email
                        </a>
                    `;
                    document.querySelector('.social-share-ref').innerHTML = shareHTML;
                }
            }
        });
    }

    // Copy referral code
    document.getElementById('copy-referral-code-btn').addEventListener('click', function() {
        const code = document.getElementById('referral-code').textContent;
        navigator.clipboard.writeText(code).then(() => {
            this.textContent = '✅ Copiato!';
            setTimeout(() => {
                this.textContent = '📋 Copia';
            }, 2000);
        });
    });

    // Copy referral link
    document.getElementById('copy-referral-link-btn').addEventListener('click', function() {
        const link = document.getElementById('referral-link');
        link.select();
        document.execCommand('copy');
        this.textContent = '✅ Copiato!';
        setTimeout(() => {
            this.textContent = '📋 Copia Link';
        }, 2000);
    });

    // Share referral (native share API if available)
    document.getElementById('share-referral-btn').addEventListener('click', function() {
        const link = document.getElementById('referral-link').value;

        if (navigator.share) {
            navigator.share({
                title: 'Unisciti a Compagni di Viaggi!',
                text: 'Ho trovato questa fantastica piattaforma per trovare compagni di viaggio!',
                url: link
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(link).then(() => {
                alert('Link copiato negli appunti!');
            });
        }
    });

    // Load referral stats when referral tab is opened
    document.querySelector('[data-tab="referral"]').addEventListener('click', function() {
        if (document.querySelector('.referral-stats-container .stats-loading')) {
            loadReferralStats();
        }
    });

    // Organizer Statistics
    function loadOrganizerStats() {
        jQuery.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_get_organizer_stats',
                nonce: cdvAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data;

                    // Overview stats grid
                    const overviewHTML = `
                        <div class="stats-overview-grid">
                            <div class="stat-overview-card">
                                <div class="stat-icon">🗺️</div>
                                <div class="stat-number">${stats.overview.total_travels}</div>
                                <div class="stat-label">Viaggi Creati</div>
                            </div>
                            <div class="stat-overview-card">
                                <div class="stat-icon">✅</div>
                                <div class="stat-number">${stats.overview.active_travels}</div>
                                <div class="stat-label">Viaggi Attivi</div>
                            </div>
                            <div class="stat-overview-card">
                                <div class="stat-icon">👥</div>
                                <div class="stat-number">${stats.overview.total_participants}</div>
                                <div class="stat-label">Partecipanti Totali</div>
                            </div>
                            <div class="stat-overview-card">
                                <div class="stat-icon">⏳</div>
                                <div class="stat-number">${stats.overview.pending_requests}</div>
                                <div class="stat-label">Richieste Pendenti</div>
                            </div>
                            <div class="stat-overview-card">
                                <div class="stat-icon">⭐</div>
                                <div class="stat-number">${stats.overview.average_rating.toFixed(1)}</div>
                                <div class="stat-label">Valutazione Media</div>
                            </div>
                            <div class="stat-overview-card">
                                <div class="stat-icon">💬</div>
                                <div class="stat-number">${stats.overview.total_reviews}</div>
                                <div class="stat-label">Recensioni Ricevute</div>
                            </div>
                            <div class="stat-overview-card">
                                <div class="stat-icon">💰</div>
                                <div class="stat-number">€${Math.round(stats.overview.total_revenue).toLocaleString()}</div>
                                <div class="stat-label">Ricavi Totali</div>
                            </div>
                            <div class="stat-overview-card">
                                <div class="stat-icon">📈</div>
                                <div class="stat-number">${stats.overview.this_month_participants}</div>
                                <div class="stat-label">Partecipanti Questo Mese</div>
                            </div>
                        </div>
                    `;
                    document.getElementById('overview-stats-container').innerHTML = overviewHTML;

                    // Travel performance
                    if (stats.travel_performance && stats.travel_performance.length > 0) {
                        let perfHTML = '<h3>🎯 Performance Viaggi</h3><div class="travel-perf-list">';
                        stats.travel_performance.forEach(travel => {
                            perfHTML += `
                                <div class="travel-perf-item">
                                    <div class="travel-perf-title">${travel.post_title}</div>
                                    <div class="travel-perf-stats">
                                        <span>👥 ${travel.accepted_count}/${travel.max_participants || 0}</span>
                                        <span>📊 ${travel.fill_rate}% pieno</span>
                                        <span>💰 €${Math.round(travel.potential_revenue || 0).toLocaleString()}</span>
                                        ${travel.pending_count > 0 ? '<span class="badge-warning">⏳ ' + travel.pending_count + ' in attesa</span>' : ''}
                                    </div>
                                </div>
                            `;
                        });
                        perfHTML += '</div>';
                        document.getElementById('travel-performance-container').innerHTML = perfHTML;
                    }

                    // Popular destinations
                    if (stats.popular_destinations && stats.popular_destinations.length > 0) {
                        let destHTML = '<h3>🌍 Destinazioni Più Popolari</h3><div class="destinations-list">';
                        stats.popular_destinations.forEach(dest => {
                            destHTML += `
                                <div class="destination-item">
                                    <div class="dest-name">${dest.destination}</div>
                                    <div class="dest-stats">
                                        <span>${dest.travel_count} viagg${dest.travel_count === 1 ? 'io' : 'i'}</span>
                                        <span>•</span>
                                        <span>${dest.total_participants} partecipanti</span>
                                    </div>
                                </div>
                            `;
                        });
                        destHTML += '</div>';
                        document.getElementById('popular-destinations-container').innerHTML = destHTML;
                    }

                    // Demographics
                    if (stats.demographics && stats.demographics.total > 0) {
                        let demoHTML = '<h3>👥 Demografia Partecipanti</h3>';
                        demoHTML += '<div class="demographics-grid">';
                        demoHTML += '<div class="demo-card"><strong>Totale Partecipanti:</strong> ' + stats.demographics.total + '</div>';
                        demoHTML += '<div class="demo-card"><strong>Viaggiatori Abituali:</strong> ' + stats.demographics.repeat_travelers + '</div>';
                        demoHTML += '</div>';
                        document.getElementById('demographics-container').innerHTML = demoHTML;
                    }

                    // Hide loading
                    document.querySelector('.stats-loading-container').style.display = 'none';
                }
            },
            error: function() {
                document.querySelector('.stats-loading-container').innerHTML = '<p>Errore nel caricamento delle statistiche.</p>';
            }
        });
    }

    // Load statistics when statistics tab is opened
    const statsTab = document.querySelector('[data-tab="statistics"]');
    if (statsTab) {
        statsTab.addEventListener('click', function() {
            if (document.querySelector('.stats-loading-container')) {
                loadOrganizerStats();
            }
        });
    }
});
</script>

<?php get_footer(); ?>
