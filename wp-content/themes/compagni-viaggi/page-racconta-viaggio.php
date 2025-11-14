<?php
/**
 * Template Name: Racconta il Tuo Viaggio
 *
 * Form per creare/modificare racconti di viaggio
 */

get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <main class="main-content">
        <div class="container">
            <div class="section text-center">
                <h1>Login Required</h1>
                <p>Devi essere registrato e aver effettuato l'accesso per raccontare il tuo viaggio.</p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn-primary">Log In</a>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn-secondary">Sign Up</a>
            </div>
        </div>
    </main>
    <?php
    get_footer();
    exit;
}

$current_user = wp_get_current_user();
$is_viaggiatore = in_array('viaggiatore', $current_user->roles) || in_array('administrator', $current_user->roles);

if (!$is_viaggiatore) {
    ?>
    <main class="main-content">
        <div class="container">
            <div class="section text-center">
                <h1>Access Denied</h1>
                <p>Only travelers can publish stories.</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">Back to Home</a>
            </div>
        </div>
    </main>
    <?php
    get_footer();
    exit;
}

// Check if editing existing story
$story_id = isset($_GET['story_id']) ? intval($_GET['story_id']) : 0;
$editing = false;
$story = null;

if ($story_id > 0) {
    $story = get_post($story_id);
    if ($story && $story->post_type === 'racconto' && ($story->post_author == get_current_user_id() || current_user_can('edit_others_posts'))) {
        $editing = true;
    } else {
        $story_id = 0;
    }
}

// Get categories
$categories = get_terms(array(
    'taxonomy' => 'categoria_racconto',
    'hide_empty' => false,
));
?>

<main class="main-content">
    <div class="container" style="max-width: 900px;">
        <div class="section">
            <div class="page-header" style="text-align: center; margin-bottom: calc(var(--spacing-unit) * 5);">
                <h1><?php echo $editing ? 'Edit Your Story' : 'Racconta il Tuo Viaggio'; ?></h1>
                <p style="font-size: 1.1rem; color: var(--text-medium);">
                    Share your experience, give tips and inspire other travelers
                </p>
            </div>

            <form id="story-form" class="cdv-form" enctype="multipart/form-data">
                <?php wp_nonce_field('cdv_ajax_nonce', 'cdv_nonce'); ?>
                <input type="hidden" name="story_id" id="story_id" value="<?php echo esc_attr($story_id); ?>">

                <!-- Titolo -->
                <div class="form-group">
                    <label for="story_title">Story Title *</label>
                    <input
                        type="text"
                        id="story_title"
                        name="title"
                        class="form-control"
                        placeholder="Es: La mia avventura alle Maldive"
                        value="<?php echo $editing ? esc_attr($story->post_title) : ''; ?>"
                        required
                    >
                </div>

                <!-- Featured Image -->
                <div class="form-group">
                    <label>Featured Image</label>
                    <div id="story-image-upload-area" style="margin-bottom: calc(var(--spacing-unit) * 2);">
                        <?php if ($editing && has_post_thumbnail($story_id)) : ?>
                            <div id="current-story-image" style="margin-bottom: calc(var(--spacing-unit) * 2);">
                                <img src="<?php echo esc_url(get_the_post_thumbnail_url($story_id, 'large')); ?>" alt="Current image" style="max-width: 100%; height: auto; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="story_image" name="story_image" accept="image/*" style="display: none;">
                        <button type="button" id="upload-story-image-btn" class="btn-secondary">
                            <?php echo $editing && has_post_thumbnail($story_id) ? 'Change Image' : 'Upload Image'; ?>
                        </button>
                        <p class="description">JPG o PNG, massimo 10MB</p>
                    </div>
                    <div id="story-image-preview" style="display: none; margin-top: calc(var(--spacing-unit) * 2);">
                        <img src="" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">
                    </div>
                </div>

                <!-- Contenuto -->
                <div class="form-group">
                    <label for="story_content">Your Story *</label>
                    <textarea
                        id="story_content"
                        name="content"
                        class="form-control"
                        rows="15"
                        placeholder="Tell your experience in detail. Cosa hai visto? Cosa ti è piaciuto? Quali consigli daresti?"
                        required
                    ><?php echo $editing ? esc_textarea($story->post_content) : ''; ?></textarea>
                    <p class="description">Minimum 200 characters. Be detailed and helpful!</p>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: calc(var(--spacing-unit) * 3);">
                    <!-- Destination -->
                    <div class="form-group">
                        <label for="story_destination">Destination *</label>
                        <input
                            type="text"
                            id="story_destination"
                            name="destination"
                            class="form-control"
                            placeholder="Es: Maldive"
                            value="<?php echo $editing ? esc_attr(get_post_meta($story_id, 'cdv_destination', true)) : ''; ?>"
                            required
                        >
                    </div>

                    <!-- Categoria -->
                    <div class="form-group">
                        <label for="story_category">Story Type</label>
                        <select id="story_category" name="category" class="form-control">
                            <option value="">Select category</option>
                            <?php
                            $current_category = $editing ? wp_get_post_terms($story_id, 'categoria_racconto') : array();
                            $current_category_id = !empty($current_category) ? $current_category[0]->term_id : 0;

                            foreach ($categories as $cat) :
                            ?>
                                <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($current_category_id, $cat->term_id); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: calc(var(--spacing-unit) * 3);">
                    <!-- Data viaggio -->
                    <div class="form-group">
                        <label for="story_travel_date">When did you travel?</label>
                        <input
                            type="month"
                            id="story_travel_date"
                            name="travel_date"
                            class="form-control"
                            value="<?php echo $editing ? esc_attr(get_post_meta($story_id, 'cdv_travel_date', true)) : ''; ?>"
                        >
                    </div>

                    <!-- Durata -->
                    <div class="form-group">
                        <label for="story_duration">Journey duration</label>
                        <input
                            type="text"
                            id="story_duration"
                            name="duration"
                            class="form-control"
                            placeholder="Es: 7 giorni"
                            value="<?php echo $editing ? esc_attr(get_post_meta($story_id, 'cdv_duration', true)) : ''; ?>"
                        >
                    </div>
                </div>

                <!-- Tags -->
                <div class="form-group">
                    <label for="story_tags">Tags (comma separated)</label>
                    <input
                        type="text"
                        id="story_tags"
                        name="tags"
                        class="form-control"
                        placeholder="Es: mare, relax, avventura, spiaggia"
                        value="<?php
                        if ($editing) {
                            $tags = wp_get_post_terms($story_id, 'tag_racconto');
                            echo esc_attr(implode(', ', wp_list_pluck($tags, 'name')));
                        }
                        ?>"
                    >
                    <p class="description">Help others find your story</p>
                </div>

                <!-- Form Messages -->
                <div id="story-form-message" style="display: none; margin: calc(var(--spacing-unit) * 2) 0;"></div>

                <!-- Submit Button -->
                <div class="form-actions" style="display: flex; gap: calc(var(--spacing-unit) * 2); margin-top: calc(var(--spacing-unit) * 4);">
                    <button type="submit" id="submit-story-btn" class="btn-primary" style="flex: 1;">
                        <?php echo $editing ? 'Update Story' : 'Publish Story'; ?>
                    </button>
                    <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
jQuery(document).ready(function($) {
    const form = $('#story-form');
    const messageDiv = $('#story-form-message');
    const submitBtn = $('#submit-story-btn');
    const storyId = $('#story_id').val();

    // Image upload
    $('#upload-story-image-btn').on('click', function() {
        $('#story_image').click();
    });

    $('#story_image').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#story-image-preview img').attr('src', e.target.result);
                $('#story-image-preview').show();
                $('#current-story-image').hide();
            };
            reader.readAsDataURL(file);
        }
    });

    // Form submission
    form.on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'cdv_submit_story');
        formData.append('nonce', $('#cdv_nonce').val());
        formData.append('story_id', storyId);
        formData.append('title', $('#story_title').val());
        formData.append('content', $('#story_content').val());
        formData.append('destination', $('#story_destination').val());
        formData.append('category', $('#story_category').val());
        formData.append('tags', $('#story_tags').val());
        formData.append('travel_date', $('#story_travel_date').val());
        formData.append('duration', $('#story_duration').val());

        submitBtn.prop('disabled', true).text('Publishing...');
        messageDiv.hide();

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // If there's an image, upload it
                    const imageFile = $('#story_image')[0].files[0];
                    if (imageFile && response.data.story_id) {
                        uploadStoryImage(response.data.story_id, imageFile, response.data.story_url);
                    } else {
                        // Redirect to story
                        messageDiv.html('<div class="alert alert-success">' + response.data.message + '</div>').show();
                        setTimeout(function() {
                            window.location.href = response.data.story_url;
                        }, 1500);
                    }
                } else {
                    messageDiv.html('<div class="alert alert-error">' + response.data.message + '</div>').show();
                    submitBtn.prop('disabled', false).text('<?php echo $editing ? 'Update Story' : 'Publish Story'; ?>');
                }
            },
            error: function() {
                messageDiv.html('<div class="alert alert-error">Errore di connessione. Riprova.</div>').show();
                submitBtn.prop('disabled', false).text('<?php echo $editing ? 'Update Story' : 'Publish Story'; ?>');
            }
        });
    });

    function uploadStoryImage(storyId, imageFile, redirectUrl) {
        const imageData = new FormData();
        imageData.append('action', 'cdv_upload_story_image');
        imageData.append('nonce', $('#cdv_nonce').val());
        imageData.append('story_id', storyId);
        imageData.append('story_image', imageFile);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: imageData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    messageDiv.html('<div class="alert alert-success">Story published successfully!</div>').show();
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1500);
                } else {
                    // Story saved but image upload failed
                    messageDiv.html('<div class="alert alert-warning">Story saved but image upload error: ' + response.data.message + '</div>').show();
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 2000);
                }
            },
            error: function() {
                messageDiv.html('<div class="alert alert-warning">Story saved but image upload error.</div>').show();
                setTimeout(function() {
                    window.location.href = redirectUrl;
                }, 2000);
            }
        });
    }
});
</script>

<style>
.cdv-form {
    background: white;
    padding: calc(var(--spacing-unit) * 4);
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: calc(var(--spacing-unit) * 3);
}

.form-group label {
    display: block;
    margin-bottom: calc(var(--spacing-unit) * 1);
    font-weight: 600;
    color: var(--text-dark);
}

.form-control {
    width: 100%;
    padding: calc(var(--spacing-unit) * 1.5);
    border: 2px solid var(--border-color);
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
}

.form-control textarea {
    resize: vertical;
    min-height: 200px;
}

.description {
    margin-top: calc(var(--spacing-unit) * 0.5);
    font-size: 0.9rem;
    color: var(--text-light);
}

.alert {
    padding: calc(var(--spacing-unit) * 2);
    border-radius: 6px;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr !important;
    }

    .cdv-form {
        padding: calc(var(--spacing-unit) * 2);
    }
}
</style>

<?php
get_footer();
?>
