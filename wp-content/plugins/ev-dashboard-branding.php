<?php
/**
 * Plugin Name: EV Dashboard Branding
 * Description: Fond d’écran + styles admin cohérents avec l’identité Équilibre Vital.
 * Version: 1.0
 * Author: Équilibre Vital
 */

if (!defined('ABSPATH')) exit;

// <<< remplace l’URL ici si besoin >>>
define('EV_DASH_BG_URL', 'http://pssr.be/wp-content/uploads/2025/09/ChatGPT-Image-15-sept.-2025-22_18_40.png');

add_action('admin_enqueue_scripts', function () {
  wp_add_inline_style('wp-admin', ev_dashboard_css());
});

add_action('login_enqueue_scripts', function () {
  wp_add_inline_style('login', ev_dashboard_css(true));
});

function ev_dashboard_css($is_login = false) {
  $bg = esc_url(EV_DASH_BG_URL);
  return "
  :root{
    --ev-primary:#7a5cff;        /* violet/bleu */
    --ev-secondary:#c24dd6;      /* magenta */
    --ev-accent:#53b7ff;         /* bleu clair */
    --ev-ink:#eef0f6;            /* texte clair */
    --ev-panel: rgba(10,14,35,.55); /* voile lisibilité */
    --ev-border: rgba(255,255,255,.14);
    --ev-ring: rgba(120,140,255,.6);
  }

  /* ===== Fond d’écran admin / login ===== */
  body".($is_login ? "" : ".wp-admin")."{
    background: #0f1c48 url('{$bg}') center center / cover fixed no-repeat !important;
  }

  /* un léger blur pour améliorer le contraste du contenu */
  ".($is_login ? "#login" : "#wpbody-content, .wrap")."{
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
  }

  /* ===== Cartes / metaboxes ===== */
  .postbox, .metabox-holder .stuffbox, .wrap .notice, .wrap .card, .update-nag,
  .dashboard-widget, .widefat, .wp-core-ui .attachment, .theme-browser .theme,
  .wrap .welcome-panel{
    background: var(--ev-panel) !important;
    color: var(--ev-ink) !important;
    border: 1px solid var(--ev-border) !important;
    border-radius: 14px !important;
    box-shadow: 0 8px 24px rgba(0,0,0,.25) !important;
  }
  .postbox h2, .wrap h1, .wrap h2, .wrap h3, .wrap .welcome-panel h2{
    color: var(--ev-ink) !important;
    letter-spacing:.2px;
  }

  /* ===== Boutons ===== */
  .wp-core-ui .button-primary{
    background-image: linear-gradient(90deg,var(--ev-secondary),var(--ev-primary)) !important;
    border: none !important;
    color:#fff !important;
    box-shadow: 0 6px 16px rgba(122,92,255,.45);
  }
  .wp-core-ui .button-primary:hover{ filter: brightness(1.05) saturate(1.05); }
  .wp-core-ui .button, .wp-core-ui .button-secondary{
    background: rgba(255,255,255,.08);
    border: 1px solid var(--ev-border);
    color: var(--ev-ink);
  }
  .wp-core-ui .button:focus, .wp-core-ui .button-primary:focus{
    outline: 2px solid var(--ev-ring); outline-offset: 2px;
  }

  /* ===== Champs ===== */
  input[type=text], input[type=email], input[type=url], input[type=number],
  input[type=password], select, textarea{
    background: rgba(255,255,255,.06) !important;
    color: var(--ev-ink) !important;
    border:1px solid var(--ev-border) !important;
    border-radius:10px !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.05);
  }
  input:focus, select:focus, textarea:focus{
    border-color: var(--ev-ring) !important;
    box-shadow: 0 0 0 3px rgba(120,140,255,.25) !important;
    outline: none !important;
  }

  /* ===== Tableaux ===== */
  .widefat thead th{ color: var(--ev-ink); border-bottom:1px solid var(--ev-border); }
  .widefat tbody tr:nth-child(odd){ background-color: rgba(255,255,255,.03); }
  .widefat tbody tr:hover{ background-color: rgba(255,255,255,.06); }

  /* ===== Admin bar & menu ===== */
  #wpadminbar{
    background: linear-gradient(90deg,var(--ev-primary),var(--ev-secondary)) !important;
  }
  #adminmenu, #adminmenu .wp-submenu{
    background: rgba(10,14,35,.75);
    border-right: 1px solid var(--ev-border);
    backdrop-filter: blur(6px);
  }
  #adminmenu a{ color: var(--ev-ink); }
  #adminmenu li.menu-top:hover, #adminmenu li.wp-has-current-submenu > a.menu-top,
  #adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head{
    background: rgba(255,255,255,.08);
  }
  #adminmenu .awaiting-mod, #adminmenu .update-plugins{ background: var(--ev-secondary); }

  /* ===== Login: panneau centré ===== */
  ".($is_login ? "
  body.login div#login{
    padding: 32px;
    background: var(--ev-panel);
    border:1px solid var(--ev-border);
    border-radius:16px;
    box-shadow: 0 10px 30px rgba(0,0,0,.35);
  }
  body.login #backtoblog a, body.login #nav a{ color: var(--ev-ink); }
  " : "")."
  ";
}
