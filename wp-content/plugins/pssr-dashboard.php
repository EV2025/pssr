<?php
/**
 * Plugin Name: PSSR Dashboard
 * Description: Suivi PSSR — inscriptions, espace participant, tableau de bord référent, rappels automatiques, RGPD.
 * Version: 0.6.6
 * Author: Équipe PSSR
 */

if (!defined('ABSPATH')) exit;

final class PSSR_Dashboard {
    const INS_TABLE     = 'pssr_inscriptions';
    const MSG_TABLE     = 'pssr_messages';
    const STEP_TABLE    = 'pssr_steps';
    const REMIND_TABLE  = 'pssr_reminders';
    const GDPR_TABLE    = 'pssr_gdpr_requests';

    /** Image de fond (amélioration UI) */
    const BG_URL = 'https://palegoldenrod-chough-720869.hostingersite.com/wp-content/uploads/2025/09/ChatGPT-Image-16-sept.-2025-10_32_14.png';

    /** === BOOT === */
    public static function init(){
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);

        add_action('admin_init', [__CLASS__, 'ensure_schema']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);

        // Shortcodes front
        add_shortcode('pssr_participant', [__CLASS__, 'shortcode_participant']);
        add_shortcode('pssr_dashboard',   [__CLASS__, 'shortcode_dashboard_chooser']); // page de choix

        // Actions admin (postbacks)
        add_action('admin_post_pssr_update_status', [__CLASS__, 'handle_update_status']);
        add_action('admin_post_pssr_export_csv',   [__CLASS__, 'handle_export_csv']);
        add_action('admin_post_pssr_export_steps', [__CLASS__, 'handle_export_steps']);
        add_action('admin_post_pssr_send_message', [__CLASS__, 'handle_send_message']);
        add_action('admin_post_pssr_update_step',  [__CLASS__, 'handle_update_step']);

        // RGPD actions (admin + public)
        add_action('admin_post_pssr_gdpr_request',       [__CLASS__, 'handle_gdpr_request']);
        add_action('admin_post_nopriv_pssr_gdpr_request',[__CLASS__, 'handle_gdpr_request']);
        add_action('admin_post_pssr_gdpr_process',       [__CLASS__, 'handle_gdpr_process']);
        add_action('admin_post_pssr_gdpr_delete',        [__CLASS__, 'handle_gdpr_delete']);

        // Cron quotidien (rappels)
        add_action('pssr_daily_cron', [__CLASS__, 'cron_reminders']);

        // 👉 Nouveaux ajouts (front) : CSS responsive + nettoyage textes
        add_action('wp_enqueue_scripts', [__CLASS__, 'front_assets']);
        add_filter('the_content', [__CLASS__, 'cleanup_public_texts'], 12);
    }

    /** === ACTIVATION === */
    public static function activate(){
        self::create_tables();
        self::maybe_create_pages(); // crée les pages [pssr_dashboard] et [pssr_participant]

        if (!wp_next_scheduled('pssr_daily_cron')) {
            $tz = wp_timezone(); $run = new DateTime('tomorrow 08:00', $tz);
            wp_schedule_event($run->getTimestamp(), 'daily', 'pssr_daily_cron');
        }
    }

    /** === DÉSACTIVATION === */
    public static function deactivate(){
        $ts = wp_next_scheduled('pssr_daily_cron');
        if ($ts) wp_unschedule_event($ts, 'pssr_daily_cron');
    }

    /** === SCHÉMA À CHAQUE CHARGEMENT ADMIN === */
    public static function ensure_schema(){ self::create_tables(); }

    /** === TABLES === */
    private static function create_tables(){
        global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $ins = $wpdb->prefix . self::INS_TABLE;
        $msg = $wpdb->prefix . self::MSG_TABLE;
        $stp = $wpdb->prefix . self::STEP_TABLE;
        $rem = $wpdb->prefix . self::REMIND_TABLE;
        $gdpr= $wpdb->prefix . self::GDPR_TABLE;

        $sql1 = "CREATE TABLE $ins (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reservation_id VARCHAR(20) NOT NULL UNIQUE,
            prenom_nom VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            tel VARCHAR(50) NULL,
            ref_sociale VARCHAR(150) NULL,
            session VARCHAR(50) NULL,
            statut ENUM('Inscrit','En cours','Terminé','Abandonné') DEFAULT 'Inscrit',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        $sql2 = "CREATE TABLE $msg (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reservation_id VARCHAR(20) NOT NULL,
            author_id BIGINT UNSIGNED NULL,
            content TEXT NOT NULL,
            attachment_url TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY res_id (reservation_id)
        ) $charset;";

        $sql3 = "CREATE TABLE $stp (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reservation_id VARCHAR(20) NOT NULL,
            step_key VARCHAR(8) NOT NULL,
            planned_date DATE NULL,
            done_date DATE NULL,
            note TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_step (reservation_id, step_key),
            KEY res_id (reservation_id)
        ) $charset;";

        $sql4 = "CREATE TABLE $rem (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reservation_id VARCHAR(20) NOT NULL,
            step_key VARCHAR(8) NOT NULL,
            remind_type ENUM('D-7','D-1','D0','D+2') NOT NULL,
            ref_date DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_rem (reservation_id, step_key, remind_type, ref_date),
            KEY res_id (reservation_id)
        ) $charset;";

        $sql5 = "CREATE TABLE $gdpr (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reservation_id VARCHAR(20) NOT NULL,
            email VARCHAR(150) NOT NULL,
            reason TEXT NULL,
            status ENUM('pending','processed') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY res_id (reservation_id),
            KEY status (status)
        ) $charset;";

        dbDelta($sql1); dbDelta($sql2); dbDelta($sql3); dbDelta($sql4); dbDelta($sql5);
    }

    /** === PAGES CRÉÉES À L’ACTIVATION === */
    private static function maybe_create_pages(){
        // 1) Mon parcours
        if (!self::get_page_id_by_path('mon-parcours')) {
            wp_insert_post([
                'post_type'   => 'page',
                'post_status' => 'publish',
                'post_title'  => 'Mon parcours',
                'post_name'   => 'mon-parcours',
                'post_content'=> '[pssr_participant]'
            ]);
        }
        // 2) Tableau de bord PSSR (choix)
        if (!self::get_page_id_by_path('tableau-de-bord-pssr')) {
            wp_insert_post([
                'post_type'   => 'page',
                'post_status' => 'publish',
                'post_title'  => 'Tableau de bord PSSR',
                'post_name'   => 'tableau-de-bord-pssr',
                'post_content'=> '[pssr_dashboard]'
            ]);
        }
        flush_rewrite_rules(false);
    }

    private static function get_page_id_by_path($path){
        $p = get_page_by_path($path);
        return $p ? $p->ID : 0;
    }

    /** Étapes du parcours */
    private static function steps_def(){
        return [
            'CAND' => ['label'=>"Candidature — dépôt & éligibilité", 'desc'=>"Présenter le dispositif, recueillir le consentement, vérifier critères et planifier l’entrée."],
            'ARF'  => ['label'=>"Ateliers de Remise en Forme", 'desc'=>"Sensibiliser, informer, mise en mouvement. Présentation du programme, éveil, notions santé/mouvement."],
            'BSS'  => ['label'=>"Bilan Socio-Sportif", 'desc'=>"État des lieux : santé, niveau d’activité, freins & motivations, tests adaptés, plan individuel."],
            'PDS'  => ['label'=>"Parcours Découverte Sportive", 'desc'=>"Essais avec partenaires : découverte de disciplines, retours d’expérience et coordination."],
            'APA'  => ['label'=>"Activité Physique Adaptée", 'desc'=>"Pratique régulière et progressive, adaptation des séances, suivi et valorisation des progrès."],
            'CPE'  => ['label'=>"Concertation Partagée d’Engagement", 'desc'=>"Coordination sport/santé/social, relais dispositifs utiles, partage d’infos et actions conjointes."],
            'SRS'  => ['label'=>"Suivi Renforcé Solution", 'desc'=>"Maintenir une pratique durable, soutien à la persévérance, ajustements et opportunités transversales."]
        ];
    }

    /** === ASSETS FRONT (responsive + masquage des textes) === */
    public static function front_assets(){
        if (is_admin()) return;

        // On cible explicitement les pages publiques PSSR
        if (!is_page(array('tableau-de-bord-pssr','mon-parcours'))) return;

        wp_register_style('pssr-front-inline', false);
        wp_enqueue_style('pssr-front-inline');

        $css = <<<CSS
/* Masquer le titre de page injecté par le thème */
body.page-tableau-de-bord-pssr .entry-title,
body.page-tableau-de-bord-pssr .wp-block-post-title,
body.page-tableau-de-bord-pssr .page-title,
body.page-mon-parcours .entry-title,
body.page-mon-parcours .wp-block-post-title,
body.page-mon-parcours .page-title{ display:none !important; }

/* Grille responsive pour la page de choix */
.pssr-chooser .grid{ display:grid; gap:14px; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); }
@media (max-width:768px){ .pssr-chooser .grid{ grid-template-columns:1fr; } }
.pssr-chooser .card{ background:#fff; border:1px solid rgba(15,10,34,.16); border-radius:16px; padding:16px; box-shadow:0 10px 26px rgba(15,10,34,.10); }
.pssr-chooser a.btn{ display:block; width:100%; text-align:center; border-radius:999px; padding:12px 18px; font-weight:800; color:#fff; text-decoration:none; background:linear-gradient(90deg,#7b5cff,#a974ff 55%,#e381ff); box-shadow:0 8px 24px rgba(15,10,34,.18); }
.pssr-chooser a.btn:hover{ transform:translateY(-2px); box-shadow:0 12px 30px rgba(15,10,34,.22); }
@media (min-width:768px){ .pssr-chooser a.btn{ width:auto; } }
CSS;

        wp_add_inline_style('pssr-front-inline', $css);
    }

    /** === Nettoyage du contenu public : supprime les phrases demandées === */
    public static function cleanup_public_texts($content){
        if (!is_page(array('tableau-de-bord-pssr','mon-parcours'))) return $content;

        $phrases = array(
            'Tableau de bord PSSR',
            'Choisissez votre espace : Participant ou Référent.',
            'Gérez les participants, mettez à jour les statuts, envoyez des messages et exportez les données.',
            'Accès réservé aux comptes Administrateur ou Éditeur.',
            'Mon parcours'
        );

        foreach ($phrases as $p) {
            // brut
            $content = str_replace($p, '', $content);
            // titres exacts
            $content = preg_replace('#<h[1-6][^>]*>\s*'.preg_quote($p,'#').'\s*</h[1-6]>#i', '', $content);
            // paragraphes exacts
            $content = preg_replace('#<p[^>]*>\s*'.preg_quote($p,'#').'\s*</p>#i', '', $content);
        }
        return $content;
    }

    /** === ADMIN ASSETS (lisibilité & fond) === */
    public static function admin_assets($hook){
        if (!in_array($hook, ['toplevel_page_pssr-dashboard', 'pssr-dashboard_page_pssr-gdpr'], true)) return;

        // Polices + feuille de style thème
        wp_enqueue_style('pssr-admin-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Manrope:wght@400;600;800&display=swap', [], null);
        wp_register_style('pssr-admin-theme', false, [], null);
        wp_enqueue_style('pssr-admin-theme');

        $bg = esc_url(self::BG_URL);

        // Thème admin
        $css = <<<CSS
:root{
  --ink:#1f123d; --ink-strong:#0f0a22; --muted:#4b3e77;
  --glass:rgba(255,255,255,.96); --line:rgba(31,18,61,.15);
  --l300:#e6d5ff; --l500:#cfa9ff;
  --card-shadow:0 10px 26px rgba(15,10,34,.10);
  --cta-grad:linear-gradient(90deg,#7b5cff,#a974ff 55%,#e381ff);
  --cta-grad-strong:linear-gradient(90deg,#6c4cf8,#9c66ff 55%,#d86dff);
}
body.wp-admin.toplevel_page_pssr-dashboard,
body.wp-admin.pssr-dashboard_page_pssr-gdpr{
  font-family:'Inter','Manrope',system-ui,sans-serif;
  color:var(--ink);
  font-size:15px; line-height:1.5;
  background:
    radial-gradient(1200px 600px at 10% -10%, rgba(124,94,255,.10), transparent 70%),
    radial-gradient(1200px 600px at 110% 10%, rgba(227,129,255,.12), transparent 70%);
}
body.wp-admin .wrap > h1{
  display:inline-block; margin:10px 0 16px; padding:14px 18px;
  border-radius:16px; background:linear-gradient(180deg,rgba(0,0,0,.35),rgba(0,0,0,.55)), url('{$bg}') center/cover no-repeat;
  color:#fff; font-weight:800; letter-spacing:.2px; text-shadow:0 2px 6px rgba(0,0,0,.45);
  box-shadow:0 8px 24px rgba(0,0,0,.18);
}
.pssr-admin-card{
  background:#fff; border:1px solid var(--line); border-radius:16px;
  box-shadow:var(--card-shadow); padding:18px;
}
.pssr-admin-filters input[type="search"],
.pssr-admin-filters select{
  border:1px solid var(--line); border-radius:12px; padding:10px 12px; font-size:15px; background:#fff;
}
.pssr-admin-filters label{ font-weight:600; color:var(--ink-strong) }
.pssr-admin-filters .button-primary{
  background-image:var(--cta-grad); color:#fff; border:none; border-radius:999px;
  font-weight:800; box-shadow:0 10px 24px rgba(15,10,34,.18); padding:.7em 1.15em; font-size:15px;
}
.pssr-admin-filters .button-primary:hover{ background-image:var(--cta-grad-strong); transform:translateY(-1px) }
.pssr-admin-filters .button{ border-radius:999px }
.dataTables_wrapper .dataTables_filter input{
  border:1px solid var(--line)!important; border-radius:999px!important; padding:.6em 1em!important; font-size:15px!important;
}
table.dataTable thead th{
  background:linear-gradient(180deg,#fff,#f5f1ff)!important;
  color:#0f0a22!important; border-bottom:1px solid var(--line)!important; font-weight:700!important;
}
table.dataTable tbody td{ color:#231a44!important }
table.dataTable tbody tr:hover{ background:#f8f4ff!important }
.dataTables_wrapper .dataTables_paginate .paginate_button.current{
  background:var(--cta-grad)!important; color:#fff!important; border:none!important; border-radius:999px!important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button{ border-radius:999px!important }
.wp-core-ui .button-primary{ background-image:var(--cta-grad); color:#fff; border:none; border-radius:999px; font-weight:800; box-shadow:0 10px 24px rgba(15,10,34,.18); }
.wp-core-ui .button-primary:hover{ background-image:var(--cta-grad-strong); transform:translateY(-1px) }
.wp-core-ui .button{ border-radius:999px }
.wp-core-ui .button.button-danger{ background:#d32f2f; color:#fff; border:none; box-shadow:0 10px 24px rgba(211,47,47,.18); }
.wp-core-ui .button.button-danger:hover{ background:#c62828 }
details{ background:#fff; border:1px solid var(--line); border-radius:12px; padding:8px 10px; }
details + details{ margin-top:8px }
details[open]{ box-shadow:var(--card-shadow) }
details summary{ cursor:pointer; font-weight:700; color:var(--ink-strong) }
table.widefat select, table.widefat textarea{ border:1px solid var(--line); border-radius:10px; font-size:14px; }
table.widefat select:focus, table.widefat textarea:focus{ outline:none; box-shadow:0 0 0 3px rgba(123,92,255,.25); border-color:#9c83ff; }
.reveal{ opacity:0; transform:translateY(12px); transition:opacity .4s ease, transform .4s ease; will-change:opacity,transform }
.reveal.in{ opacity:1; transform:none }
@media (prefers-reduced-motion:reduce){ .reveal{ opacity:1; transform:none; transition:none } }
CSS;
        wp_add_inline_style('pssr-admin-theme', $css);

        // DataTables
        wp_enqueue_style('pssr-datatables', 'https://cdn.datatables.net/v/dt/dt-1.13.8/r-2.5.0/datatables.min.css', [], '1.13.8');
        wp_enqueue_script('pssr-datatables', 'https://cdn.datatables.net/v/dt/dt-1.13.8/r-2.5.0/datatables.min.js', ['jquery'], '1.13.8', true);

        // Media Library
        wp_enqueue_media();

        // Init DataTables + Media + Reveal
        $init = <<<JS
jQuery(function($){
  var t = $('#pssr-table, #pssr-gdpr-table');
  if (t.length) {
    t.DataTable({
      responsive: true,
      pageLength: 25,
      order: [[6,'desc']],
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/fr-FR.json' }
    });
  }
  $(document).on('click', '.pssr-media-btn', function(e){
    e.preventDefault();
    var btn = $(this);
    var frame = wp.media({ title: 'Sélectionner un document', button: { text: 'Utiliser ce document' }, multiple: false });
    frame.on('select', function(){
      var url = frame.state().get('selection').first().toJSON().url || '';
      btn.closest('form').find('input[name="attachment_url"]').val(url).trigger('change');
    });
    frame.open();
  });

  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var els = $('.wrap, .pssr-admin-card, .dataTables_wrapper');
    els.addClass('reveal');
    var io = new IntersectionObserver(function(entries, obs){
      entries.forEach(function(e){
        if (e.isIntersecting){ e.target.classList.add('in'); obs.unobserve(e.target); }
      });
    }, { rootMargin: '0px 0px -10%', threshold: 0.12 });
    els.each(function(){ io.observe(this); });
  }
});
JS;
        wp_add_inline_script('pssr-datatables', $init);
    }

    /** === ADMIN MENU === */
    public static function admin_menu(){
        add_menu_page('PSSR Dashboard', 'PSSR', 'edit_pages', 'pssr-dashboard', [__CLASS__, 'admin_page'], 'dashicons-groups', 26);
        add_submenu_page('pssr-dashboard', 'PSSR — RGPD', 'RGPD', 'edit_pages', 'pssr-gdpr', [__CLASS__, 'admin_gdpr_page']);
    }

    /** === ADMIN — LISTE PARTICIPANTS === */
    public static function admin_page(){
        if (!current_user_can('edit_pages')) wp_die(__('Accès refusé', 'pssr'));
        global $wpdb;
        $ins = $wpdb->prefix . self::INS_TABLE;

        $status  = isset($_GET['status'])  ? sanitize_text_field($_GET['status'])  : '';
        $session = isset($_GET['session']) ? sanitize_text_field($_GET['session']) : '';
        $q       = isset($_GET['q'])       ? sanitize_text_field($_GET['q'])       : '';

        $where  = 'WHERE 1=1'; $params = [];
        if ($status !== '')  { $where .= ' AND statut=%s';  $params[] = $status; }
        if ($session !== '') { $where .= ' AND session=%s'; $params[] = $session; }
        if ($q) { $like = '%'.$wpdb->esc_like($q).'%'; $where .= ' AND (prenom_nom LIKE %s OR email LIKE %s OR reservation_id LIKE %s)'; array_push($params, $like, $like, $like); }

        $sql  = $wpdb->prepare("SELECT * FROM $ins $where ORDER BY created_at DESC", $params);
        $rows = $wpdb->get_results($sql);

        $sessions = $wpdb->get_col("SELECT DISTINCT session FROM $ins WHERE session IS NOT NULL AND session<>'' ORDER BY session");

        $export_url  = wp_nonce_url(admin_url('admin-post.php?action=pssr_export_csv'),   'pssr_export');
        $export2_url = wp_nonce_url(admin_url('admin-post.php?action=pssr_export_steps'),'pssr_export_steps');
        ?>
        <div class="wrap">
          <h1>PSSR — Tableau de bord</h1>

          <div class="pssr-admin-card pssr-admin-filters">
            <form method="get" style="margin:0;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
              <input type="hidden" name="page" value="pssr-dashboard"/>
              <label style="display:flex;gap:6px;align-items:center;">
                <span>Recherche</span>
                <input type="search" name="q" placeholder="Nom, email, ID…" value="<?php echo esc_attr($q); ?>"/>
              </label>
              <label>Statut
                <select name="status">
                  <option value="">Tous</option>
                  <?php foreach(['Inscrit','En cours','Terminé','Abandonné'] as $s): ?>
                    <option value="<?php echo esc_attr($s); ?>" <?php selected($status,$s); ?>><?php echo esc_html($s); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Session
                <select name="session">
                  <option value="">Toutes</option>
                  <?php if ($sessions): foreach($sessions as $sess): ?>
                    <option value="<?php echo esc_attr($sess); ?>" <?php selected($session,$sess); ?>><?php echo esc_html($sess); ?></option>
                  <?php endforeach; endif; ?>
                </select>
              </label>
              <button class="button button-primary">Filtrer</button>
              <a class="button button-secondary" href="<?php echo esc_url($export_url); ?>">Export inscriptions CSV</a>
              <a class="button" href="<?php echo esc_url($export2_url); ?>">Export étapes CSV</a>
            </form>
          </div>

          <div class="pssr-admin-card" style="margin-top:12px;">
            <table id="pssr-table" class="widefat fixed striped">
              <thead>
                <tr>
                  <th>ID réserv.</th><th>Nom</th><th>Email</th><th>Tél</th><th>Session</th><th>Statut</th><th>Créé</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach($rows as $r): ?>
                <tr>
                  <td><code><?php echo esc_html($r->reservation_id); ?></code></td>
                  <td><?php echo esc_html($r->prenom_nom); ?></td>
                  <td><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a></td>
                  <td><?php echo esc_html($r->tel); ?></td>
                  <td><?php echo esc_html($r->session); ?></td>
                  <td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-flex;gap:6px;flex-wrap:wrap;">
                      <input type="hidden" name="action" value="pssr_update_status"/>
                      <?php wp_nonce_field('pssr_update_status'); ?>
                      <input type="hidden" name="reservation_id" value="<?php echo esc_attr($r->reservation_id); ?>"/>
                      <select name="statut">
                        <?php foreach(['Inscrit','En cours','Terminé','Abandonné'] as $s): ?>
                          <option value="<?php echo esc_attr($s); ?>" <?php selected($r->statut,$s); ?>><?php echo esc_html($s); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="button button-primary">OK</button>
                    </form>
                  </td>
                  <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($r->created_at))); ?></td>
                  <td>
                    <details>
                      <summary>Message</summary>
                      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px;display:grid;gap:6px;">
                        <input type="hidden" name="action" value="pssr_send_message"/>
                        <?php wp_nonce_field('pssr_send_message'); ?>
                        <input type="hidden" name="reservation_id" value="<?php echo esc_attr($r->reservation_id); ?>"/>
                        <textarea name="content" rows="3" placeholder="Écrire un message au participant" required></textarea>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                          <input type="url" name="attachment_url" placeholder="Lien d'un document (optionnel)" style="min-width:280px;"/>
                          <button class="button pssr-media-btn" type="button">Choisir un document</button>
                        </div>
                        <label><input type="checkbox" name="notify" value="1" checked/> Notifier par e-mail</label>
                        <button class="button">Envoyer</button>
                      </form>
                    </details>

                    <details style="margin-top:6px;">
                      <summary>Étapes</summary>
                      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px;display:grid;gap:8px;">
                        <input type="hidden" name="action" value="pssr_update_step"/>
                        <?php wp_nonce_field('pssr_update_step'); ?>
                        <input type="hidden" name="reservation_id" value="<?php echo esc_attr($r->reservation_id); ?>"/>
                        <label>Étape
                          <select name="step_key" required>
                            <?php foreach (array_keys(self::steps_def()) as $k): ?>
                              <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($k); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </label>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                          <label>Date prévue <input type="date" name="planned_date"/></label>
                          <label>Date réalisée <input type="date" name="done_date"/></label>
                        </div>
                        <label>Note (privée référent)
                          <textarea name="note" rows="2" placeholder="Note interne pour le suivi (facultatif)"></textarea>
                        </label>
                        <label><input type="checkbox" name="notify" value="1"/> Notifier le participant par e-mail</label>
                        <button class="button button-primary">Enregistrer l’étape</button>
                      </form>
                    </details>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="8">Aucune inscription pour le moment.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php
    }

    /** === ADMIN — PAGE RGPD === */
    public static function admin_gdpr_page(){
        if (!current_user_can('edit_pages')) wp_die(__('Accès refusé', 'pssr'));
        global $wpdb;
        $gdpr = $wpdb->prefix . self::GDPR_TABLE;
        $rows = $wpdb->get_results("SELECT * FROM $gdpr ORDER BY created_at DESC");
        ?>
        <div class="wrap">
          <h1>PSSR — RGPD (Demandes de suppression)</h1>

          <div class="pssr-admin-card" style="margin-bottom:12px;">
            <p style="font-size:15px;color:#231a44">Liste des demandes soumises par les participants (droit à l’oubli). Vous pouvez marquer une demande comme <strong>traitée</strong> ou <strong>supprimer toutes les données</strong> liées à l’identifiant.</p>
          </div>

          <div class="pssr-admin-card">
            <table id="pssr-gdpr-table" class="widefat fixed striped">
              <thead>
                <tr>
                  <th>ID réserv.</th><th>Email</th><th>Motif</th><th>Statut</th><th>Créé</th><th>Traité le</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach($rows as $r): ?>
                <tr>
                  <td><code><?php echo esc_html($r->reservation_id); ?></code></td>
                  <td><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a></td>
                  <td><?php echo nl2br(esc_html($r->reason)); ?></td>
                  <td><?php echo esc_html($r->status); ?></td>
                  <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($r->created_at))); ?></td>
                  <td><?php echo $r->processed_at ? esc_html(date_i18n('Y-m-d H:i', strtotime($r->processed_at))) : '—'; ?></td>
                  <td style="display:flex;gap:6px;flex-wrap:wrap;">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                      <input type="hidden" name="action" value="pssr_gdpr_process"/>
                      <?php wp_nonce_field('pssr_gdpr_process'); ?>
                      <input type="hidden" name="id" value="<?php echo esc_attr($r->id); ?>"/>
                      <button class="button">Marquer traité</button>
                    </form>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('⚠️ Confirmer la suppression TOTALE des données liées à cet ID ? Action irréversible.');">
                      <input type="hidden" name="action" value="pssr_gdpr_delete"/>
                      <?php wp_nonce_field('pssr_gdpr_delete'); ?>
                      <input type="hidden" name="reservation_id" value="<?php echo esc_attr($r->reservation_id); ?>"/>
                      <button class="button button-danger">Supprimer toutes les données</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7">Aucune demande pour le moment.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php
    }

    /** === ACTIONS ADMIN === */

    public static function handle_update_status(){
        if (!current_user_can('edit_pages')) wp_die('Accès refusé');
        check_admin_referer('pssr_update_status');
        $rid = isset($_POST['reservation_id']) ? sanitize_text_field($_POST['reservation_id']) : '';
        $statut = isset($_POST['statut']) ? sanitize_text_field($_POST['statut']) : '';
        if (!$rid || !in_array($statut, ['Inscrit','En cours','Terminé','Abandonné'], true)){
            wp_redirect(admin_url('admin.php?page=pssr-dashboard')); exit;
        }
        global $wpdb; $ins = $wpdb->prefix . self::INS_TABLE;
        $wpdb->update($ins, ['statut'=>$statut], ['reservation_id'=>$rid], ['%s'], ['%s']);
        wp_redirect(admin_url('admin.php?page=pssr-dashboard')); exit;
    }

    public static function handle_export_csv(){
        if (!current_user_can('edit_pages')) wp_die('Accès refusé');
        check_admin_referer('pssr_export');
        global $wpdb; $ins = $wpdb->prefix . self::INS_TABLE;
        $rows = $wpdb->get_results("SELECT reservation_id, prenom_nom, email, tel, ref_sociale, session, statut, created_at FROM $ins ORDER BY created_at DESC", ARRAY_A);
        nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=pssr_inscriptions.csv');
        $out = fopen('php://output', 'w'); fputcsv($out, ['reservation_id','prenom_nom','email','tel','ref_sociale','session','statut','created_at']);
        if ($rows) foreach($rows as $r){ fputcsv($out, $r); } fclose($out); exit;
    }

    public static function handle_export_steps(){
        if (!current_user_can('edit_pages')) wp_die('Accès refusé');
        check_admin_referer('pssr_export_steps');
        global $wpdb; $stp = $wpdb->prefix . self::STEP_TABLE;
        $rows = $wpdb->get_results("SELECT reservation_id, step_key, planned_date, done_date, note, updated_at FROM $stp ORDER BY reservation_id, step_key", ARRAY_A);
        nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=pssr_etapes.csv');
        $out = fopen('php://output', 'w'); fputcsv($out, ['reservation_id','step_key','planned_date','done_date','note','updated_at']);
        if ($rows) foreach($rows as $r){ fputcsv($out, $r); } fclose($out); exit;
    }

    public static function handle_send_message(){
        if (!current_user_can('edit_pages')) wp_die('Accès refusé');
        check_admin_referer('pssr_send_message');

        $rid       = isset($_POST['reservation_id']) ? sanitize_text_field($_POST['reservation_id']) : '';
        $content   = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $attachment= isset($_POST['attachment_url']) ? esc_url_raw($_POST['attachment_url']) : '';
        $notify    = !empty($_POST['notify']);

        if (!$rid || !$content){
            wp_redirect(admin_url('admin.php?page=pssr-dashboard')); exit;
        }

        global $wpdb; 
        $msg = $wpdb->prefix . self::MSG_TABLE; 
        $ins = $wpdb->prefix . self::INS_TABLE;

        // Enregistrer le message
        $wpdb->insert($msg, [
            'reservation_id' => $rid,
            'author_id'      => get_current_user_id(),
            'content'        => $content,
            'attachment_url' => $attachment,
            'created_at'     => current_time('mysql')
        ], ['%s','%d','%s','%s','%s']);

        // Notification e-mail simple au participant
        if ($notify){
            $row = $wpdb->get_row($wpdb->prepare("SELECT email, prenom_nom FROM $ins WHERE reservation_id=%s", $rid));
            if ($row && !empty($row->email)){
                $subject = 'PSSR — Nouveau message';
                $body = wpautop(
                    'Bonjour '.esc_html($row->prenom_nom).",\n\n".
                    "Vous avez reçu un nouveau message concernant votre parcours PSSR :\n\n".
                    wp_strip_all_tags($content)."\n\n".
                    ($attachment ? "Document joint : ".$attachment."\n\n" : "").
                    "Votre identifiant : ".$rid
                );
                wp_mail($row->email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
            }
        }

        wp_redirect(admin_url('admin.php?page=pssr-dashboard')); 
        exit;
    }

    public static function handle_update_step(){
        if (!current_user_can('edit_pages')) wp_die('Accès refusé');
        check_admin_referer('pssr_update_step');
        $rid   = isset($_POST['reservation_id']) ? sanitize_text_field($_POST['reservation_id']) : '';
        $step  = isset($_POST['step_key']) ? sanitize_text_field($_POST['step_key']) : '';
        $pdate = isset($_POST['planned_date']) && $_POST['planned_date'] !== '' ? sanitize_text_field($_POST['planned_date']) : null;
        $ddate = isset($_POST['done_date'])    && $_POST['done_date']    !== '' ? sanitize_text_field($_POST['done_date'])    : null;
        $note  = isset($_POST['note']) ? wp_kses_post($_POST['note']) : '';
        $notify= !empty($_POST['notify']);
        if (!$rid || !$step || !array_key_exists($step, self::steps_def())){ wp_redirect(admin_url('admin.php?page=pssr-dashboard')); exit; }

        global $wpdb; $stp = $wpdb->prefix . self::STEP_TABLE;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $stp WHERE reservation_id=%s AND step_key=%s", $rid, $step));
        $data = ['reservation_id'=>$rid,'step_key'=>$step,'planned_date'=>$pdate,'done_date'=>$ddate,'note'=>$note,'updated_at'=>current_time('mysql')];
        $fmt  = ['%s','%s','%s','%s','%s','%s'];
        if ($exists){ unset($data['reservation_id'],$data['step_key']); $wpdb->update($stp, $data, ['id'=>$exists], ['%s','%s','%s','%s'], ['%d']); }
        else{ $wpdb->insert($stp, $data, $fmt); }

        if ($notify){
            $ins = $wpdb->prefix . self::INS_TABLE;
            $row = $wpdb->get_row($wpdb->prepare("SELECT email, prenom_nom FROM $ins WHERE reservation_id=%s", $rid));
            if ($row && !empty($row->email)){
                $defs = self::steps_def(); $lbl = $defs[$step]['label'] ?? $step;
                $subject = 'PSSR — Mise à jour de votre parcours';
                $lines = [];
                if ($pdate) $lines[] = "• Date prévue : ".$pdate;
                if ($ddate) $lines[] = "• Date réalisée : ".$ddate;
                if ($note)  $lines[] = "• Note : ".wp_strip_all_tags($note);
                $body = wpautop('Bonjour '.esc_html($row->prenom_nom).",\n\n"."Votre étape « ".$lbl." » a été mise à jour.\n".implode("\n", $lines)."\n\n"."Votre identifiant : ".$rid);
                wp_mail($row->email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
            }
        }
        wp_redirect(admin_url('admin.php?page=pssr-dashboard')); exit;
    }

    /** 4. Notifications automatiques (cron) */
    public static function cron_reminders(){
        global $wpdb;
        $ins = $wpdb->prefix . self::INS_TABLE;
        $stp = $wpdb->prefix . self::STEP_TABLE;
        $rem = $wpdb->prefix . self::REMIND_TABLE;
        $msg = $wpdb->prefix . self::MSG_TABLE;

        $tz = wp_timezone(); $today = new DateTime('now', $tz);
        $dates = [
            'D-7' => (clone $today)->modify('+7 days')->format('Y-m-d'),
            'D-1' => (clone $today)->modify('+1 day')->format('Y-m-d'),
            'D0'  => $today->format('Y-m-d'),
            'D+2' => (clone $today)->modify('-2 days')->format('Y-m-d'),
        ];
        $defs = self::steps_def();

        foreach ($dates as $type => $refDate) {
            $where_done = ($type === 'D+2') ? "AND (s.done_date IS NULL OR s.done_date='0000-00-00')" : "";
            $sql = $wpdb->prepare("
                SELECT s.reservation_id, s.step_key, s.planned_date, s.done_date, i.prenom_nom, i.email
                FROM $stp s JOIN $ins i ON i.reservation_id = s.reservation_id
                WHERE s.planned_date = %s $where_done
            ", $refDate);
            $rows = $wpdb->get_results($sql);
            if (!$rows) continue;

            foreach ($rows as $r) {
                $label = $defs[$r->step_key]['label'] ?? $r->step_key;
                switch ($type) {
                    case 'D-7': $when = "dans 7 jours"; break;
                    case 'D-1': $when = "demain"; break;
                    case 'D0':  $when = "aujourd’hui"; break;
                    case 'D+2': $when = "relance"; break;
                    default:    $when = ""; break;
                }

                $subject = 'PSSR — Rappel '.$when;
                $lineMain = "Étape « ".$label." » — prévue le ".$r->planned_date.".";
                $extra = ($type === 'D+2') ? "\nMerci de nous informer si vous avez un empêchement." : "";

                $html = wpautop(
                    'Bonjour '.esc_html($r->prenom_nom).",\n\n".
                    $lineMain.$extra."\n\n".
                    "ID : ".$r->reservation_id
                );

                wp_mail($r->email, $subject, $html, ['Content-Type: text/html; charset=UTF-8']);

                $wpdb->insert($rem, [
                    'reservation_id'=>$r->reservation_id,'step_key'=>$r->step_key,'remind_type'=>$type,'ref_date'=>$refDate,'created_at'=>current_time('mysql')
                ], ['%s','%s','%s','%s','%s']);

                $wpdb->insert($msg, [
                    'reservation_id'=>$r->reservation_id,
                    'author_id'=>0,
                    'content'=>'[Rappel '.$type.'] '.$label.' — prévue : '.$r->planned_date,
                    'attachment_url'=>'',
                    'created_at'=>current_time('mysql')
                ], ['%s','%d','%s','%s','%s']);
            }
        }
    }

    /** === RGPD : création demande côté participant === */
    public static function handle_gdpr_request(){
        check_admin_referer('pssr_gdpr_request');
        $rid = isset($_POST['reservation_id']) ? sanitize_text_field($_POST['reservation_id']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $reason = isset($_POST['reason']) ? wp_kses_post($_POST['reason']) : '';
        if (!$rid || !$email){ wp_die('Requête invalide'); }

        global $wpdb; $gdpr = $wpdb->prefix . self::GDPR_TABLE;
        $already = $wpdb->get_var($wpdb->prepare("SELECT id FROM $gdpr WHERE reservation_id=%s AND status='pending'", $rid));
        if (!$already){
            $wpdb->insert($gdpr, [
                'reservation_id'=>$rid,
                'email'=>$email,
                'reason'=>$reason,
                'status'=>'pending',
                'created_at'=>current_time('mysql')
            ], ['%s','%s','%s','%s','%s']);
        }

        // Alerte email admin
        $subject = 'PSSR — Demande de suppression de données (RGPD)';
        $body = wpautop("Une nouvelle demande RGPD a été soumise.\n\nIdentifiant : $rid\nEmail : $email\n\nMotif :\n".wp_strip_all_tags($reason));
        foreach (self::admin_recipients() as $to) {
            wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
        }

        wp_safe_redirect(wp_get_referer() ?: home_url('/')); exit;
    }

    /** Marquer une demande comme traitée */
    public static function handle_gdpr_process(){
        if (!current_user_can('edit_pages')) wp_die('Accès refusé');
        check_admin_referer('pssr_gdpr_process');
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if (!$id){ wp_redirect(admin_url('admin.php?page=pssr-gdpr')); exit; }
        global $wpdb; $gdpr = $wpdb->prefix . self::GDPR_TABLE;
        $wpdb->update($gdpr, ['status'=>'processed','processed_at'=>current_time('mysql')], ['id'=>$id], ['%s','%s'], ['%d']);
        wp_redirect(admin_url('admin.php?page=pssr-gdpr')); exit;
    }

    /** ⚠️ Suppression totale des données d’un participant */
    public static function handle_gdpr_delete(){
        if (!current_user_can('edit_pages')) wp_die('Accès refusé');
        check_admin_referer('pssr_gdpr_delete');
        $rid = isset($_POST['reservation_id']) ? sanitize_text_field($_POST['reservation_id']) : '';
        if (!$rid){ wp_redirect(admin_url('admin.php?page=pssr-gdpr')); exit; }

        global $wpdb;
        $ins = $wpdb->prefix . self::INS_TABLE;
        $msg = $wpdb->prefix . self::MSG_TABLE;
        $stp = $wpdb->prefix . self::STEP_TABLE;
        $gdpr= $wpdb->prefix . self::GDPR_TABLE;

        $wpdb->delete($msg,  ['reservation_id'=>$rid], ['%s']);
        $wpdb->delete($stp,  ['reservation_id'=>$rid], ['%s']);
        $wpdb->delete($ins,  ['reservation_id'=>$rid], ['%s']);
        $wpdb->update($gdpr, ['status'=>'processed','processed_at'=>current_time('mysql')], ['reservation_id'=>$rid], ['%s','%s'], ['%s']);

        wp_redirect(admin_url('admin.php?page=pssr-gdpr')); exit;
    }

    /** Destinataires admin */
    private static function admin_recipients(){
        $emails = [ get_option('admin_email') ];
        if (is_email('contact@pssr.be')) $emails[] = 'contact@pssr.be';
        return array_unique(array_filter($emails, 'is_email'));
    }

    /** === FRONT — ESPACE PARTICIPANT (UI responsive) === */
    public static function shortcode_participant($atts){
        global $wpdb; 
        $ins = $wpdb->prefix . self::INS_TABLE;
        $msg = $wpdb->prefix . self::MSG_TABLE;
        $stp = $wpdb->prefix . self::STEP_TABLE;
        $gdpr= $wpdb->prefix . self::GDPR_TABLE;

        $rid = isset($_POST['reservation_id']) ? sanitize_text_field($_POST['reservation_id'])
             : (isset($_GET['rid']) ? sanitize_text_field($_GET['rid']) : '');

        ob_start();
        $bg = esc_url(self::BG_URL);
        ?>
        <style>
        .pssr-participant { 
          --ink:#0f0a22; --muted:#3e3566; --line:rgba(15,10,34,.16);
          --radius:16px; --card-shadow:0 10px 26px rgba(15,10,34,.10);
          --cta-grad:linear-gradient(90deg,#7b5cff,#a974ff 55%,#e381ff);
          font-family: Inter, Manrope, system-ui, sans-serif; color: var(--ink);
          background:
            radial-gradient(1100px 600px at -10% -10%, rgba(124,94,255,.10), transparent 70%),
            radial-gradient(1100px 600px at 110% 0%, rgba(227,129,255,.12), transparent 70%),
            linear-gradient(180deg, rgba(0,0,0,.45), rgba(0,0,0,.45)),
            url('<?php echo $bg; ?>') center/cover no-repeat;
          border-radius:20px; padding:18px;
          width:100%; max-width:960px; margin:0 auto; box-sizing:border-box;
        }
        .pssr-title { margin:0 0 8px; font-weight:800; font-size:1.35rem; color:#fff; text-shadow:0 2px 6px rgba(0,0,0,.45); }
        .pssr-card, .pssr-progress { background:rgba(255,255,255,.98); border:1px solid var(--line); border-radius: var(--radius); box-shadow: var(--card-shadow); padding:16px; width:100%; box-sizing:border-box; }
        .pssr-login { display:grid; gap:12px; width:100%; max-width:100%; }
        .pssr-login input { border:1px solid var(--line); border-radius: var(--radius); padding:12px 14px; font-size:16px; width:100%; box-sizing:border-box; }
        .pssr-btn { display:inline-block; padding:12px 18px; border-radius:999px; font-weight:800; color:#fff; background:var(--cta-grad); border:0; box-shadow:0 8px 24px rgba(15,10,34,.18); cursor:pointer; transition:transform .15s ease, box-shadow .25s ease; width:100%; text-align:center; box-sizing:border-box; }
        .pssr-btn:hover{ transform:translateY(-2px); box-shadow:0 12px 30px rgba(15,10,34,.22); }
        @media (min-width: 768px){ .pssr-btn{ width:auto; } }
        .pssr-steps { list-style:none; display:flex; flex-wrap:wrap; gap:12px; padding:0; margin:0; }
        .pssr-step { flex:1 1 160px; min-width:160px; text-align:center; padding:16px 12px; border-radius: var(--radius); border:1px solid var(--line); background:#fff; box-shadow: var(--card-shadow); cursor:pointer; transition:transform .12s ease, box-shadow .25s ease, background .25s ease; font-weight:700; color:#211a3f; }
        .pssr-step:hover{ transform:translateY(-2px); box-shadow:0 12px 30px rgba(15,10,34,.16); }
        .pssr-step[data-active="true"]{ background:linear-gradient(180deg,#7b5cff,#a974ff); color:#fff; border-color: transparent; text-shadow:0 1px 2px rgba(0,0,0,.28); }
        .pssr-step small { display:block; color: var(--muted); font-weight:600; }
        .pssr-step[data-active="true"] small{ color:#f0e9ff }
        .pssr-panel { display:none; margin-top:10px; border:1px solid var(--line); border-radius: var(--radius); background:#fff; box-shadow: var(--card-shadow); padding:12px; color:#1a1434; }
        .pssr-panel.active { display:block; }
        .pssr-kv { display:grid; grid-template-columns:200px 1fr; gap:10px; align-items:center; }
        @media (max-width:980px){ .pssr-kv{ grid-template-columns:1fr; } }
        .pssr-tag { display:inline-block; border:1px solid var(--line); border-radius:999px; padding:6px 10px; font-weight:800; background:linear-gradient(135deg,#efe6ff,#d5c2ff); color:#2b1f5b; }
        .pssr-msg{ color:#231a44 }
        </style>
        <?php

        echo '<div class="pssr-participant">';

        if (!$rid){
            ?>
            <form method="post" class="pssr-login">
              <h3 class="pssr-title">Espace participant</h3>
              <label for="pssr_rid" style="color:#fff;font-weight:700;text-shadow:0 1px 2px rgba(0,0,0,.45)">Votre numéro de réservation</label>
              <input id="pssr_rid" name="reservation_id" placeholder="Ex: AB-20250910" required />
              <button type="submit" class="pssr-btn">Accéder à mon parcours</button>
              <small style="color:#efe9ff;text-shadow:0 1px 2px rgba(0,0,0,.45)">Vous trouvez ce numéro dans l’email de confirmation.</small>
            </form>
            <?php
            echo '</div>';
            return ob_get_clean();
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ins WHERE reservation_id=%s", $rid));
        if (!$row){
            echo '<div class="pssr-card"><p>Numéro introuvable. Vérifiez votre saisie.</p><p><a class="pssr-btn" style="text-decoration:none" href="'.esc_url(remove_query_arg('rid')).'">Réessayer</a></p></div>';
            echo '</div>'; 
            return ob_get_clean();
        }

        // Étapes
        $defs = self::steps_def();
        $stp_tbl = $wpdb->prefix . self::STEP_TABLE;
        $steps_rows = $wpdb->get_results($wpdb->prepare("SELECT step_key, planned_date, done_date, note FROM $stp_tbl WHERE reservation_id=%s", $rid), OBJECT_K);
        $activeKey = null;
        switch ($row->statut) { case 'Inscrit': $activeKey='CAND'; break; case 'En cours': $activeKey='ARF'; break; case 'Terminé': $activeKey='SRS'; break; }

        ?>
        <div class="pssr-card" style="margin:10px 0;">
            <div class="pssr-kv">
              <div><strong>Nom</strong></div><div><?php echo esc_html($row->prenom_nom); ?></div>
              <div><strong>Email</strong></div><div><a href="mailto:<?php echo esc_attr($row->email); ?>"><?php echo esc_html($row->email); ?></a></div>
              <div><strong>Téléphone</strong></div><div><?php echo esc_html($row->tel); ?></div>
              <div><strong>Référent·e social·e</strong></div><div><?php echo esc_html($row->ref_sociale); ?></div>
              <div><strong>Session</strong></div><div><?php echo esc_html($row->session); ?></div>
              <div><strong>Statut</strong></div><div><span class="pssr-tag"><?php echo esc_html($row->statut); ?></span></div>
            </div>
        </div>

        <div class="pssr-progress" style="margin:16px 0;">
          <ol class="pssr-steps" role="tablist">
            <?php foreach ($defs as $key => $info): 
                $isActive = ($activeKey && $activeKey === $key);
                $sr = $steps_rows[$key] ?? null;
                $meta = [];
                if ($sr && !empty($sr->planned_date)) $meta[] = "Prévue: ".esc_html($sr->planned_date);
                if ($sr && !empty($sr->done_date))    $meta[] = "Réalisée: ".esc_html($sr->done_date);
                $metaTxt = $meta ? '<small>'.implode(' • ', $meta).'</small>' : '<small>&nbsp;</small>';
            ?>
              <li class="pssr-step" role="tab" aria-selected="<?php echo $isActive?'true':'false'; ?>" data-step="<?php echo esc_attr($key); ?>" data-active="<?php echo $isActive ? 'true':'false'; ?>">
                <?php if ($key==='CAND'): ?>
                  <span style="font-weight:800;"><?php echo esc_html($info['label']); ?></span>
                <?php else: ?>
                  <strong><?php echo esc_html($key); ?></strong><br><span style="font-size:.95em;"><?php echo esc_html($info['label']); ?></span>
                <?php endif; ?>
                <?php echo $metaTxt; ?>
              </li>
            <?php endforeach; ?>
          </ol>
        </div>

        <?php foreach ($defs as $key => $info):
            $sr = $steps_rows[$key] ?? null;
            $isActive = ($activeKey && $activeKey === $key);
        ?>
          <div class="pssr-panel <?php echo $isActive?'active':''; ?>" id="pssr-panel-<?php echo esc_attr($key); ?>" role="tabpanel">
            <h4 style="margin:0 0 6px;"><?php echo esc_html($info['label']); ?></h4>
            <p style="color:var(--muted);margin:0 0 8px;"><?php echo esc_html($info['desc']); ?></p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;color:var(--ink);">
              <span class="pssr-tag">Prévue : <?php echo $sr && $sr->planned_date ? esc_html($sr->planned_date) : '—'; ?></span>
              <span class="pssr-tag">Réalisée : <?php echo $sr && $sr->done_date ? esc_html($sr->done_date) : '—'; ?></span>
            </div>
            <?php if ($sr && !empty($sr->note)): ?>
              <div style="margin-top:8px;border:1px dashed var(--l500);border-radius:var(--radius);padding:10px;background:#fff;">
                <strong>Note de suivi :</strong><br><?php echo wpautop(esc_html($sr->note)); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <script>
        (function(){
          const tiles = document.querySelectorAll('.pssr-step');
          const panels = document.querySelectorAll('.pssr-panel');
          tiles.forEach(t => {
            t.addEventListener('click', () => {
              const key = t.getAttribute('data-step');
              panels.forEach(p => p.classList.remove('active'));
              const panel = document.getElementById('pssr-panel-' + key);
              if(panel){ panel.classList.add('active'); panel.scrollIntoView({behavior:'smooth', block:'nearest'}); }
              tiles.forEach(x => x.setAttribute('data-active','false'));
              tiles.forEach(x => x.setAttribute('aria-selected','false'));
              t.setAttribute('data-active','true');
              t.setAttribute('aria-selected','true');
            });
          });
        })();
        </script>
        <?php

        // Messages
        $msg_tbl = $wpdb->prefix . self::MSG_TABLE;
        $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $msg_tbl WHERE reservation_id=%s ORDER BY created_at DESC", $rid));
        ?>
        <div class="pssr-card" style="margin-top:16px;">
          <h4 style="margin:0 0 8px;font-size:1.2rem;font-weight:800;color:#211a3f;">Messages et documents</h4>
          <?php if ($messages): foreach($messages as $m): ?>
            <div class="pssr-msg" style="border:1px solid var(--line);border-radius:var(--radius);padding:10px;margin:8px 0;background:#fff;">
              <div style="font-size:12px;color:var(--muted);">Envoyé le <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($m->created_at))); ?></div>
              <div><?php echo wpautop(esc_html($m->content)); ?></div>
              <?php if (!empty($m->attachment_url)): ?>
                <div style="margin-top:6px;"><a href="<?php echo esc_url($m->attachment_url); ?>" target="_blank" rel="noopener">📄 Ouvrir le document</a></div>
              <?php endif; ?>
            </div>
          <?php endforeach; else: ?>
            <p style="color:var(--muted);">Pas encore de message. Revenez bientôt ✨</p>
          <?php endif; ?>
        </div>

        <?php
        // RGPD — bouton demande de suppression
        $gdpr_tbl = $wpdb->prefix . self::GDPR_TABLE;
        $pending = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $gdpr_tbl WHERE reservation_id=%s AND status='pending'", $rid));
        if (!$pending): ?>
        <div class="pssr-card" style="margin-top:16px;">
          <h4 style="margin:0 0 8px;font-size:1.2rem;font-weight:800;color:#211a3f;">🔒 RGPD — Droit à l’oubli</h4>
          <p style="color:var(--muted);margin-top:0;">Vous pouvez demander la suppression de vos données personnelles liées à ce parcours.</p>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:grid;gap:8px;max-width:100%;">
            <input type="hidden" name="action" value="pssr_gdpr_request"/>
            <?php wp_nonce_field('pssr_gdpr_request'); ?>
            <input type="hidden" name="reservation_id" value="<?php echo esc_attr($rid); ?>"/>
            <label>Email de contact
              <input type="email" name="email" value="<?php echo esc_attr($row->email); ?>" required style="border:1px solid var(--line);border-radius:16px;padding:10px 12px;width:100%;box-sizing:border-box;"/>
            </label>
            <label>Motif (facultatif)
              <textarea name="reason" rows="3" placeholder="Expliquez votre demande (facultatif)" style="border:1px solid var(--line);border-radius:16px;padding:10px 12px;width:100%;box-sizing:border-box;"></textarea>
            </label>
            <button class="pssr-btn" type="submit">Demander la suppression de mes données</button>
            <small style="color:var(--muted);">Votre demande sera traitée par l’équipe PSSR (délai légal : 30 jours).</small>
          </form>
        </div>
        <?php else: ?>
        <div class="pssr-card" style="margin-top:16px;">
          <h4 style="margin:0 0 8px;font-size:1.2rem;font-weight:800;color:#211a3f;">🔒 RGPD — Demande envoyée</h4>
          <p style="color:var(--muted);margin-top:0;">Votre demande de suppression est <strong>en cours de traitement</strong>. Merci pour votre patience.</p>
        </div>
        <?php endif; ?>

        <?php
        echo '</div>'; // .pssr-participant
        return ob_get_clean();
    }

    /** === FRONT — PAGE “TABLEAU DE BORD PSSR” (choix minimal, mobile) === */
    public static function shortcode_dashboard_chooser($atts){
        $participant_id = self::get_page_id_by_path('mon-parcours');
        $participant_url = $participant_id ? get_permalink($participant_id) : home_url('/mon-parcours/');
        $admin_target = admin_url('admin.php?page=pssr-dashboard');
        $referent_url = home_url('/connexion-referent/'); // redirige vers admin après login
        $bg = esc_url(self::BG_URL);

        ob_start(); ?>
        <style>
          .pssr-chooser{--ink:#0f0a22; --muted:#3e3566; --line:rgba(15,10,34,.16); --card-shadow:0 10px 26px rgba(15,10,34,.10); --cta-grad:linear-gradient(90deg,#7b5cff,#a974ff 55%,#e381ff); font-family:Inter,Manrope,system-ui,sans-serif; color:var(--ink);}
          .pssr-chooser .wrap{
            width:100%; max-width:960px; margin:0 auto; box-sizing:border-box;
            padding:20px; border-radius:20px;
            background:
              radial-gradient(1100px 600px at -10% -10%, rgba(124,94,255,.10), transparent 70%),
              radial-gradient(1100px 600px at 110% 0%, rgba(227,129,255,.12), transparent 70%),
              linear-gradient(180deg, rgba(0,0,0,.45), rgba(0,0,0,.45)),
              url('<?php echo $bg; ?>') center/cover no-repeat;
          }
          .pssr-chooser .grid{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));}
          @media (max-width: 768px){ .pssr-chooser .grid{ grid-template-columns:1fr; } }
          .pssr-chooser .card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--card-shadow);padding:16px;display:grid;gap:10px;width:100%;box-sizing:border-box;}
          .pssr-chooser a.btn{display:block;padding:12px 18px;border-radius:999px;font-weight:800;color:#fff;background:var(--cta-grad);box-shadow:0 8px 24px rgba(15,10,34,.18);text-decoration:none;text-align:center;width:100%;box-sizing:border-box;}
          .pssr-chooser a.btn:hover{transform:translateY(-2px);box-shadow:0 12px 30px rgba(15,10,34,.22);}
          @media (min-width: 768px){ .pssr-chooser a.btn{ width:auto; } }
        </style>
        <div class="pssr-chooser">
          <div class="wrap">
            <div class="grid">
              <div class="card">
                <h3 style="margin:0;color:#211a3f;font-weight:800;">👤 Espace Participant</h3>
                <a class="btn" href="<?php echo esc_url($participant_url); ?>">Accéder à mon parcours</a>
              </div>
              <div class="card">
                <h3 style="margin:0;color:#211a3f;font-weight:800;">👥 Espace Référent</h3>
                <a class="btn" href="<?php echo esc_url($referent_url); ?>">Cahier de communication</a>
              </div>
            </div>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

PSSR_Dashboard::init();
