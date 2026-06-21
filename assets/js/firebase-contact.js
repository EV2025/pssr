import { firebaseConfig, firebaseEnabled, siteConfig } from './firebase-config.js';

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

  return {
    ...data,
    source: location.pathname,
    pageTitle: document.title,
    userAgent: navigator.userAgent.slice(0, 300),
    rgpdConsent: Boolean(raw.rgpdConsent),
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
    ? 'L’équipe PSSR vérifiera les disponibilités et vous recontactera pour confirmer les modalités.'
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
        <div><dt>Statut</dt><dd>Reçu — en attente de traitement</dd></div>
        <div><dt>Date</dt><dd>${esc(new Date().toLocaleString('fr-BE'))}</dd></div>
      </dl>
      <p class="receipt-note-v58">Conservez ce numéro pour toute question. ${esc(next)}</p>
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
  const enabled = await initFirebase().catch((err) => {
    console.error('Firebase init error:', err);
    return false;
  });

  document.querySelectorAll('form[data-firebase-collection]').forEach(form => {
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

      if (isReservation) {
        payload.reservationCode = makeTrackingCode('RES');
        payload.trackingCode = payload.reservationCode;
        payload.status = 'reçu';
        payload.paymentStatus = payload.paymentStatus || 'à confirmer';
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
          showMessage(form, 'Firebase n’est pas encore configuré. Ouverture de votre email pour envoyer la demande.', false);
          mailtoFallback(payload);
          return;
        }
        await addDoc(collection(db, collectionName), payload);
        rememberSubmission(payload);
        form.reset();
        showReceipt(form, payload, collectionName);
      }catch(err){
        console.error(err);
        showMessage(form, 'Impossible d’enregistrer dans Firebase. Vérifiez la configuration et les règles Firestore.', false);
      }finally{
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  });
}

attachForms();
