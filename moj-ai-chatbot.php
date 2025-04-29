<?php
/*
Plugin Name: Moj AI Chatbot 2
Description: Jednostavan AI chatbot plugin za odgovaranje na pitanja o proizvodima (maslinovo ulje, pekmezi, trešnje).
Version: 1.0
Author: Ivan Turk
*/


/*
add_action('wp_footer', function() {
    echo '<p style="text-align: center; color: green;">✅ AI Chatbot plugin je aktivan i učitan.</p>';
}); 
*/

// Dodaj meni u WordPress admin
add_action('admin_menu', 'mojchat_dodaj_admin_meni');

function mojchat_dodaj_admin_meni() {
    add_menu_page(
        'AI Chatbot Postavke',       // Naslov stranice
        'AI Chatbot',                // Naziv menija
        'manage_options',            // Tko smije vidjeti
        'mojchat_postavke',          // Slug stranice
        'mojchat_prikazi_postavke',  // Callback funkcija
        'dashicons-format-chat',     // Ikonica (WP Dashicon)
        100                          // Pozicija
    );
}

function mojchat_prikazi_postavke() {
    ?>
    <div class="wrap">
        <h1>Postavke AI Chatbota</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('mojchat_postavke_grupa');
                do_settings_sections('mojchat_postavke');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'mojchat_postavke_init');

function mojchat_postavke_init() {
    // Grupa
    register_setting('mojchat_postavke_grupa', 'mojchat_api_key');
    register_setting('mojchat_postavke_grupa', 'mojchat_naziv_tvrtke');
    register_setting('mojchat_postavke_grupa', 'mojchat_popis_proizvoda');
    register_setting('mojchat_postavke_grupa', 'mojchat_fallback_poruka');
    register_setting('mojchat_postavke_grupa', 'mojchat_avatar_url');

    // Sekcija
    add_settings_section('mojchat_sekcija', 'Opće postavke', null, 'mojchat_postavke');

    // Polja
    add_settings_field('mojchat_api_key', 'OpenAI API ključ', 'mojchat_api_key_callback', 'mojchat_postavke', 'mojchat_sekcija');
    add_settings_field('mojchat_naziv_tvrtke', 'Naziv tvrtke', 'mojchat_naziv_tvrtke_callback', 'mojchat_postavke', 'mojchat_sekcija');
    add_settings_field('mojchat_popis_proizvoda', 'Popis proizvoda', 'mojchat_popis_proizvoda_callback', 'mojchat_postavke', 'mojchat_sekcija');
    add_settings_field('mojchat_fallback_poruka', 'Poruka kupcu za nevezana pitanja', 'mojchat_fallback_poruka_callback', 'mojchat_postavke', 'mojchat_sekcija');
    add_settings_field('mojchat_avatar_url', 'URL slike avatara', 'mojchat_avatar_url_callback', 'mojchat_postavke', 'mojchat_sekcija');
}

function mojchat_avatar_url_callback() {
    $value = esc_url(get_option('mojchat_avatar_url'));
    echo '<input type="text" id="mojchat_avatar_url" name="mojchat_avatar_url" value="' . $value . '" size="50">';
    echo '<button type="button" class="button" id="mojchat_upload_button">Odaberi sliku</button>';
    echo '<p class="description">Kliknite "Odaberi sliku" za izbor iz WordPress Medija.</p>';
}



function mojchat_api_key_callback() {
    $value = esc_attr(get_option('mojchat_api_key'));
    echo '<input type="text" name="mojchat_api_key" value="' . $value . '" size="50">';
}

function mojchat_naziv_tvrtke_callback() {
    $value = esc_attr(get_option('mojchat_naziv_tvrtke'));
    echo '<input type="text" name="mojchat_naziv_tvrtke" value="' . $value . '" size="50">';
}

function mojchat_popis_proizvoda_callback() {
    $value = esc_textarea(get_option('mojchat_popis_proizvoda'));
    echo '<textarea name="mojchat_popis_proizvoda" rows="5" cols="50">' . $value . '</textarea>';
}

function mojchat_fallback_poruka_callback() {
    $value = esc_textarea(get_option('mojchat_fallback_poruka'));
    echo '<textarea name="mojchat_fallback_poruka" rows="3" cols="50">' . $value . '</textarea>';
}


//JS i CSS hooks
add_action('wp_enqueue_scripts', 'mojchat_ucitaj_assets');

function mojchat_ucitaj_assets() {
    wp_enqueue_style('mojchat-style', plugin_dir_url(__FILE__) . 'public/chatbot.css', array(), time());

    wp_enqueue_script('mojchat-script', plugin_dir_url(__FILE__) . 'public/chatbot.js', array('jquery'), time(), true);

    // Dodaj AJAX URL
    wp_localize_script('mojchat-script', 'mojchat_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'avatar_url' => esc_url(get_option('mojchat_avatar_url'))
    ));

    // 👇 OVO ispod - samo za admin stranicu
    if (is_admin()) {
        wp_enqueue_media(); // Učitaj WordPress Media Library skriptu
        wp_enqueue_script('mojchat-admin-script', plugin_dir_url(__FILE__) . 'public/admin.js', array('jquery'), time(), true);
    }
    
}

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_media();
    wp_enqueue_script('mojchat-admin-script', plugin_dir_url(__FILE__) . 'public/admin.js', array('jquery'), time(), true);
});



// Backend handler koji vraća odgovore iz OpenAI-a

add_action('wp_ajax_mojchat_posalji_upit', 'mojchat_posalji_upit');
add_action('wp_ajax_nopriv_mojchat_posalji_upit', 'mojchat_posalji_upit');

function mojchat_posalji_upit() {
    $poruka = sanitize_text_field($_POST['poruka'] ?? '');

    if (empty($poruka)) {
        wp_send_json_error(['odgovor' => 'Greška: prazna poruka.']);
    }

    // Uzmi API ključ i ostale podatke
    $api_key = get_option('mojchat_api_key');
    $naziv_tvrtke = get_option('mojchat_naziv_tvrtke');
    $proizvodi = get_option('mojchat_popis_proizvoda');
    $fallback = get_option('mojchat_fallback_poruka');

    if (!$api_key) {
        wp_send_json_success(['odgovor' => 'API ključ nije postavljen.']);
    }

    // Sastavi prompt
    $prompt = "Ti si AI agent podrške za tvrtku $naziv_tvrtke. 
Odgovaraj samo na pitanja vezana uz sljedeće proizvode: $proizvodi. 
Ako pitanje nije vezano uz njih, reci: \"$fallback\". 
Pitanje korisnika: $poruka";

    // Pozovi OpenAI API
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo-0125',
            'messages' => [
                ['role' => 'system', 'content' => 'Ti si korisnička podrška.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 300
        ])
    ]);

    if (is_wp_error($response)) {
        wp_send_json_success(['odgovor' => 'Greška u komunikaciji s OpenAI API-jem.']);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $odgovor = $body['choices'][0]['message']['content'] ?? 'Nema odgovora.';

    wp_send_json_success(['odgovor' => $odgovor]);
}

