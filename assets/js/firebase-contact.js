import { firebaseConfig, firebaseEnabled, siteConfig } from './firebase-config.js';
import { buildEpcQrPayload, makeEpcQrSvg, epcDebugPayloadLength } from './epc-qr.js';

let db = null;
let addDoc = null;
let collection = null;
let serverTimestamp = null;

async function initFirebase(){
  if (!firebaseEnabled) return false;
  const appMod = await import('https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js');
  const fsMod = await import('https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore.js');
  const app = appMod.initializeApp(firebaseConfig);
  db = fsMod.getFirestore(app);
  addDoc = fsMod.addDoc;
  collection = fsMod.collection;
  serverTimestamp = fsMod.serverTimestamp;
  return true;
}

function cleanString(value, max = 1000){
  return String(value || '').trim().slice(0, max);
}

function esc(value){
  return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[c]));
}

const bankTransferConfig = {
  beneficiary: 'Équilibre Vital asbl',
  beneficiaryQr: 'Equilibre Vital asbl',
  iban: 'BE17 5230 8164 9221',
  bic: 'TRIOBEBB',
  amount: '165',
  currency: 'EUR',
  label: 'Cotisation PSSR — année académique'
};

function bankTransferValues(payload){
  const amount = payload.priceAmount || bankTransferConfig.amount;
  const currency = payload.priceCurrency || bankTransferConfig.currency;
  const communication = payload.reservationCode || payload.trackingCode || '';
  const ibanCompact = bankTransferConfig.iban.replace(/\s+/g, '');
  const qrPayload = buildEpcQrPayload({
    beneficiary: bankTransferConfig.beneficiaryQr || bankTransferConfig.beneficiary,
    iban: bankTransferConfig.iban,
    bic: bankTransferConfig.bic,
    amount,
    communication
  });
  return { amount, currency, communication, ibanCompact, qrPayload };
}

function paymentInstructionHtml(payload){
  const { amount, currency, communication, ibanCompact, qrPayload } = bankTransferValues(payload);
  let qrHtml = '';
  let qrWarning = '';
  try {
    qrHtml = makeEpcQrSvg(qrPayload, { size: 238, title: `QR code SEPA pour la réservation ${communication}` });
    const qrLength = epcDebugPayloadLength(qrPayload);
    qrWarning = qrLength > 120 ? '<p class="receipt-note-v58">Si votre application bancaire ne lit pas le QR, utilisez les informations écrites ci-dessous.</p>' : '';
  } catch (err) {
    console.warn('QR SEPA indisponible:', err);
    qrHtml = '<p class="receipt-note-v58">QR code indisponible pour cette réservation. Utilisez les informations écrites ci-dessous.</p>';
  }

  const allDetails = [
    `Montant : ${amount} ${currency}`,
    `Bénéficiaire : ${bankTransferConfig.beneficiary}`,
    `IBAN : ${bankTransferConfig.iban}`,
    `BIC : ${bankTransferConfig.bic}`,
    `Communication : ${communication}`
  ].join('\n');

  return `
    <section class="receipt-payment-v1 epc-payment-v1" aria-label="Instructions de virement bancaire">
      <p class="receipt-eyebrow-v58">Paiement par virement bancaire</p>
      <h3>Étape suivante : scannez le QR ou effectuez le virement</h3>
      <div class="epc-payment-grid-v1">
        <div class="epc-qr-card-v1">
          <p class="epc-qr-title-v1">QR code bancaire SEPA</p>
          <div class="epc-qr-box-v1">${qrHtml}</div>
          <p class="epc-qr-help-v1">Scannez avec votre application bancaire. Vérifiez toujours le montant et la communication avant de valider.</p>
          ${qrWarning}
        </div>
        <div>
          <dl class="receipt-details-v58 epc-details-v1">
            <div><dt>Montant</dt><dd><strong>${esc(amount)} ${esc(currency)}</strong></dd></div>
            <div><dt>Bénéficiaire</dt><dd>${esc(bankTransferConfig.beneficiary)}</dd></div>
            <div><dt>IBAN</dt><dd><code>${esc(bankTransferConfig.iban)}</code></dd></div>
            <div><dt>BIC</dt><dd><code>${esc(bankTransferConfig.bic)}</code></dd></div>
            <div><dt>Communication</dt><dd><code>${esc(communication)}</code></dd></div>
          </dl>
          <div class="epc-copy-actions-v1" aria-label="Copier les informations de virement">
            <button type="button" class="epc-copy-btn-v1" data-copy-value="${esc(ibanCompact)}">Copier l’IBAN</button>
            <button type="button" class="epc-copy-btn-v1" data-copy-value="${esc(communication)}">Copier la communication</button>
            <button type="button" class="epc-copy-btn-v1" data-copy-value="${esc(allDetails)}">Copier tout</button>
          </div>
        </div>
      </div>
      <p class="receipt-note-v58"><strong>Important :</strong> indiquez exactement la communication ci-dessus. L’équipe passera votre dossier en “payé” après vérification du virement sur le compte bancaire.</p>
    </section>`;
}

function makeTrackingCode(type = 'GEN'){
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  const random = Math.random().toString(36).replace(/[^a-z0-9]/gi, '').slice(2, 6).toUpperCase().padEnd(4, 'X');
  return `PSSR-${type}-${y}${m}${d}-${random}`;
}

function dataFromForm(form){
  const raw = Object.fromEntries(new FormData(form).entries());
  delete raw.website;

  const data = {};
  for (const [key, value] of Object.entries(raw)) {
    data[key] = cleanString(value, key === 'message' || key === 'objectifs' ? 3000 : 300);
  }

  // Compatibilité : certains anciens formulaires utilisaient name/phone/subject/consent.
  // Firestore V58 attend surtout nom/telephone/type/rgpdConsent.
  if (!data.nom && data.name) data.nom = data.name;
  if (!data.telephone && data.phone) data.telephone = data.phone;
  if (!data.type && data.subject) data.type = data.subject;

  const hasConsent = Boolean(raw.rgpdConsent || raw.consent || raw.privacy || raw.accept);

  return {
    ...data,
    source: location.pathname,
    pageTitle: document.title,
    userAgent: navigator.userAgent.slice(0, 300),
    rgpdConsent: hasConsent,
    createdAt: serverTimestamp ? serverTimestamp() : new Date().toISOString()
  };
}

function findMessageElement(form){
  return form.querySelector('.msg') || document.getElementById(form.id === 'reservation-form' ? 'reservation-msg' : 'contact-msg');
}

function showMessage(form, message, ok = true){
  const msg = findMessageElement(form);
  if (msg){
    msg.hidden = false;
    msg.style.display = 'block';
    msg.textContent = message;
    msg.style.color = ok ? '#356b42' : '#9b2f2f';
    msg.scrollIntoView({behavior:'smooth', block:'nearest'});
  } else {
    alert(message);
  }
}

function showReceipt(form, payload, kind){
  const msg = findMessageElement(form);
  const code = payload.reservationCode || payload.messageCode || payload.trackingCode || '—';
  const isReservation = kind === 'reservations';
  const title = isReservation ? 'Réservation reçue' : 'Demande reçue';
  const label = isReservation ? 'Numéro de réservation' : 'Numéro de suivi';
  const next = isReservation
    ? 'Votre place sera vérifiée par l’équipe. Pour finaliser le dossier, utilisez les informations de virement ci-dessous.'
    : 'L’équipe PSSR reviendra vers vous dès que possible.';
  const msgText = isReservation
    ? `Votre demande de réservation a bien été enregistrée. Votre numéro de réservation est ${code}.`
    : `Votre demande a bien été enregistrée. Votre numéro de suivi est ${code}.`;

  if (!msg){
    alert(`${msgText}\nConservez ce numéro.`);
    return;
  }

  msg.hidden = false;
  msg.style.display = 'block';
  msg.style.color = '#244b31';
  msg.innerHTML = `
    <article class="receipt-card-v58" role="status" aria-live="polite">
      <p class="receipt-eyebrow-v58">Accusé de réception</p>
      <h2>${esc(title)}</h2>
      <p>${esc(msgText)}</p>
      <div class="receipt-code-v58"><span>${esc(label)}</span><strong>${esc(code)}</strong></div>
      <dl class="receipt-details-v58">
        <div><dt>Statut</dt><dd>${isReservation ? 'Reçu — en attente de virement' : 'Reçu — en attente de traitement'}</dd></div>
        <div><dt>Date</dt><dd>${esc(new Date().toLocaleString('fr-BE'))}</dd></div>
      </dl>
      <p class="receipt-note-v58">Conservez ce numéro pour toute question. ${esc(next)}</p>
      ${isReservation ? paymentInstructionHtml(payload) : ''}
    </article>`;
  msg.scrollIntoView({behavior:'smooth', block:'nearest'});
}

function validate(form, data){
  if (!form.checkValidity()) {
    form.reportValidity();
    return 'Veuillez compléter les champs obligatoires.';
  }
  if (!data.nom || data.nom.length < 2) return 'Veuillez indiquer votre nom.';
  if (!data.email || !/^\S+@\S+\.\S+$/.test(data.email)) return 'Veuillez indiquer une adresse email valide.';
  if (!data.rgpdConsent) return 'Veuillez accepter la politique de confidentialité.';
  if (form.dataset.firebaseCollection === 'messages' && (!data.message || data.message.length < 3)) return 'Veuillez écrire un message.';
  if (form.dataset.firebaseCollection === 'reservations' && (!data.creneau || data.creneau.length < 2) && (!data.modules || data.modules.length < 2)) return 'Veuillez choisir une activité ou un module.';
  return '';
}


function ensureFeedbackElement(form){
  let feedback = form.querySelector('.contact-live-feedback-v59');
  if (!feedback) {
    feedback = document.createElement('p');
    feedback.className = 'contact-live-feedback-v59';
    feedback.setAttribute('role', 'status');
    feedback.setAttribute('aria-live', 'polite');
    feedback.hidden = true;
    const actions = form.querySelector('.home-contact-actions-v52, .form-actions, .actions, button[type="submit"]') || form.lastElementChild;
    if (actions && actions.parentNode === form) actions.insertAdjacentElement('afterend', feedback);
    else form.appendChild(feedback);
  }
  return feedback;
}

function setLiveFeedback(form, message, state = 'info'){
  const feedback = ensureFeedbackElement(form);
  feedback.hidden = false;
  feedback.textContent = message;
  feedback.dataset.state = state;
}


function clearLiveFeedback(form){
  const feedback = form.querySelector('.contact-live-feedback-v59');
  if (!feedback) return;
  feedback.hidden = true;
  feedback.textContent = '';
  delete feedback.dataset.state;
}

function withTimeout(promise, ms, message){
  let timer = null;
  const timeout = new Promise((_, reject) => {
    timer = setTimeout(() => reject(new Error(message || 'Délai dépassé.')), ms);
  });
  return Promise.race([promise, timeout]).finally(() => {
    if (timer) clearTimeout(timer);
  });
}

function initCopyButtons(){
  if (document.documentElement.dataset.epcCopyReady === 'true') return;
  document.documentElement.dataset.epcCopyReady = 'true';

  document.addEventListener('click', async (event) => {
    const button = event.target.closest('.epc-copy-btn-v1');
    if (!button) return;

    const value = button.getAttribute('data-copy-value') || '';
    if (!value) return;

    const originalText = button.textContent;
    const markCopied = () => {
      button.textContent = 'Copié ✓';
      window.setTimeout(() => { button.textContent = originalText; }, 1800);
    };

    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(value);
        markCopied();
        return;
      }
    } catch (_) {
      // Fallback ci-dessous.
    }

    try {
      const textarea = document.createElement('textarea');
      textarea.value = value;
      textarea.setAttribute('readonly', '');
      textarea.style.position = 'fixed';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      textarea.remove();
      markCopied();
    } catch (_) {
      window.prompt('Copiez cette information :', value);
    }
  });
}

function initLiveFormFeedback(form){
  if (form.dataset.liveFeedbackReady === 'true') return;
  form.dataset.liveFeedbackReady = 'true';
  const fields = Array.from(form.querySelectorAll('input, select, textarea')).filter(field => {
    const type = (field.getAttribute('type') || '').toLowerCase();
    return !['hidden', 'submit', 'button', 'reset'].includes(type) && field.name !== 'website';
  });
  const updateField = field => {
    const type = (field.getAttribute('type') || '').toLowerCase();
    const hasValue = type === 'checkbox' || type === 'radio' ? field.checked : String(field.value || '').trim().length > 0;
    field.classList.toggle('is-filled-v59', hasValue);
    field.classList.toggle('is-invalid-v59', Boolean(field.required && !field.checkValidity() && hasValue));
  };
  const updateForm = () => {
    fields.forEach(updateField);
    const filled = fields.filter(field => {
      const type = (field.getAttribute('type') || '').toLowerCase();
      return type === 'checkbox' || type === 'radio' ? field.checked : String(field.value || '').trim().length > 0;
    }).length;
    if (filled > 0 && !form.dataset.submittedOk) {
      setLiveFeedback(form, 'Saisie détectée : vos informations sont prises en compte.', form.checkValidity() ? 'ok' : 'info');
    }
  };
  fields.forEach(field => {
    field.addEventListener('input', updateForm);
    field.addEventListener('change', updateForm);
  });
}

function mailtoFallback(data){
  const code = data.reservationCode || data.messageCode || data.trackingCode || '';
  const subject = encodeURIComponent(code ? `Demande PSSR — ${code}` : 'Message depuis le site PSSR');
  const body = encodeURIComponent(Object.entries(data).map(([k, v]) => `${k}: ${v}`).join('\n'));
  location.href = `mailto:${siteConfig.contactEmail}?subject=${subject}&body=${body}`;
}

function submissionKey(payload){
  return ['pssrSubmission', payload.email || '', payload.creneau || '', payload.modules || '', payload.message || ''].join('|').toLowerCase();
}

function wasRecentlySubmitted(payload){
  try{
    const key = submissionKey(payload);
    const last = Number(localStorage.getItem(key) || 0);
    return last && (Date.now() - last) < 3 * 60 * 1000;
  }catch(_){ return false; }
}

function rememberSubmission(payload){
  try{ localStorage.setItem(submissionKey(payload), String(Date.now())); }catch(_){ }
}

async function attachForms(){
  const forms = Array.from(document.querySelectorAll('form[data-firebase-collection]'));
  forms.forEach(initLiveFormFeedback);

  const enabled = await initFirebase().catch((err) => {
    console.error('Firebase init error:', err);
    return false;
  });

  forms.forEach(form => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (form.website && form.website.value) return;

      const payload = dataFromForm(form);
      const validationError = validate(form, payload);
      if (validationError){
        showMessage(form, validationError, false);
        return;
      }

      const collectionName = form.dataset.firebaseCollection || 'messages';
      const isReservation = collectionName === 'reservations';

      if (wasRecentlySubmitted(payload)) {
        showMessage(form, 'Une demande identique vient déjà d’être envoyée. Attendez quelques minutes ou contactez l’équipe PSSR si nécessaire.', false);
        return;
      }

      setLiveFeedback(form, 'Envoi en cours… merci de patienter quelques secondes.', 'sending');

      if (isReservation) {
        payload.reservationCode = makeTrackingCode('RES');
        payload.trackingCode = payload.reservationCode;
        payload.status = 'reçu';
        payload.paymentStatus = payload.paymentStatus || 'en attente de virement';
        payload.priceAmount = payload.priceAmount || '165';
        payload.priceCurrency = payload.priceCurrency || 'EUR';
        payload.priceLabel = payload.priceLabel || 'Tarif solidaire — 165€ / année académique';
        if (!payload.modules && payload.creneau) payload.modules = payload.creneau;
      } else {
        payload.messageCode = makeTrackingCode('MSG');
        payload.trackingCode = payload.messageCode;
        payload.status = 'reçu';
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      try{
        if (!enabled || !db){
          clearLiveFeedback(form);
          showMessage(form, 'Firebase n’est pas encore configuré. Ouverture de votre email pour envoyer la demande.', false);
          mailtoFallback(payload);
          return;
        }

        await withTimeout(
          addDoc(collection(db, collectionName), payload),
          15000,
          'Firebase met trop de temps à répondre. Vérifiez votre connexion puis réessayez.'
        );

        rememberSubmission(payload);
        form.dataset.submittedOk = 'true';
        form.reset();
        form.querySelectorAll('.is-filled-v59,.is-invalid-v59').forEach(el => el.classList.remove('is-filled-v59','is-invalid-v59'));
        clearLiveFeedback(form);
        showReceipt(form, payload, collectionName);
      }catch(err){
        console.error(err);
        clearLiveFeedback(form);
        const message = err && err.message && err.message.includes('trop de temps')
          ? err.message
          : 'Impossible d’enregistrer dans Firebase. Vérifiez la connexion, la configuration ou les règles Firestore.';
        showMessage(form, message, false);
      }finally{
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  });
}

initCopyButtons();
attachForms();
