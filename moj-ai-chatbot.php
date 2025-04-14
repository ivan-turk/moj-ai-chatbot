<?php
/*
Plugin Name: Moj AI Chatbot
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
    register_setting('mojchat_postavke_grupa', 'mojchat_naziv_firme');
    register_setting('mojchat_postavke_grupa', 'mojchat_popis_proizvoda');
    register_setting('mojchat_postavke_grupa', 'mojchat_fallback_poruka');

    // Sekcija
    add_settings_section('mojchat_sekcija', 'Opće postavke', null, 'mojchat_postavke');

    // Polja
    add_settings_field('mojchat_api_key', 'OpenAI API ključ', 'mojchat_api_key_callback', 'mojchat_postavke', 'mojchat_sekcija');
    add_settings_field('mojchat_naziv_firme', 'Naziv poduzeća', 'mojchat_naziv_firme_callback', 'mojchat_postavke', 'mojchat_sekcija');
    add_settings_field('mojchat_popis_proizvoda', 'Popis proizvoda', 'mojchat_popis_proizvoda_callback', 'mojchat_postavke', 'mojchat_sekcija');
    add_settings_field('mojchat_fallback_poruka', 'Poruka kupcu za nevezana pitanja', 'mojchat_fallback_poruka_callback', 'mojchat_postavke', 'mojchat_sekcija');
}

function mojchat_api_key_callback() {
    $value = esc_attr(get_option('mojchat_api_key'));
    echo '<input type="text" name="mojchat_api_key" value="' . $value . '" size="50">';
}

function mojchat_naziv_firme_callback() {
    $value = esc_attr(get_option('mojchat_naziv_firme'));
    echo '<input type="text" name="mojchat_naziv_firme" value="' . $value . '" size="50">';
}

function mojchat_popis_proizvoda_callback() {
    $value = esc_textarea(get_option('mojchat_popis_proizvoda'));
    echo '<textarea name="mojchat_popis_proizvoda" rows="5" cols="50">' . $value . '</textarea>';
}

function mojchat_fallback_poruka_callback() {
    $value = esc_textarea(get_option('mojchat_fallback_poruka'));
    echo '<textarea name="mojchat_fallback_poruka" rows="3" cols="50">' . $value . '</textarea>';
}

