<?php
/**
 * Plugin Name: PSSR — Connexion Référent
 * Description: Ajoute une page /connexion-referent/ (shortcode [pssr_login]) et redirige les référents vers le dashboard PSSR après login.
 * Version: 1.3
 * Author: PSSR
 */

if (!defined('ABSPATH')) exit;

/**
 * Shortcode [pssr_login]
 * Usage: créer une page et y mettre [pssr_login] (créée automatiquement à l’activation).
 * -> Inspiré du modèle fourni (titre, label, input, bouton, small) + image décor.
 */
add_shortcode('pssr_login', function($atts){
    $redirect_to = admin_url('admin.php?page=pssr-dashboard');

    $atts = shortcode_atts([
        'title'          => 'Espace référent',
        'placeholder'    => 'Votre e-mail ou identifiant',
        'placeholder_pw' => 'Votre mot de passe',
        'button_text'    => 'Se connecter',
    ], $atts, 'pssr_login');

    // Si déjà connecté ET "référent" (peut éditer des pages mais pas admin) -> va au dashboard PSSR
    if (is_user_logged_in() && current_user_can('edit_pages') && !current_user_can('manage_options')) {
        wp_safe_redirect($redirect_to);
        exit;
    }

    // URLs utiles
    $form_action = esc_url( wp_login_url($redirect_to) );
    $lost_url    = esc_url( wp_lostpassword_url($redirect_to) );

    // Image de décor (HTTPS pour éviter le mixed content)
    $bg = esc_url('https://pssr.be/wp-content/uploads/2025/09/ChatGPT-Image-16-sept.-2025-10_32_14.png');

    // Variables de texte
    $title       = esc_html($atts['title']);
    $ph_user     = esc_attr($atts['placeholder']);
    $ph_pass     = esc_attr($atts['placeholder_pw']);
    $btn_text    = esc_html($atts['button_text']);

    $html = <<<HTML
<style>
  /* Conteneur avec décor (image + voile), sans débordement */
  .pssr-ref {
    --ink:#0f0a22; --muted:#efe9ff;
    font-family: Inter, Manrope, system-ui, sans-serif;
  }
  .pssr-ref .wrap {
    width:100%; max-width: 960px; margin: 0 auto; box-sizing: border-box;
    padding: 18px; border-radius: 20px;
    background:
      radial-gradient(1100px 600px at -10% -10%, rgba(124,94,255,.10), transparent 70%),
      radial-gradient(1100px 600px at 110% 0%, rgba(227,129,255,.12), transparent 70%),
      linear-gradient(180deg, rgba(0,0,0,.45), rgba(0,0,0,.45)),
      url('{$bg}') center/cover no-repeat;
    box-shadow: 0 12px 30px rgba(15,10,34,.15);
  }

  /* Forme inspirée de ton modèle : titre, label, input, bouton, small */
  form.pssr-login {
    display: grid; gap: 12px;
    width: 100%; max-width: 520px; margin: 0 auto;
    background: rgba(255,255,255,.08);
    backdrop-filter: blur(1px);
  }

  .pssr-title {
    margin: 0 0 6px 0;
    color:#fff; font-weight:800;
    font-size: clamp(1.1rem, 1rem + .6vw, 1.35rem);
    text-shadow: 0 1px 2px rgba(0,0,0,.45);
    text-align:left;
  }

  .pssr-login label {
    color:#fff; font-weight:700;
    text-shadow: 0 1px 2px rgba(0,0,0,.45);
  }

  /* Champs : largeur confinée, jamais de dépassement (mobile inclus) */
  .pssr-login input[type="text"],
  .pssr-login input[type="password"] {
    width: 100%; max-width: 100%; box-sizing: border-box;
    padding: 10px 12px; border-radius: 12px;
    border: 1px solid rgba(255,255,255,.45);
    background: rgba(255,255,255,.92);
    font-size: 16px; color: #111;
  }

  /* Checkbox souvenir */
  .pssr-remember {
    display: flex; align-items: center; gap: 8px; color:#fff;
    text-shadow: 0 1px 2px rgba(0,0,0,.45);
    font-weight: 600;
  }

  /* Bouton large (cible tactile >=44px), style du modèle */
  .pssr-btn {
    display: inline-block; width: 100%;
    border: 0; border-radius: 999px;
    padding: 12px 18px; min-height: 44px;
    font-weight: 800; color: #fff; cursor: pointer;
    background: linear-gradient(90deg,#7b5cff,#a974ff 55%,#e381ff);
    box-shadow: 0 8px 24px rgba(15,10,34,.18);
    text-align: center;
  }
  .pssr-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 30px rgba(15,10,34,.22); }

  /* Note d’aide */
  .pssr-help {
    color: var(--muted);
    text-shadow: 0 1px 2px rgba(0,0,0,.45);
  }
  .pssr-help a { color: #fff; text-decoration: underline; }

  /* Micro-ajustements pour très petits écrans */
  @media (max-width: 420px){
    .pssr-ref .wrap { border-radius: 16px; padding: 14px; }
    .pssr-login input[type="text"],
    .pssr-login input[type="password"] { padding: 9px 10px; font-size: 15px; }
  }
</style>

<div class="pssr-ref">
  <div class="wrap">
    <form method="post" action="{$form_action}" class="pssr-login" novalidate>
      <h3 class="pssr-title">{$title}</h3>

      <label for="pssr_user">Nom d’utilisateur ou e-mail</label>
      <input id="pssr_user" name="log" type="text" placeholder="{$ph_user}" required />

      <label for="pssr_pass">Mot de passe</label>
      <input id="pssr_pass" name="pwd" type="password" placeholder="{$ph_pass}" required />

      <label class="pssr-remember">
        <input type="checkbox" name="rememberme" value="forever" /> Se souvenir de moi
      </label>

      <input type="hidden" name="redirect_to" value="{$redirect_to}" />

      <button type="submit" class="pssr-btn">{$btn_text}</button>

      <small class="pssr-help">Mot de passe oublié ? <a href="{$lost_url}">Réinitialiser</a></small>
    </form>
  </div>
</div>
HTML;

    return $html;
});

/** Crée automatiquement la page /connexion-referent/ à l’activation */
register_activation_hook(__FILE__, function(){
    $slug = 'connexion-referent';
    if (!get_page_by_path($slug)) {
        wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Connexion Référent',
            'post_name'    => $slug,
            'post_content' => '[pssr_login]'
        ]);
        flush_rewrite_rules(false);
    }
});

/**
 * Après login, si l’utilisateur est “référent” (peut éditer des pages mais n’est pas admin),
 * on l’envoie vers le dashboard PSSR.
 */
add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user){
    if (is_wp_error($user)) return $redirect_to;
    if ($user instanceof WP_User && user_can($user,'edit_pages') && !user_can($user,'manage_options')) {
        return admin_url('admin.php?page=pssr-dashboard');
    }
    return $redirect_to;
}, 10, 3);
