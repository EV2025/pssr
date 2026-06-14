<?php
if ( ! defined('ABSPATH') ) exit;

/* === Charger le CSS du thème enfant + notre JS === */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'child-style',
        trailingslashit( get_stylesheet_directory_uri() ) . 'style.css',
        array(),
        filemtime( get_stylesheet_directory() . '/style.css' )
    );

    wp_enqueue_script(
        'pssr-js',
        trailingslashit( get_stylesheet_directory_uri() ) . 'pssr.js',
        array(),
        filemtime( get_stylesheet_directory() . '/pssr.js' ),
        true
    );
});

/* === Réglages généraux === */
define( 'PSSR_ADMIN_EMAIL', 'equilibrevital.bruxelles@gmail.com' );

/* === Redirection sûre === */
function pssr_redirect( $url, $query = array() ){
    if ( $query ) $url = add_query_arg( $query, $url );
    wp_safe_redirect( $url );
    exit;
}

/* === SHORTCODE [pssr_inscription] — Formulaire d’inscription === */
add_shortcode('pssr_inscription', function(){
    $months = array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre');
    $year   = 2026;

    ob_start(); ?>
    <section id="inscriptions">
      <div class="section-wrap">
        <h2 class="sec-title">Inscriptions jusqu'au 31 octobre 2026</h2>

        <div class="sessions" id="i-list" aria-label="Sélectionne un mois d’inscription">
          <?php foreach ($months as $m): ?>
            <button type="button" class="session" data-month="<?php echo esc_attr($m . ' ' . $year); ?>">
              <span class="m"><?php echo esc_html($m); ?></span>
              <span class="y"><?php echo esc_html($year); ?></span>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="form">
          <h3 style="margin:0 0 10px; color:#6c2ba8">Formulaire d’inscription</h3>

          <form id="i-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <input type="hidden" name="action" value="pssr_inscription">
            <?php wp_nonce_field('pssr_inscription', 'pssr_nonce'); ?>
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;height:0;width:0;opacity:0;" aria-hidden="true">

            <div class="row">
              <input id="fnom" type="text" name="prenom_nom" placeholder="Prénom &amp; Nom" required />
              <input id="mail" type="email" name="email" placeholder="Email" required />
              <input id="tel" type="tel" name="tel" placeholder="Tél" pattern="[\s\S]{0,30}" style="max-width:160px" />
              <input id="ref" type="text" name="ref_sociale" placeholder="Référent·e social·e" required />
              <input id="sess" type="text" name="session" placeholder="Mois d’inscription" required readonly />
              <button type="submit">Envoyer</button>
            </div>
          </form>

          <div class="ok" id="i-msg" role="status" aria-live="polite" style="display:none;">Merci ! Inscription bien reçue.</div>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
});

/* === SHORTCODE [pssr_contact] — Formulaire de contact === */
add_shortcode('pssr_contact', function(){
    ob_start(); ?>
    <section id="contact">
      <div class="section-wrap" style="max-width:1280px;margin:0 auto;padding:48px 20px 56px;background:#fff;border-radius:24px;box-shadow:0 8px 32px #e1bee7;">
        <form class="form" id="contact-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" autocomplete="on">
          <input type="hidden" name="action" value="pssr_contact">
          <?php wp_nonce_field('pssr_contact', 'pssr_nonce'); ?>
          <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;height:0;width:0;opacity:0;" aria-hidden="true">

          <h3 style="margin:0 0 10px; color:#6c2ba8">Formulaire de contact</h3>
          <div class="row">
            <input id="c-nom" type="text" name="nom" placeholder="Prénom &amp; Nom" required style="flex:1 1 180px;min-width:120px;" />
            <input id="c-mail" type="email" name="email" placeholder="Email" required style="flex:1 1 180px;min-width:120px;" />
            <input id="c-tel" type="tel" name="tel" placeholder="Tél" pattern="[\s\S]{0,30}" style="flex:1 1 120px;max-width:160px;" />
            <textarea id="c-msg" name="message" placeholder="Message" rows="3" required style="flex:2 1 260px;min-width:180px;"></textarea>
            <button type="submit" style="flex:1 1 120px;">Envoyer</button>
          </div>
        </form>
        <div class="ok" id="contact-msg" role="status" aria-live="polite" style="display:none;">Merci ! Message bien envoyé.</div>
      </div>
    </section>
    <?php
    return ob_get_clean();
});

/* === SHORTCODE [pssr_thanks] — Message sur /merci/ === */
add_shortcode('pssr_thanks', function(){
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $type   = isset($_GET['type'])   ? sanitize_text_field($_GET['type'])   : '';

    ob_start(); ?>
    <div class="section-wrap" style="max-width:800px;margin:auto;">
      <?php if ($status === 'ok' && $type === 'inscription'): ?>
        <div class="ok" style="display:block;">🎉 Merci ! Votre inscription a bien été enregistrée.</div>
      <?php elseif ($status === 'ok' && $type === 'contact'): ?>
        <div class="ok" style="display:block;">📨 Merci ! Votre message a bien été envoyé.</div>
      <?php elseif ($status === 'error'): ?>
        <div class="ok" style="display:block;background:#ffe9e9;color:#8a1f1f;">⚠️ Oups, un problème est survenu. Veuillez réessayer.</div>
      <?php else: ?>
        <div class="ok" style="display:block;">✅ Opération terminée.</div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

/* === Handlers admin-post — INSCRIPTION === */
add_action( 'admin_post_nopriv_pssr_inscription', 'pssr_handle_inscription' );
add_action( 'admin_post_pssr_inscription',        'pssr_handle_inscription' );

function pssr_handle_inscription() {
    if ( ! isset($_POST['pssr_nonce']) || ! wp_verify_nonce( $_POST['pssr_nonce'], 'pssr_inscription' ) ) {
        pssr_redirect( 'https://pssr.be/merci/', array('status'=>'error','code'=>'nonce') );
    }
    if ( ! empty( $_POST['website'] ?? '' ) ) { // bot
        pssr_redirect( 'https://pssr.be/merci/', array('status'=>'ok','type'=>'inscription') );
    }

    $nom     = sanitize_text_field( $_POST['prenom_nom'] ?? '' );
    $email   = sanitize_email( $_POST['email'] ?? '' );
    $tel     = sanitize_text_field( $_POST['tel'] ?? '' );
    $ref     = sanitize_text_field( $_POST['ref_sociale'] ?? '' );
    $session = sanitize_text_field( $_POST['session'] ?? '' );

    if ( empty($nom) || empty($email) || ! is_email($email) || empty($session) ) {
        pssr_redirect( 'https://pssr.be/merci/', array('status'=>'error','code'=>'invalid') );
    }

    // Mail admin
    $subject_admin = 'Nouvelle inscription PSSR';
    $body_admin = "Nouvelle inscription reçue :\n\n"
        . "Prénom & Nom : $nom\nEmail : $email\nTéléphone : $tel\nRéférent·e social·e : $ref\nSession choisie : $session\n";
    $headers_admin = array('Content-Type: text/plain; charset=UTF-8','Reply-To: ' . $nom . ' <' . $email . '>');
    wp_mail( PSSR_ADMIN_EMAIL, $subject_admin, $body_admin, $headers_admin );

    // Mail utilisateur
    $subject_user = "Confirmation d'inscription au PSSR";
    $body_user = "Bonjour $nom,\n\nVotre inscription au PSSR est enregistrée pour la session : $session.\nNous vous contacterons prochainement.\n\nÉquilibre Vital";
    $headers_user = array('Content-Type: text/plain; charset=UTF-8','Reply-To: Équilibre Vital <' . PSSR_ADMIN_EMAIL . '>','From: Équilibre Vital <' . PSSR_ADMIN_EMAIL . '>');
    wp_mail( $email, $subject_user, $body_user, $headers_user );

    pssr_redirect( 'https://pssr.be/merci/', array('status'=>'ok','type'=>'inscription') );
}

/* === Handlers admin-post — CONTACT === */
add_action( 'admin_post_nopriv_pssr_contact', 'pssr_handle_contact' );
add_action( 'admin_post_pssr_contact',        'pssr_handle_contact' );

function pssr_handle_contact() {
    if ( ! isset($_POST['pssr_nonce']) || ! wp_verify_nonce( $_POST['pssr_nonce'], 'pssr_contact' ) ) {
        pssr_redirect( 'https://pssr.be/merci/', array('status'=>'error','code'=>'nonce') );
    }
    if ( ! empty( $_POST['website'] ?? '' ) ) { // bot
        pssr_redirect( 'https://pssr.be/merci/', array('status'=>'ok','type'=>'contact') );
    }

    $nom   = sanitize_text_field( $_POST['nom'] ?? '' );
    $email = sanitize_email( $_POST['email'] ?? '' );
    $tel   = sanitize_text_field( $_POST['tel'] ?? '' );
    $msg   = wp_kses_post( $_POST['message'] ?? '' );

    if ( empty($nom) || empty($email) || ! is_email($email) || empty($msg) ) {
        pssr_redirect( 'https://pssr.be/merci/', array('status'=>'error','code'=>'invalid') );
    }

    // Mail admin
    $subject_admin = 'Nouveau message de contact PSSR';
    $body_admin = "Nouveau message reçu :\n\nPrénom & Nom : $nom\nEmail : $email\nTéléphone : $tel\n\nMessage :\n$msg\n";
    $headers_admin = array('Content-Type: text/plain; charset=UTF-8','Reply-To: ' . $nom . ' <' . $email . '>');
    wp_mail( PSSR_ADMIN_EMAIL, $subject_admin, $body_admin, $headers_admin );

    // Mail utilisateur
    $subject_user = 'Votre demande a bien été reçue — PSSR';
    $body_user = "Bonjour $nom,\n\nMerci pour votre message. Nous vous répondrons au plus vite.\n\nÉquilibre Vital";
    $headers_user = array('Content-Type: text/plain; charset=UTF-8','Reply-To: Équilibre Vital <' . PSSR_ADMIN_EMAIL . '>','From: Équilibre Vital <' . PSSR_ADMIN_EMAIL . '>');
    wp_mail( $email, $subject_user, $body_user, $headers_user );

    pssr_redirect( 'https://pssr.be/merci/', array('status'=>'ok','type'=>'contact') );
}
