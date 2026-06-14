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

function makeReservationCode(){
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  const random = Math.random().toString(36).slice(2, 6).toUpperCase();
  return `PSSR-${y}${m}${d}-${random}`;
}

function dataFromForm(form){
  const raw = Object.fromEntries(new FormData(form).entries());
  delete raw.website;

  const data = {};
  for (const [key, value] of Object.entries(raw)) {
    data[key] = cleanString(value, key === 'message' ? 3000 : 300);
  }

  return {
    ...data,
    source: location.pathname,
    userAgent: navigator.userAgent.slice(0, 300),
    createdAt: serverTimestamp ? serverTimestamp() : new Date().toISOString()
  };
}

function showMessage(form, message, ok = true){
  const msg = form.querySelector('.msg') || document.getElementById(form.id === 'reservation-form' ? 'reservation-msg' : 'contact-msg');
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

function validate(form, data){
  if (!data.nom || data.nom.length < 2) return 'Veuillez indiquer votre nom.';
  if (!data.email || !/^\S+@\S+\.\S+$/.test(data.email)) return 'Veuillez indiquer une adresse email valide.';
  if (form.dataset.firebaseCollection === 'messages' && (!data.message || data.message.length < 3)) return 'Veuillez écrire un message.';
  if (form.dataset.firebaseCollection === 'reservations' && (!data.creneau || data.creneau.length < 2)) return 'Veuillez choisir un créneau.';
  return '';
}

function mailtoFallback(data){
  const subject = encodeURIComponent('Message depuis le site PSSR');
  const body = encodeURIComponent(Object.entries(data).map(([k, v]) => `${k}: ${v}`).join('\\n'));
  location.href = `mailto:${siteConfig.contactEmail}?subject=${subject}&body=${body}`;
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
      const reservationCode = isReservation ? makeReservationCode() : '';
      if (isReservation) {
        payload.reservationCode = reservationCode;
        payload.status = payload.status || 'nouvelle';
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
        form.reset();
        if (isReservation) {
          showMessage(form, `Merci, votre réservation a bien été enregistrée. Votre référence est : ${reservationCode}. Gardez ce code : il sert uniquement à retrouver votre demande, il ne donne pas accès au tableau de bord admin.`);
        } else {
          showMessage(form, 'Merci, votre message a bien été enregistré.');
        }
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
