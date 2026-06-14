<?php
/**
 * Plugin Name: PSSR — Booking Plein Écran (bilingue, moderne mauve/fuchsia)
 * Description: [pssr_booking] — Sélection M1→M6, formulaire minimal, communication propre et facture A4 bilingue (PDF si Dompdf sinon impression). UI moderne (cartes + boutons mauve/fuchsia), responsive, tableau 100% largeur.
 * Version: 3.9.0
 * Author: PSSR
 * License: GPLv2 or later
 * Text Domain: pssr-booking
 */

if (!defined('ABSPATH')) exit;

/* =========================================================
   IDENTITÉ / CONSTANTES (Belgique)
   ========================================================= */
const PSSRBF_ORG   = 'Equilibre Vital';
const PSSRBF_ADDR  = 'Bruxelles-Capitale, Belgique';
const PSSRBF_BCE   = '1019487618';
const PSSRBF_NACE  = '88999 – Autres activités de travail social sans hébergement n.c.a. / Andere maatschappelijke dienstverlening zonder huisvesting n.e.g.';
const PSSRBF_EMAIL = 'equilibrevital.bruxelles@gmail.com';
const PSSRBF_TEL   = '0476/840.756';
const PSSRBF_IBAN  = 'BE52 7350 6608 8209';
const PSSRBF_BIC   = 'KRED BE BB';
const PSSRBF_LOGO  = 'http://pssr.be/wp-content/uploads/2025/09/cropped-ChatGPT-Image-21-juin-2025-20_32_51.png';

const PSSRBF_DST_NAME = 'CPAS – OCMW Anderlecht';
const PSSRBF_DST_ADDR = 'Avenue Raymond Vander Bruggen 62/64, 1070 Anderlecht – Bruxelles – Belgique';
const PSSRBF_DST_BCE  = '0212.346.856';
const PSSRBF_DST_NACE = '84115 – Administration publique locale / Lokale overheidsadministratie';
const PSSRBF_DST_TEL  = '02 529 41 20';
const PSSRBF_DST_EMAIL= 'contactcenter@cpas-anderlecht.brussels';

const PSSRBF_TVA_TX  = 0.00; // exonéré (art.44)
const PSSRBF_TVA_TXT_EXO_FR = 'TVA non applicable selon l’article 44 du Code de la TVA.';
const PSSRBF_TVA_TXT_EXO_NL = 'BTW niet van toepassing volgens artikel 44 van het BTW-Wetboek.';
const PSSRBF_PAY_DAYS = 30;

const PSSRBF_PACK  = 500.00; // Pack collectif (tous modules)

const PSSRBF_PAGE_SLUG  = 'reservation-pssr';
const PSSRBF_PAGE_TITLE = 'Réservation PSSR';

const PSSRBF_OPT_SEQ    = 'pssrbf_seq_';
const PSSRBF_OPT_ORD    = 'pssrbf_order_';
const PSSRBF_OPT_RIDMAP = 'pssrbf_rid_names';

/* =========================================================
   ACTIVATION (page auto)
   ========================================================= */
register_activation_hook(__FILE__, function () {
  if (!get_option(PSSRBF_OPT_RIDMAP)) update_option(PSSRBF_OPT_RIDMAP, [], false);
  $p = get_page_by_path(PSSRBF_PAGE_SLUG);
  if ($p && $p->post_status === 'trash') {
    wp_untrash_post($p->ID);
    wp_update_post(['ID'=>$p->ID,'post_status'=>'publish','post_content'=>'[pssr_booking]']);
  } elseif (!$p) {
    wp_insert_post([
      'post_type'=>'page','post_status'=>'publish',
      'post_title'=>PSSRBF_PAGE_TITLE,'post_name'=>PSSRBF_PAGE_SLUG,
      'post_content'=>'[pssr_booking]'
    ]);
  }
  flush_rewrite_rules(false);
});

/* =========================================================
   HELPERS
   ========================================================= */
function pssrbf_money($v){ return number_format((float)$v, 2, ',', ' ') . ' €'; }
function pssrbf_clean_rid($rid){ return preg_replace('~[^A-Z0-9\-]~','', strtoupper(trim((string)$rid))); }
function pssrbf_key($rid){ return PSSRBF_OPT_ORD . md5($rid); }
function pssrbf_get_order($rid){
  $rid = pssrbf_clean_rid($rid); if(!$rid) return null;
  $raw = get_option(pssrbf_key($rid),''); if(!$raw) return null;
  $arr = json_decode($raw,true); return is_array($arr)?$arr:null;
}
function pssrbf_put_order($o){
  if (empty($o['rid'])) return false;
  $rid = pssrbf_clean_rid($o['rid']); $o['rid']=$rid;
  return update_option(pssrbf_key($rid), wp_json_encode($o), false);
}
function pssrbf_next_invoice(){
  $y = date('Y'); $k = PSSRBF_OPT_SEQ.$y; $n = (int)get_option($k,0); $n++;
  update_option($k,$n,false);
  return sprintf('CPAS-%s-%03d',$y,$n);
}
function pssrbf_remember_candidate($rid,$name){
  $rid = pssrbf_clean_rid($rid); $name = trim((string)$name);
  if(!$rid || !$name) return;
  $map = get_option(PSSRBF_OPT_RIDMAP, []); if(!is_array($map)) $map=[];
  $map[$rid] = $name; update_option(PSSRBF_OPT_RIDMAP, $map, false);
}

/* =========================================================
   MODULES FR/NL
   ========================================================= */
function pssrbf_modules(){
  $mods = [
    1 => ['code'=>'M1','title_fr'=>'Ateliers de Sensibilisation (groupe ÷ 12)','title_nl'=>'Sensibiliseringsateliers (groep ÷ 12)','desc'=>'Sensibiliser / Bewustmaking','price'=>30.00,'hours'=>3,'rate'=>10.0,'mode'=>'Groupe / Groep','lieu'=>'Salle / Extérieur'],
    2 => ['code'=>'M2','title_fr'=>'Bilan Socio-Sportif','title_nl'=>'Sociaal-Sportieve Evaluatie','desc'=>'Évaluer & plan / Evaluatie & plan','price'=>30.00,'hours'=>3,'rate'=>10.0,'mode'=>'Individuel / Individueel','lieu'=>'Salle / Bureau'],
    3 => ['code'=>'M3','title_fr'=>'Parcours Découverte Sportive','title_nl'=>'Sportieve Ontdekkingsparcours','desc'=>'Découverte / Ontdekking','price'=>30.00,'hours'=>6,'rate'=>5.0,'mode'=>'Individuel','lieu'=>'Clubs partenaires'],
    4 => ['code'=>'M4','title_fr'=>'Activité Physique Adaptée','title_nl'=>'Aangepaste Lichamelijke Activiteit','desc'=>'Adaptée / Aangepast','price'=>30.00,'hours'=>4,'rate'=>7.5,'mode'=>'Individuel','lieu'=>'Salle / Terrain'],
    5 => ['code'=>'M5','title_fr'=>'Concertation Partagée','title_nl'=>'Gedeeld Overlegmoment','desc'=>'Coordination / Coördinatie','price'=>30.00,'hours'=>3,'rate'=>10.0,'mode'=>'Réunion / Overleg','lieu'=>'Salle / Visioconf.'],
    6 => ['code'=>'M6','title_fr'=>'Suivi Renforcé & Solutions (40h)','title_nl'=>'Versterkte Opvolging & Oplossingen (40u)','desc'=>'Stage / Praktijk','price'=>350.00,'hours'=>40,'rate'=>8.75,'mode'=>'Individuel / Groep','lieu'=>"Structure d’accueil"],
  ];
  ksort($mods, SORT_NUMERIC);
  return apply_filters('pssrbf_modules', $mods);
}

/* =========================================================
   SHORTCODE
   ========================================================= */
add_shortcode('pssr_booking', function(){
  ob_start();
  pssrbf_styles_min();
  $nonce = wp_create_nonce('pssrbf_nonce');
  $pack_price = (float) apply_filters('pssrbf_pack_price', PSSRBF_PACK);
  ?>
  <section id="pssrbf" role="application" aria-label="Réservation de modules">
    <header class="fx-header">
      <div class="fx-header-inner">
        <h1 class="ttl">Parcours socio-sportif — sélection & facture</h1>
        <div class="filters">
          <!-- Pas de recherche -->
          <button class="btn-ghost" id="pack-toggle" type="button" aria-pressed="false" title="Activer/Désactiver le Pack">Pack (<?php echo number_format($pack_price,2,',',' '); ?> €)</button>
        </div>
      </div>
    </header>

    <main class="fx-main" id="fx-main">
      <div class="cols">
        <!-- Catalogue -->
        <section aria-label="Catalogue" class="col">
          <h2 class="h2">Modules (M1 → M6)</h2>
          <div id="cards" class="cards"><?php echo pssrbf_render_cards(); ?></div>
        </section>

        <!-- Panier & Formulaire -->
        <section aria-label="Sélection et coordonnées" class="col">
          <h2 class="h2">Votre sélection</h2>
          <div id="cart" class="cart" aria-live="polite"><p class="help">Aucun module sélectionné.</p></div>
          <div class="total"><span>Total</span><strong id="total">0,00 €</strong></div>

          <h2 class="h2" style="margin-top:16px">Informations minimales</h2>
          <form id="mandant" class="form" autocomplete="off">
            <div class="row">
              <input class="in" id="rid" name="rid" placeholder="N° de réservation (ex: AB-20250910)" required>
              <input class="in" id="candidate" name="candidate" placeholder="Nom de la personne inscrite" required>
            </div>
            <div class="row">
              <input class="in" id="refcpas" name="refcpas" placeholder="Référent CPAS" required>
              <input class="in" id="follow_email" name="follow_email" type="email" placeholder="Email de suivi" required>
            </div>

            <div class="actions actions--two">
              <button id="btn-comm" class="btn btn--fix" type="button" title="Générer la communication" disabled>Communication</button>
              <button id="btn-download" class="btn-ghost btn--fix" type="button" disabled>Télécharger la facture (PDF)</button>
            </div>
          </form>

          <!-- Communication -->
          <div class="box ci" id="block-ci" hidden>
            <h3 class="h2" style="margin:0 0 6px">Communication</h3>
            <textarea id="ci-txt" class="in" style="min-height:220px" readonly></textarea>
            <button type="button" class="btn-ghost" id="ci-copy" style="margin-top:8px">Copier</button>
          </div>
        </section>
      </div>
    </main>

    <footer class="fx-footer" id="fx-footer">
      <div class="foot-left">
        <strong id="sum">0,00 €</strong>
        <span class="muted" id="count">0 sélection</span>
      </div>
      <div class="foot-right">
        <input id="ridbar" class="in in-sm" placeholder="N° de Réservation">
      </div>
    </footer>
  </section>
  <?php
  pssrbf_js_min($nonce, $pack_price);
  return ob_get_clean();
});

/* =========================================================
   RENDU CARTES (UI moderne)
   ========================================================= */
function pssrbf_render_cards(){
  $mods = pssrbf_modules(); $h='';
  foreach($mods as $id=>$m){
    $h .= '<article class="cardw" data-id="'.(int)$id.'">';
    $h .=   '<div class="cardw__title">'.esc_html($m['code'].' — '.$m['title_fr']).'</div>';
    $h .=   '<div class="muted">'.esc_html($m['title_nl']).'</div>';
    $h .=   '<div class="meta muted">'.esc_html($m['hours']).' h @ '.number_format($m['rate'],2,',',' ').' €/h • '.esc_html($m['mode']).' • '.esc_html($m['lieu']).'</div>';
    $h .=   '<div class="cardw__foot">';
    $h .=     '<span class="price">'.number_format_i18n((float)$m['price'],2).' €</span>';
    $h .=     '<span class="status" style="display:none"></span>';
    $h .=     '<div class="btns">';
    $h .=       '<button class="btn btn-sm add" data-id="'.(int)$id.'">Ajouter</button>';
    $h .=       '<button class="btn-ghost btn-sm remove" data-id="'.(int)$id.'" style="display:none">Retirer</button>';
    $h .=     '</div>';
    $h .=   '</div>';
    $h .= '</article>';
  }
  return $h;
}

/* =========================================================
   AJAX
   ========================================================= */
add_action('wp_ajax_pssrbf_checkout','pssrbf_ajax_checkout');
add_action('wp_ajax_nopriv_pssrbf_checkout','pssrbf_ajax_checkout');
function pssrbf_ajax_checkout(){
  check_ajax_referer('pssrbf_nonce');
  $ids = array_map('intval',(array)($_POST['ids'] ?? []));
  sort($ids, SORT_NUMERIC);
  $mods = pssrbf_modules(); $total=0; $rows=[];
  foreach($ids as $id){
    if(isset($mods[$id])){
      $rows[]=[
        'id'=>$id,
        'code'=>$mods[$id]['code'],
        'title_fr'=>$mods[$id]['title_fr'],
        'title_nl'=>$mods[$id]['title_nl'],
        'price'=>$mods[$id]['price']
      ];
      $total += (float)$mods[$id]['price'];
    }
  }
  wp_send_json_success(['modules'=>$rows,'total'=>$total]);
}

add_action('wp_ajax_pssrbf_reserver','pssrbf_ajax_reserver');
add_action('wp_ajax_nopriv_pssrbf_reserver','pssrbf_ajax_reserver');
function pssrbf_ajax_reserver(){
  check_ajax_referer('pssrbf_nonce');

  $ids   = array_map('intval',(array)($_POST['ids'] ?? []));
  $pack  = !empty($_POST['pack']);
  $rid   = pssrbf_clean_rid($_POST['rid'] ?? '');
  $candidate = sanitize_text_field($_POST['candidate'] ?? '');
  $refcpas   = sanitize_text_field($_POST['refcpas'] ?? '');
  $follow_email = sanitize_email($_POST['follow_email'] ?? '');

  if (!$rid || (!$pack && !$ids) || !$candidate || !$refcpas || !$follow_email){
    wp_send_json_error(['msg'=>'Champs requis manquants.']); return;
  }

  $allmods = pssrbf_modules();
  if ($pack){ $ids = array_keys($allmods); }
  sort($ids, SORT_NUMERIC);

  $pack_price = (float) apply_filters('pssrbf_pack_price', PSSRBF_PACK);
  $subtotal = $pack ? $pack_price : 0.0;
  if(!$pack){ foreach($ids as $id){ if(isset($allmods[$id])) $subtotal += (float)$allmods[$id]['price']; } }
  $vat = round($subtotal * (float)PSSRBF_TVA_TX, 2);
  $total = $subtotal + $vat;

  $o = pssrbf_get_order($rid);
  if($o){
    $o['candidate']=$candidate; $o['refcpas']=$refcpas; $o['follow_email']=$follow_email;
    $o['modules']=$ids; $o['pack']=$pack?1:0;
    $o['subtotal']=$subtotal; $o['vat']=$vat; $o['montant']=$total;
    $o['updated_at']=current_time('mysql');
    $o['issued_at'] = $o['issued_at'] ?? current_time('mysql');
    $o['due_at'] = date_i18n('Y-m-d H:i:s', strtotime('+'.PSSRBF_PAY_DAYS.' days'));
  } else {
    $o = [
      'rid'=>$rid,
      'invoice_no'=>pssrbf_next_invoice(),
      'issued_at'=>current_time('mysql'),
      'due_at'=>date_i18n('Y-m-d H:i:s', strtotime('+'.PSSRBF_PAY_DAYS.' days')),
      'candidate'=>$candidate,
      'refcpas'=>$refcpas,
      'follow_email'=>$follow_email,
      'modules'=>$ids,'pack'=>$pack?1:0,
      'subtotal'=>$subtotal,'vat'=>$vat,'montant'=>$total,'status'=>'pending',
    ];
  }
  pssrbf_put_order($o);
  pssrbf_remember_candidate($rid, $candidate);
  wp_send_json_success(['ok'=>1,'rid'=>$rid,'ci'=>pssrbf_build_ci($o)]);
}

/* =========================================================
   PDF / IMPRESSION
   ========================================================= */
add_action('wp_ajax_pssrbf_download_pdf','pssrbf_ajax_download_pdf');
add_action('wp_ajax_nopriv_pssrbf_download_pdf','pssrbf_ajax_download_pdf');
function pssrbf_ajax_download_pdf(){
  check_ajax_referer('pssrbf_nonce');
  $rid  = pssrbf_clean_rid($_GET['rid'] ?? '');
  if(!$rid){ status_header(400); exit('Bad request'); }
  $o = pssrbf_get_order($rid); if(!$o){ status_header(404); exit('Not found'); }

  if (class_exists('\Dompdf\Dompdf')) {
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8">'.pssrbf_print_styles_for_download().'</head><body>'.pssrbf_render_invoice_a4($o,true).'</body></html>';
    $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled'=>true,'defaultPaperSize'=>'a4','defaultPaperOrientation'=>'portrait']);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();
    $fname = 'Facture-'.$o['invoice_no'].'.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    echo $dompdf->output(); exit;
  }

  $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Facture-'.$o['invoice_no'].'</title>'.pssrbf_print_styles_for_download().'</head><body onload="window.print()" onafterprint="window.close()">'.pssrbf_render_invoice_a4($o,true).'</body></html>';
  nocache_headers();
  header('Content-Type: text/html; charset=utf-8');
  echo $html; exit;
}

/* =========================================================
   COMMUNICATION — bilingue dans le contenu (titre simple)
   ========================================================= */
function pssrbf_build_ci($o){
  $mods = pssrbf_modules();
  $lines_fr=[]; $lines_nl=[]; $subtotal=0.0;

  if(!empty($o['pack'])){
    $subtotal = (float) apply_filters('pssrbf_pack_price', PSSRBF_PACK);
    foreach(array_keys($mods) as $id){
      $lines_fr[] = $mods[$id]['code'].' — '.$mods[$id]['title_fr'];
      $lines_nl[] = $mods[$id]['code'].' — '.$mods[$id]['title_nl'];
    }
  } else {
    foreach(($o['modules']??[]) as $id){
      if(isset($mods[$id])){
        $m=$mods[$id]; $subtotal += (float)$m['price'];
        $lines_fr[] = $m['code'].' — '.$m['title_fr'].' ('.pssrbf_money($m['price']).')';
        $lines_nl[] = $m['code'].' — '.$m['title_nl'].' ('.pssrbf_money($m['price']).')';
      }
    }
  }
  $vat = round($subtotal * (float)PSSRBF_TVA_TX, 2);
  $total = $subtotal + $vat;

  $txt =
"FR — Communication\n".
"Réf. : PSSR ".$o['rid']." • Personne inscrite : ".$o['candidate']." • Référent CPAS : ".$o['refcpas']." • Email : ".$o['follow_email']."\n".
"Modules :\n- ".implode("\n- ", $lines_fr)."\n".
"Sous-total : ".pssrbf_money($subtotal)." • TVA : ".pssrbf_money($vat)." • Total : ".pssrbf_money($total)."\n".
"Paiement sous ".PSSRBF_PAY_DAYS." jours (merci de prévenir en cas de retard).\n".
"\n".
"NL — Communicatie\n".
"Ref. : PSSR ".$o['rid']." • Ingeschreven : ".$o['candidate']." • CPAS-referent : ".$o['refcpas']." • E-mail : ".$o['follow_email']."\n".
"Modules :\n- ".implode("\n- ", $lines_nl)."\n".
"Subtotaal : ".pssrbf_money($subtotal)." • BTW : ".pssrbf_money($vat)." • Totaal : ".pssrbf_money($total)."\n".
"Betaling binnen ".PSSRBF_PAY_DAYS." dagen (gelieve te verwittigen bij vertraging).\n".
"\n".
"Infos comptabilité / Boekhouding\n".
"• Nom / Naam : ".PSSRBF_ORG."\n".
"• IBAN : ".PSSRBF_IBAN."\n".
"• BIC : ".PSSRBF_BIC."\n".
"• BCE / Ondernemingsnummer : ".PSSRBF_BCE."\n";
  return $txt;
}

/* =========================================================
   FACTURE A4 — moderne, compacte (1 page), full width
   ========================================================= */
function pssrbf_render_invoice_a4($o, $for_download){
  $issued = date_i18n('d/m/Y', strtotime($o['issued_at'] ?? 'now'));
  $due    = date_i18n('d/m/Y', strtotime($o['due_at'] ?? '+'.PSSRBF_PAY_DAYS.' days'));
  $tva_tx = (float)PSSRBF_TVA_TX;

  $mods     = pssrbf_modules();
  $lines    = [];
  $subtotal = 0.0;

  if(!empty($o['pack'])){
    foreach($mods as $id=>$m){
      $lines[] = ['code'=>$m['code'],'desc'=>$m['title_fr'].' / '.$m['title_nl'],'qty'=>1,'unit'=>0.00,'total'=>0.00,'note'=>'Inclus / Inbegrepen'];
    }
    $pack = (float) apply_filters('pssrbf_pack_price', PSSRBF_PACK);
    $lines[] = ['code'=>'PACK','desc'=>'Forfait PSSR — Tous modules / Alle modules','qty'=>1,'unit'=>$pack,'total'=>$pack,'bold'=>true];
    $subtotal = $pack;
  } else {
    $ids = array_map('intval', (array)($o['modules']??[]));
    sort($ids,SORT_NUMERIC);
    foreach($ids as $id){
      if(isset($mods[$id])){
        $m=$mods[$id]; $unit=(float)$m['price'];
        $lines[] = ['code'=>$m['code'],'desc'=>$m['title_fr'].' / '.$m['title_nl'],'qty'=>1,'unit'=>$unit,'total'=>$unit];
        $subtotal += $unit;
      }
    }
  }
  $vat  = round($subtotal * $tva_tx, 2);
  $total= $subtotal + $vat;

  ob_start(); ?>
  <div class="a4 a4-theme">
    <div class="band-top"></div>

    <!-- HEADER -->
    <section class="row head">
      <div class="brand">
        <?php if(PSSRBF_LOGO): ?><img src="<?php echo esc_url(PSSRBF_LOGO); ?>" alt="Logo Equilibre Vital" class="logo"><?php endif; ?>
        <div class="org">
          <strong><?php echo esc_html(PSSRBF_ORG); ?></strong><br>
          <?php echo esc_html(PSSRBF_ADDR); ?><br>
          BCE / Ondernemingsnr.: <?php echo esc_html(PSSRBF_BCE); ?><br>
          Email : <?php echo esc_html(PSSRBF_EMAIL); ?> — Tel.: <?php echo esc_html(PSSRBF_TEL); ?>
        </div>
      </div>
      <div class="docid">
        <h1><span class="pill">FACTURE / FACTUUR</span> <?php echo esc_html($o['invoice_no']); ?></h1>
        <div class="meta">
          Émise le : <?php echo esc_html($issued); ?> • Échéance : <?php echo esc_html($due); ?> &nbsp;|&nbsp;
          Uitgiftedatum : <?php echo esc_html($issued); ?> • Vervaldatum : <?php echo esc_html($due); ?>
        </div>
      </div>
    </section>

    <!-- BLOCS IDENTITÉ -->
    <section class="row cols-2 block">
      <div class="box">
        <div class="box-title">Bénéficiaire / Begunstigde</div>
        <div class="line"><strong><?php echo esc_html(PSSRBF_ORG); ?></strong></div>
        <div class="line"><?php echo esc_html(PSSRBF_ADDR); ?></div>
        <div class="line">BCE / Ondernemingsnummer : <?php echo esc_html(PSSRBF_BCE); ?></div>
        <div class="line">NACEBEL : <?php echo esc_html(PSSRBF_NACE); ?></div>
        <div class="line">Email : <?php echo esc_html(PSSRBF_EMAIL); ?> — Tel.: <?php echo esc_html(PSSRBF_TEL); ?></div>
        <div class="line">IBAN : <?php echo esc_html(PSSRBF_IBAN); ?> — BIC : <?php echo esc_html(PSSRBF_BIC); ?></div>
      </div>
      <div class="box">
        <div class="box-title">Destinataire / Ontvanger</div>
        <div class="line"><strong><?php echo esc_html(PSSRBF_DST_NAME); ?></strong></div>
        <div class="line"><?php echo esc_html(PSSRBF_DST_ADDR); ?></div>
        <div class="line">BCE / Ondernemingsnummer : <?php echo esc_html(PSSRBF_DST_BCE); ?></div>
        <div class="line">NACEBEL : <?php echo esc_html(PSSRBF_DST_NACE); ?></div>
        <div class="line">Tel.: <?php echo esc_html(PSSRBF_DST_TEL); ?> — Email : <?php echo esc_html(PSSRBF_DST_EMAIL); ?></div>
        <div class="line muted">Référent / Referent : <?php echo esc_html($o['refcpas'] ?? ''); ?> — Email : <?php echo esc_html($o['follow_email'] ?? ''); ?></div>
        <div class="line">Réf.: PSSR <?php echo esc_html($o['rid']); ?></div>
      </div>
    </section>

    <!-- TABLEAU PRESTATIONS (full width) -->
    <section class="row block table-wrap">
      <table class="table table-fluid">
        <thead>
          <tr>
            <th style="width:64px">Code</th>
            <th>Description (FR / NL)</th>
            <th class="r" style="width:64px">Qté</th>
            <th class="r" style="width:92px">PU</th>
            <th class="r" style="width:110px">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($lines as $L): ?>
            <tr<?php echo !empty($L['bold'])?' style="font-weight:700"':''; ?>>
              <td><?php echo esc_html($L['code']); ?></td>
              <td>
                <?php echo esc_html($L['desc']); ?>
                <?php if(!empty($L['note'])): ?><div class="muted" style="font-size:10px"><?php echo esc_html($L['note']); ?></div><?php endif; ?>
              </td>
              <td class="r"><?php echo (int)$L['qty']; ?></td>
              <td class="r"><?php echo pssrbf_money($L['unit']); ?></td>
              <td class="r"><?php echo pssrbf_money($L['total']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="4" class="r"><strong>Sous-total / Subtotaal</strong></td><td class="r"><?php echo pssrbf_money($subtotal); ?></td></tr>
          <tr><td colspan="4" class="r"><strong>TVA / BTW</strong></td><td class="r"><?php echo pssrbf_money($vat); ?></td></tr>
          <tr class="grand"><td colspan="4" class="r"><strong>Total</strong></td><td class="r total"><?php echo pssrbf_money($total); ?></td></tr>
        </tfoot>
      </table>
    </section>

    <!-- Mentions + Paiement -->
    <section class="row cols-2 block">
      <div class="box">
        <div class="box-title">Mentions / Vermeldingen</div>
        <div class="line muted">TVA / BTW : Exonération – article 44 Code TVA / Vrijstelling – artikel 44 BTW-Wetboek.</div>
        <div class="line muted"><?php echo esc_html(PSSRBF_TVA_TXT_EXO_FR); ?> <?php echo esc_html(PSSRBF_TVA_TXT_EXO_NL); ?></div>
      </div>
      <div class="box">
        <div class="box-title">Paiement / Betaling</div>
        <div class="line">Paiement sous <?php echo (int)PSSRBF_PAY_DAYS; ?> jours. En cas de retard, merci de nous en avertir.</div>
        <div class="line">Betaling binnen <?php echo (int)PSSRBF_PAY_DAYS; ?> dagen. Bij vertraging gelieve ons te verwittigen.</div>
        <div class="line">IBAN : <strong><?php echo esc_html(PSSRBF_IBAN); ?></strong> — BIC : <strong><?php echo esc_html(PSSRBF_BIC); ?></strong></div>
        <div class="line">Communication / Mededeling : <strong>PSSR <?php echo esc_html($o['rid']); ?></strong></div>
      </div>
    </section>

    <footer class="foot muted">
      Merci pour votre confiance • Bedankt voor uw vertrouwen — Document généré automatiquement / Automatisch gegenereerd document
    </footer>

    <div class="band-bottom"></div>
  </div>
  <?php
  return ob_get_clean();
}

/* =========================================================
   CSS — UI moderne + A4 + Compact 1 page
   ========================================================= */
function pssrbf_styles_min(){ static $done=false; if($done) return; $done=true; ?>
<style>
/* libérer la largeur thème */
body:has(#pssrbf) .container, body:has(#pssrbf) .site, body:has(#pssrbf) .site-content,
body:has(#pssrbf) .content-area, body:has(#pssrbf) .entry-content, body:has(#pssrbf) .wrap,
body:has(#pssrbf) .container-fluid, body:has(#pssrbf) .ast-container,
body:has(#pssrbf) .elementor-section-wrap, body:has(#pssrbf) .elementor-container,
body:has(#pssrbf) .fl-row-content-wrap { max-width:none!important; width:100%!important; padding-left:0!important; padding-right:0!important; overflow:visible!important }

/* Thème mauve/fuchsia */
#pssrbf{
  width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw);
  min-height:100svh;
  --bg-start:#0f0a1f; --bg-end:#0a0c17;
  --mauve:#7B5CFF; --mauve-dark:#5C3FE6;
  --fuchsia:#E24CCB; --fuchsia-dark:#C43BB0;
  --ink:#ECEAF8; --ink-muted:rgba(236,234,248,.78);
  --surf:rgba(255,255,255,.08); --line:rgba(255,255,255,.14);
  --r:16px; --gap:12px; --max:1200px;
  font-family:Inter,system-ui,sans-serif; color:var(--ink);
  display:flex; flex-direction:column;
  background:
    radial-gradient(1100px 560px at 20% -10%, rgba(123,92,255,.18), transparent 60%),
    radial-gradient(900px 500px at 90% 10%, rgba(226,76,203,.10), transparent 60%),
    linear-gradient(180deg, var(--bg-start), var(--bg-end));
}
#pssrbf *{box-sizing:border-box; min-width:0}

/* Header */
#pssrbf .fx-header{ position:sticky; top:0; z-index:50; background:rgba(15,10,31,.85); backdrop-filter:blur(8px); border-bottom:1px solid var(--line) }
#pssrbf .fx-header-inner{ max-width:var(--max); margin:0 auto; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px }
#pssrbf .ttl{ margin:0; font-size:21px; font-weight:800; color:#fff; letter-spacing:.2px }
#pssrbf .filters{ display:flex; gap:8px; align-items:center }

/* Inputs & boutons */
#pssrbf .in{ width:100%; padding:12px 13px; border-radius:12px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--ink) }
#pssrbf .in-sm{ padding:10px 12px; max-width:210px }
#pssrbf .in:focus-visible{ outline:2px solid var(--fuchsia); outline-offset:2px }

#pssrbf .btn, #pssrbf .btn-ghost{
  border-radius:999px; font-weight:800; cursor:pointer;
  padding:11px 18px; min-height:44px; line-height:1;
  display:inline-flex; align-items:center; justify-content:center;
  transition:transform .06s ease, box-shadow .2s ease, opacity .2s ease, background .2s ease, border .2s ease;
}
#pssrbf .btn{ background: linear-gradient(135deg, var(--mauve), var(--fuchsia)); color:#fff; border:0; box-shadow:0 12px 28px rgba(123,92,255,.32) }
#pssrbf .btn:hover{ background: linear-gradient(135deg, var(--mauve-dark), var(--fuchsia-dark)); transform:translateY(-1px); box-shadow:0 14px 32px rgba(193,59,176,.34) }
#pssrbf .btn:disabled{ opacity:.55; cursor:not-allowed; box-shadow:none }
#pssrbf .btn-ghost{ background:transparent; color:var(--mauve); border:2px solid var(--mauve) }
#pssrbf .btn-ghost:hover{ background: rgba(226,76,203,.08); color: var(--fuchsia-dark); border-color: var(--fuchsia-dark); transform:translateY(-1px) }
#pssrbf .btn-sm{ padding:8px 12px; min-height:36px; font-weight:800 }
#pssrbf .btn--fix{ min-width:0; }

/* Layout */
#pssrbf .fx-main{ flex:1; min-height:0; }
#pssrbf .cols{ max-width:var(--max); margin:0 auto; padding:18px; display:grid; grid-template-columns:2fr 1.2fr; gap:16px }
#pssrbf .col{ background:var(--surf); border:1px solid var(--line); border-radius:var(--r); padding:14px }
#pssrbf .h2{ margin:0 0 10px; font-size:16px; font-weight:800; color:#fff }
#pssrbf .help, #pssrbf .muted{ color:var(--ink-muted) }
#pssrbf .form .row{ display:grid; grid-template-columns:1fr 1fr; gap:10px }

/* Cartes modules */
#pssrbf .cards{ display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px }
#pssrbf .cardw{ background:rgba(255,255,255,.10); border:1px solid var(--line); border-radius:16px; padding:14px; display:flex; flex-direction:column; gap:8px; box-shadow: 0 6px 18px rgba(0,0,0,.12) }
#pssrbf .cardw__title{ font-weight:800; color:#fff }
#pssrbf .meta{ font-size:12px }
#pssrbf .cardw__foot{ display:flex; align-items:center; justify-content:space-between; gap:8px }
#pssrbf .cardw .status{ font-size:12px; color:#cfd1ff; display:none }
#pssrbf .price{ font-weight:900 }

/* Panier */
#pssrbf .cart .row{ display:flex; justify-content:space-between; align-items:center; gap:8px; background:rgba(255,255,255,.06); border:1px solid var(--line); border-radius:12px; padding:8px }
#pssrbf .total{ display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); padding-top:10px; margin-top:10px; font-weight:800 }

/* Actions */
#pssrbf .actions{ margin-top:12px }
#pssrbf .actions--two{ display:grid; grid-template-columns:1fr 1fr; gap:10px }

/* Footer sticky */
#pssrbf .fx-footer{ position:sticky; bottom:0; z-index:60; background:rgba(15,10,31,.92); backdrop-filter:blur(8px); border-top:1px solid var(--line); padding:10px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; width:100%; max-width:var(--max); margin:0 auto }
#pssrbf .fx-main{ padding-bottom: 96px; }

/* A4 (écran) — compact & pro */
#pssrbf .a4{ color:#111; background:#fff; font-size:11px; line-height:1.35; position:relative }
#pssrbf .a4 .muted{ color:#666 }
#pssrbf .a4 .band-top{ position:absolute; inset:0 0 auto 0; height:12mm; background:linear-gradient(90deg, #ede9ff 0%, #ffe9fb 70%); border-bottom:1px solid #e6e9f2 }
#pssrbf .a4 .band-bottom{ position:absolute; inset:auto 0 0 0; height:5mm; background:#f3f5fa; border-top:1px solid #e6e9f2 }
#pssrbf .a4 .head{ display:flex; justify-content:space-between; gap:10px; margin:13mm 0 6px }
#pssrbf .a4 .brand{ display:flex; gap:8px; align-items:flex-start }
#pssrbf .a4 .logo{ max-height:120px; width:auto; height:54px; border-radius:10px; background:#fff; border:1px solid #e9e9f4; object-fit:contain }
#pssrbf .a4 .docid h1{ font-size:16px; margin:0 0 4px }
#pssrbf .a4 .docid .pill{ background:linear-gradient(135deg, #7B5CFF, #E24CCB); color:#fff; padding:3px 8px; border-radius:999px; margin-right:6px }
#pssrbf .a4 .meta{ font-size:11px }
#pssrbf .a4 .block{ margin-top:6px }
#pssrbf .a4 .cols-2{ display:grid; grid-template-columns:1fr 1fr; gap:8px }
#pssrbf .a4 .box{ border:1px solid #e8ebf4; border-radius:10px; padding:8px; background:#fff }
#pssrbf .a4 .box-title{ font-weight:800; margin-bottom:4px; color:#5C3FE6 }
#pssrbf .a4 .table{ width:100%; border-collapse:collapse; margin:0; table-layout:fixed }
#pssrbf .a4 .table thead th{ background:#f6f7fb; border:1px solid #eceff6; padding:6px; text-align:left; white-space:nowrap }
#pssrbf .a4 .table td{ padding:6px; border:1px solid #eceff6; background:#fff; vertical-align:top }
#pssrbf .a4 .table .r{ text-align:right }
#pssrbf .a4 .table tfoot td{ padding:6px }
#pssrbf .a4 .table tfoot .grand td,
#pssrbf .a4 .table tr.grand td{ background:#f9f6ff; border-top:2px solid #7B5CFF }
#pssrbf .a4 .table tfoot .total{ font-weight:900; font-size:13px; color:#5C3FE6 }
#pssrbf .a4 .foot{ border-top:1px solid #eceff6; margin-top:8px; padding-top:6px; font-size:10.5px; text-align:center }

/* Responsive */
@media (max-width:980px){
  #pssrbf .cols{ grid-template-columns:1fr }
  #pssrbf .form .row{ grid-template-columns:1fr }
  #pssrbf .actions--two{ grid-template-columns:1fr }
  #pssrbf .a4 .cols-2{ grid-template-columns:1fr }
  #pssrbf .a4 .head{ flex-direction:column; margin-top:9mm }
}

/* Impression écran (pas PDF dompdf) */
@media print{
  #pssrbf{ background:#fff }
  #pssrbf .fx-header,#pssrbf .fx-footer{ display:none }
  #pssrbf .col{ border:0; background:#fff }
}
</style>
<script>
(function(){
  var el = document.getElementById('pssrbf'); if(!el) return;
  var p = el.parentElement;
  for (var i=0;i<8 && p;i++,p=p.parentElement){
    if (/(container|wrap|entry-content|content|site|elementor|ast-container|fl-row-content-wrap)/i.test(p.className)) {
      p.style.maxWidth = 'none'; p.style.overflow = 'visible'; p.style.paddingLeft = p.style.paddingRight = '0';
    }
  }
  var footer = document.getElementById('fx-footer') || el.querySelector('.fx-footer');
  var main = el.querySelector('.fx-main');
  function pad(){ if(!footer || !main) return; var h = footer.getBoundingClientRect().height || 72; main.style.paddingBottom = (h + 18) + 'px'; }
  pad(); window.addEventListener('resize', pad);
  try{ document.documentElement.style.scrollBehavior='smooth'; }catch(e){}
})();
</script>
<?php }

/* =========================================================
   CSS A4 pour PDF/impression (compacte 1 page)
   ========================================================= */
function pssrbf_print_styles_for_download(){
  ob_start(); ?>
  <style>
    @page { size: A4 portrait; margin: 14mm; }
    html, body { background:#fff; }
    body{font-family:Inter,system-ui,sans-serif;color:#111;margin:0;padding:0;font-size:11px;line-height:1.35}
    .a4{ color:#111; background:#fff; position:relative }
    .a4 .muted{ color:#666 }
    .a4 .band-top{ position:absolute; inset:0 0 auto 0; height:12mm; background:linear-gradient(90deg, #ede9ff 0%, #ffe9fb 70%); border-bottom:1px solid #e6e9f2 }
    .a4 .band-bottom{ position:absolute; inset:auto 0 0 0; height:5mm; background:#f3f5fa; border-top:1px solid #e6e9f2 }
    .a4 .head{ display:flex; justify-content:space-between; gap:10px; margin:12mm 0 6px }
    .a4 .brand{ display:flex; gap:8px; align-items:flex-start }
    .a4 .logo{ max-height:120px; width:auto; height:54px; border-radius:10px; border:1px solid #e9e9f4; object-fit:contain }
    .a4 .docid h1{ font-size:16px; margin:0 0 4px }
    .a4 .docid .pill{ background:linear-gradient(135deg, #7B5CFF, #E24CCB); color:#fff; padding:3px 8px; border-radius:999px; margin-right:6px }
    .a4 .meta{ font-size:11px }
    .a4 .block{ margin-top:6px }
    .a4 .cols-2{ display:grid; grid-template-columns:1fr 1fr; gap:8px }
    .a4 .box{ border:1px solid #e8ebf4; border-radius:10px; padding:8px; background:#fff }
    .a4 .box-title{ font-weight:800; margin-bottom:4px; color:#5C3FE6 }
    .a4 .table{ width:100%; border-collapse:collapse; margin:0; table-layout:fixed }
    .a4 .table thead th{ background:#f6f7fb; border:1px solid #eceff6; padding:6px; text-align:left; white-space:nowrap }
    .a4 .table td{ padding:6px; border:1px solid #eceff6; background:#fff; vertical-align:top }
    .a4 .table .r{ text-align:right }
    .a4 .table tfoot td{ padding:6px }
    .a4 .table tr.grand td{ background:#f9f6ff; border-top:2px solid #7B5CFF }
    .a4 .table tfoot .total{ font-weight:900; font-size:13px; color:#5C3FE6 }
    .a4 .foot{ border-top:1px solid #eceff6; margin-top:8px; padding-top:6px; font-size:10.5px; text-align:center }
  </style>
  <?php return ob_get_clean();
}

/* =========================================================
   JS — LOGIQUE
   ========================================================= */
function pssrbf_js_min($nonce, $pack_price){ ?>
<script>
(function(){
  const root = document.getElementById('pssrbf');
  const qs = s => root.querySelector(s);

  const list = qs('#cards');
  const cart = qs('#cart');
  const totalEl = qs('#total');
  const sumEl = qs('#sum');
  const countEl = qs('#count');

  const ridForm = qs('#rid');
  const ridBar  = qs('#ridbar');
  const candidate = qs('#candidate');
  const refcpas  = qs('#refcpas');
  const follow   = qs('#follow_email');

  const packToggle = qs('#pack-toggle');
  const btnComm = qs('#btn-comm');
  const btnDl   = qs('#btn-download');

  const ciBlock = qs('#block-ci');
  const ciTxt   = qs('#ci-txt');

  const ajaxURL = '<?php echo esc_url( admin_url('admin-ajax.php') ); ?>';
  const NONCE   = '<?php echo esc_js($nonce); ?>';
  const PACKVAL = <?php echo json_encode((float)$pack_price); ?>;
  const MODS    = <?php echo wp_json_encode(pssrbf_modules()); ?>;

  // État initial
  localStorage.setItem('pssrbf_pack','0');
  localStorage.setItem('pssrbf_sel','[]');

  const fmt = v => new Intl.NumberFormat('fr-BE',{minimumFractionDigits:2}).format(Number(v||0))+' \u20AC';
  const getSel = ()=> JSON.parse(localStorage.getItem('pssrbf_sel')||'[]');
  const setSel = ids => { ids = [...new Set(ids.map(i=>String(i)))]; ids.sort((a,b)=>Number(a)-Number(b)); localStorage.setItem('pssrbf_sel', JSON.stringify(ids)); refreshCardsUI(); };
  const getPack= ()=> localStorage.getItem('pssrbf_pack')==='1';
  const setPack= on => { localStorage.setItem('pssrbf_pack', on?'1':'0'); packToggle.setAttribute('aria-pressed', on?'true':'false'); refreshCardsUI(); };

  function refreshCardsUI(){
    const idsSel = new Set(getSel().map(String));
    list.querySelectorAll('.cardw').forEach(card=>{
      const id = String(card.dataset.id);
      const add = card.querySelector('.add');
      const rem = card.querySelector('.remove');
      const st  = card.querySelector('.status');
      if (getPack()){
        if (add) add.style.display='none';
        if (rem) rem.style.display='none';
        if (st){ st.textContent='Inclus (Pack)'; st.style.display='inline'; }
      } else {
        if (st) st.style.display='none';
        if (idsSel.has(id)){ if(add) add.style.display='none'; if(rem) rem.style.display='inline-flex'; }
        else { if(add) add.style.display='inline-flex'; if(rem) rem.style.display='none'; }
      }
    });
  }

  // Ajout module
  list.addEventListener('click', e=>{
    const add = e.target.closest('.add'); if(!add) return;
    const id = String(add.dataset.id);
    const ids = getSel();
    if (!ids.includes(id)) ids.push(id);
    setPack(false);
    setSel(ids);
    refreshCart();
  });

  // Retrait module
  list.addEventListener('click', e=>{
    const rem = e.target.closest('.remove'); if(!rem) return;
    const id = String(rem.dataset.id);
    const ids = getSel().filter(x => String(x)!==id);
    setPack(false);
    setSel(ids);
    refreshCart();
  });

  // Pack ON/OFF — ON => tous, OFF => vide
  packToggle.addEventListener('click', ()=>{
    const on = !getPack();
    setPack(on);
    if (on) setSel(Object.keys(MODS)); else setSel([]);
    refreshCart();
  });

  // Retirer depuis panier
  cart.addEventListener('click', e=>{
    const rm = e.target.closest('[data-remove]'); if(!rm) return;
    const id = String(rm.dataset.remove);
    const ids = getSel().filter(x => String(x)!==id);
    setPack(false);
    setSel(ids);
    refreshCart();
  });

  // Sync RID top/bottom
  function setRIDStorage(v){ localStorage.setItem('pssrbf_rid', (v||'').toUpperCase()); syncRIDtoUI(); }
  function syncRIDtoUI(){ const v=(localStorage.getItem('pssrbf_rid')||'').toUpperCase(); if(ridForm && ridForm!==document.activeElement) ridForm.value=v; if(ridBar && ridBar!==document.activeElement) ridBar.value=v; }
  ridForm?.addEventListener('input', ()=> setRIDStorage(ridForm.value));
  ridBar ?.addEventListener('input', ()=> setRIDStorage(ridBar.value));

  async function refreshCart(){
    let ids = getSel().map(Number).sort((a,b)=>a-b).map(String);
    let total = 0;

    if (getPack()){
      total = PACKVAL;
      cart.innerHTML =
        '<p class="help">Pack activé — tous les modules (M1 → M6).</p>' +
        Object.keys(MODS).map(id=>`
          <div class="row">
            <span>${MODS[id].code} — ${MODS[id].title_fr} / ${MODS[id].title_nl}</span>
            <button class="btn btn-sm" data-remove="${id}">Retirer</button>
          </div>`).join('');
      countEl.textContent = Object.keys(MODS).length+' modules (Pack)';
    } else {
      const f = new FormData();
      f.append('_ajax_nonce',NONCE);
      f.append('action','pssrbf_checkout');
      ids.forEach(id=> f.append('ids[]', id));
      const j = await fetch(ajaxURL, {method:'POST', body:f}).then(r=>r.json()).catch(()=>({}));
      const mods=j?.data?.modules||[];
      total = Number(j?.data?.total||0);
      cart.innerHTML = mods.length ? mods.map(m=>`
        <div class="row">
          <span>${m.code} — ${m.title_fr} / ${m.title_nl}</span>
          <button class="btn btn-sm" data-remove="${m.id}">Retirer</button>
        </div>
      `).join('') : '<p class="help">Aucun module sélectionné.</p>';
      countEl.textContent = (mods.length||0)+' sélection';
    }
    totalEl.textContent = fmt(total);
    sumEl.textContent   = fmt(total);

    const ready = ((ridForm?.value||'').trim() && (candidate?.value||'').trim() && (refcpas?.value||'').trim() && (follow?.value||'').trim() && (getPack() || getSel().length>0));
    btnComm.disabled = !ready;
    btnDl  .disabled = !ready;

    refreshCardsUI();
  }

  // Communication
  btnComm?.addEventListener('click', async ()=>{
    const ids = getSel(); const pack = getPack();
    const f = new FormData();
    f.append('_ajax_nonce',NONCE);
    f.append('action','pssrbf_reserver');
    ids.forEach(id=> f.append('ids[]', id));
    f.append('pack', pack ? '1':'');
    f.append('rid', (ridForm.value||'').toUpperCase());
    f.append('candidate',(candidate.value||'').trim());
    f.append('refcpas', (refcpas.value||'').trim());
    f.append('follow_email', (follow.value||'').trim());
    const j = await fetch(ajaxURL, {method:'POST', body:f}).then(r=>r.json()).catch(()=>({}));
    if(j?.success){
      ciBlock.hidden=false; ciTxt.value=j.data?.ci||'';
      alert('Enregistré. Communication prête.');
    } else alert(j?.data?.msg || 'Erreur.');
  });

  // PDF
  btnDl?.addEventListener('click', ()=>{
    const rid=(ridForm.value||'').toUpperCase(); if(!rid){ alert('N° de Réservation requis.'); return; }
    const url = ajaxURL+'?action=pssrbf_download_pdf&rid='+encodeURIComponent(rid)+'&_ajax_nonce='+encodeURIComponent(NONCE);
    window.open(url,'_blank','noopener');
  });

  // Init
  (function init(){
    try{ document.documentElement.style.scrollBehavior='smooth'; }catch(e){}
    localStorage.setItem('pssrbf_pack','0');
    localStorage.setItem('pssrbf_sel','[]');
    packToggle && packToggle.setAttribute('aria-pressed','false');
    refreshCardsUI();
    refreshCart();
  })();
})();
</script>
<?php }
