import { firebaseConfig, firebaseEnabled } from './firebase-config.js';

const warning = document.getElementById('config-warning');
const loginPanel = document.getElementById('login-panel');
const dashboardPanel = document.getElementById('dashboard-panel');
const loginForm = document.getElementById('login-form');
const loginMsg = document.getElementById('login-msg');
const logoutBtn = document.getElementById('logout');
const recordsEl = document.getElementById('records');
const exportBtn = document.getElementById('export-csv');
const seedBtn = document.getElementById('seed-pages');
const seedSlotsBtn = document.getElementById('seed-slots');
const seedServicesBtn = document.getElementById('seed-services');
const collectionTitle = document.getElementById('collection-title');
const summaryEl = document.getElementById('admin-summary');

let auth, db;
let currentCollection = 'messages';
let rows = [];
let modules = {};
let unsub = null;

const labels = {
  messages:'Messages reçus',
  reservations:'Réservations reçues',
  pages:'Contenu des pages',
  users:'Clients / membres',
  services:'Services & tarifs',
  slots:'Calendrier / créneaux',
  payments:'Paiements — suivi manuel',
  notifications:'Notifications internes',
  attendances:'Présences',
  emailLogs:'Journaux d’e-mails',
  stats:'Statistiques'
};

function setMsg(text, ok = false){
  loginMsg.hidden = false;
  loginMsg.textContent = text;
  loginMsg.style.color = ok ? '#356b42' : '#9b2f2f';
}

function fmtDate(v){
  try { return v?.toDate ? v.toDate().toLocaleString('fr-BE') : (v || ''); }
  catch { return ''; }
}


const fieldLabels = {
  nom: 'Nom',
  fullName: 'Nom complet',
  displayName: 'Nom affiché',
  firstName: 'Prénom',
  lastName: 'Nom de famille',
  email: 'E-mail',
  tel: 'Téléphone',
  phone: 'Téléphone',
  subject: 'Sujet',
  message: 'Message',
  notes: 'Notes',
  type: 'Type de demande',
  status: 'Statut',
  createdAt: 'Date de création',
  updatedAt: 'Dernière mise à jour',
  source: 'Page d’origine',
  userAgent: 'Navigateur / appareil',
  service: 'Service demandé',
  serviceName: 'Service',
  activity: 'Activité',
  price: 'Prix',
  priceLabel: 'Tarif',
  day: 'Jour',
  time: 'Horaire',
  startTime: 'Heure de début',
  endTime: 'Heure de fin',
  public: 'Public',
  capacity: 'Capacité',
  active: 'Actif',
  order: 'Ordre d’affichage',
  slug: 'Identifiant de page',
  title: 'Titre',
  content: 'Contenu',
  published: 'Publié',
  reservationCode: 'Référence de réservation',
  memberCode: 'Code membre',
  uid: 'ID utilisateur',
  role: 'Rôle',
  level: 'Niveau PSSR',
  paymentStatus: 'Statut paiement',
  amount: 'Montant',
  method: 'Méthode',
  consentRgpd: 'Consentement RGPD',
  rgpdConsent: 'Consentement RGPD',
  newsletterConsent: 'Consentement newsletter',
  importedFrom: 'Source d’import',
  slotId: 'ID du créneau',
  reservationId: 'ID réservation',
  attendanceStatus: 'Statut présence',
  date: 'Date',
  preferredDate: 'Date souhaitée',
  preferredTime: 'Heure souhaitée'
};

const valueLabels = {
  contact: 'Demande de contact',
  reservation: 'Réservation',
  nouveau: 'Nouveau',
  nouvelle: 'Nouvelle',
  traité: 'Traité',
  traitee: 'Traitée',
  confirmée: 'Confirmée',
  confirmee: 'Confirmée',
  'liste attente': 'Liste d’attente',
  annulée: 'Annulée',
  annulee: 'Annulée',
  payé: 'Payé',
  paye: 'Payé',
  'à relancer': 'À relancer',
  actif: 'Actif',
  inactif: 'Inactif',
  member: 'Membre',
  coach: 'Coach',
  admin: 'Administrateur'
};

const technicalFields = new Set([
  'userAgent',
  'source',
  'uid',
  'slotId',
  'reservationId',
  'importedFrom',
  'ownerUid',
  'createdBy',
  'updatedBy'
]);

function labelForField(key){
  return fieldLabels[key] || key.replace(/([A-Z])/g, ' $1').replace(/^./, c => c.toUpperCase());
}

function labelForValue(value){
  if (typeof value === 'boolean') return value ? 'Oui' : 'Non';
  if (typeof value === 'string') return valueLabels[value] || value;
  return value;
}

function formatValue(key, value){
  if (key === 'createdAt' || key === 'updatedAt' || key === 'date') return fmtDate(value);
  const labelled = labelForValue(value);
  if (Array.isArray(labelled)) return labelled.map(v => String(labelForValue(v))).join(', ');
  if (labelled && typeof labelled === 'object') {
    try { return JSON.stringify(labelled, null, 2); }
    catch { return String(labelled); }
  }
  return labelled ?? '';
}

function esc(v){
  return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[c]));
}

async function init(){
  if (!firebaseEnabled){
    warning.hidden = false;
    loginPanel.hidden = true;
    return;
  }

  const appMod = await import('https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js');
  const authMod = await import('https://www.gstatic.com/firebasejs/10.12.5/firebase-auth.js');
  const fsMod = await import('https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore.js');
  const app = appMod.initializeApp(firebaseConfig);
  auth = authMod.getAuth(app);
  db = fsMod.getFirestore(app);
  modules = { ...authMod, ...fsMod };

  loginForm.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(loginForm);
    try {
      await modules.signInWithEmailAndPassword(auth, fd.get('email'), fd.get('password'));
      setMsg('Connexion réussie.', true);
    } catch(err) {
      console.error(err);
      setMsg('Connexion refusée. Vérifiez l’email, le mot de passe, Authentication et les domaines autorisés.');
    }
  });

  logoutBtn.addEventListener('click', () => modules.signOut(auth));

  document.querySelectorAll('.tab').forEach(btn => btn.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentCollection = btn.dataset.tab;
    loadCollection();
  }));

  recordsEl.addEventListener('click', handleRecordAction);
  seedBtn?.addEventListener('click', seedPages);
  seedSlotsBtn?.addEventListener('click', seedSlots);
  seedServicesBtn?.addEventListener('click', seedServices);

  modules.onAuthStateChanged(auth, user => {
    loginPanel.hidden = Boolean(user);
    dashboardPanel.hidden = !user;
    logoutBtn.hidden = !user;
    if (user) loadCollection();
    if (!user && unsub) unsub();
  });
}

function titleForRow(r){
  return r.nom || r.fullName || r.displayName || r.activity || r.serviceName || r.title || r.email || r.reservationCode || r.slug || r.id;
}

function orderFieldFor(collectionName){
  if (collectionName === 'slots' || collectionName === 'services') return ['order','asc'];
  if (collectionName === 'stats') return null;
  return ['createdAt','desc'];
}

async function loadCollection(){
  if (unsub) unsub();
  collectionTitle.textContent = labels[currentCollection] || currentCollection;
  recordsEl.innerHTML = '<p>Chargement…</p>';
  summaryEl.innerHTML = '';

  if (currentCollection === 'stats') {
    await renderStats();
    return;
  }

  const order = orderFieldFor(currentCollection);
  let q;
  try {
    q = order ? modules.query(modules.collection(db, currentCollection), modules.orderBy(order[0], order[1])) : modules.collection(db, currentCollection);
  } catch {
    q = modules.collection(db, currentCollection);
  }

  unsub = modules.onSnapshot(q, snap => {
    rows = snap.docs.map(d => ({ id: d.id, ...d.data() }));
    renderRows();
    renderSummary();
  }, err => {
    recordsEl.innerHTML = `<p class="msg">Lecture impossible : ${esc(err.message)}</p>`;
  });
}

function renderSummary(){
  if (!rows.length) { summaryEl.innerHTML = ''; return; }
  const total = rows.length;
  const nouveau = rows.filter(r => String(r.status || '').toLowerCase().includes('nou')).length;
  const traite = rows.filter(r => /trait|confirm|pay/i.test(String(r.status || r.paymentStatus || ''))).length;
  summaryEl.innerHTML = `<div class="admin-summary"><div class="metric"><strong>${total}</strong><span>Total</span></div><div class="metric"><strong>${nouveau}</strong><span>Nouveaux</span></div><div class="metric"><strong>${traite}</strong><span>Traités / confirmés</span></div></div>`;
}

function actionsFor(r){
  if (!['messages','reservations','payments','notifications','services','slots'].includes(currentCollection)) return '';
  const b = [];
  if (currentCollection === 'messages') {
    b.push(['traité','Marquer traité'], ['nouveau','Remettre nouveau']);
  }
  if (currentCollection === 'reservations') {
    b.push(['confirmée','Confirmer'], ['liste attente','Liste d’attente'], ['annulée','Annuler']);
  }
  if (currentCollection === 'payments') {
    b.push(['payé','Marquer payé'], ['à relancer','À relancer']);
  }
  if (currentCollection === 'notifications') {
    b.push(['lu','Marquer lu'], ['à traiter','À traiter']);
  }
  if (currentCollection === 'services' || currentCollection === 'slots') {
    b.push([r.active === false ? 'actif' : 'inactif', r.active === false ? 'Activer' : 'Désactiver']);
  }
  const updateButtons = b.map(([value,label]) => `<button data-action="status" data-id="${esc(r.id)}" data-value="${esc(value)}">${esc(label)}</button>`).join('');
  const deleteButton = `<button class="danger" data-action="delete" data-id="${esc(r.id)}">Supprimer</button>`;
  return `<div class="status-actions">${updateButtons}${deleteButton}</div>`;
}

function renderRows(){
  if (!rows.length){
    recordsEl.innerHTML = '<p>Aucune donnée pour cette collection.</p>';
    return;
  }

  recordsEl.innerHTML = rows.map(r => {
    const title = titleForRow(r);
    const entries = Object.entries(r).filter(([k]) => k !== 'id');
    const visibleEntries = entries.filter(([k]) => !technicalFields.has(k));
    const technicalEntries = entries.filter(([k]) => technicalFields.has(k));

    const visibleHtml = visibleEntries.map(([k,v]) =>
      `<dt>${esc(labelForField(k))}</dt><dd>${esc(formatValue(k, v))}</dd>`
    ).join('');

    const technicalHtml = technicalEntries.length ?
      `<details class="technical-details"><summary>Détails techniques</summary><dl>${technicalEntries.map(([k,v]) => `<dt>${esc(labelForField(k))}</dt><dd>${esc(formatValue(k, v))}</dd>`).join('')}<dt>ID du document</dt><dd>${esc(r.id)}</dd></dl></details>` :
      `<details class="technical-details"><summary>Détails techniques</summary><dl><dt>ID du document</dt><dd>${esc(r.id)}</dd></dl></details>`;

    return `<article class="record"><h3>${esc(title)}</h3><dl>${visibleHtml}</dl>${technicalHtml}${actionsFor(r)}</article>`;
  }).join('');
}

async function handleRecordAction(e){
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const id = btn.dataset.id;
  const action = btn.dataset.action;
  if (!id) return;
  if (action === 'delete') {
    if (!confirm('Supprimer ce document ?')) return;
    await modules.deleteDoc(modules.doc(db, currentCollection, id));
    return;
  }
  if (action === 'status') {
    const value = btn.dataset.value;
    const patch = { updatedAt: modules.serverTimestamp() };
    if (currentCollection === 'payments') patch.paymentStatus = value;
    else if (currentCollection === 'services' || currentCollection === 'slots') patch.active = value === 'actif';
    else patch.status = value;
    await modules.updateDoc(modules.doc(db, currentCollection, id), patch);
  }
}

exportBtn.addEventListener('click', () => {
  if (!rows.length) return;
  const keys = [...new Set(rows.flatMap(r => Object.keys(r)))];
  const csv = [
    keys.map(labelForField).join(','),
    ...rows.map(r => keys.map(k => '"' + String(formatValue(k, r[k])).replace(/"/g, '""') + '"').join(','))
  ].join('\n');
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `${currentCollection}-pssr.csv`;
  a.click();
  URL.revokeObjectURL(a.href);
});

async function seedPages(){
  seedBtn.disabled = true;
  seedBtn.textContent = 'Import en cours…';
  try{
    const response = await fetch('../data/pages-extracted.json');
    const pages = await response.json();
    let count = 0;
    for (const page of pages){
      const id = page.slug || `page-${page.id}`;
      await modules.setDoc(modules.doc(db, 'pages', id), {
        ...page,
        importedFrom: 'wordpress-export',
        updatedAt: modules.serverTimestamp()
      }, { merge: true });
      count++;
    }
    switchTab('pages');
    alert(`${count} pages importées ou mises à jour dans Firestore.`);
  }catch(err){
    console.error(err);
    alert('Import impossible. Vérifiez que vous êtes admin et que les règles Firestore sont installées.');
  }finally{
    seedBtn.disabled = false;
    seedBtn.textContent = 'Importer le contenu WordPress';
  }
}

async function seedSlots(){
  seedSlotsBtn.disabled = true;
  seedSlotsBtn.textContent = 'Import créneaux…';
  try{
    const response = await fetch('../data/slots-seed.json');
    const slots = await response.json();
    let count = 0;
    for (const slot of slots){
      await modules.setDoc(modules.doc(db, 'slots', slot.id), {
        ...slot,
        importedFrom: 'phase2a-seed',
        updatedAt: modules.serverTimestamp()
      }, { merge: true });
      count++;
    }
    switchTab('slots');
    alert(`${count} créneaux importés ou mis à jour dans Firestore.`);
  }catch(err){
    console.error(err);
    alert('Import impossible. Vérifiez que vous êtes admin et que les règles Firestore Phase 2A sont publiées.');
  }finally{
    seedSlotsBtn.disabled = false;
    seedSlotsBtn.textContent = 'Importer les créneaux officiels';
  }
}

async function seedServices(){
  seedServicesBtn.disabled = true;
  seedServicesBtn.textContent = 'Import services…';
  try{
    const response = await fetch('../data/services-seed.json');
    const services = await response.json();
    let count = 0;
    for (const service of services){
      await modules.setDoc(modules.doc(db, 'services', service.id), {
        ...service,
        updatedAt: modules.serverTimestamp()
      }, { merge: true });
      count++;
    }
    switchTab('services');
    alert(`${count} services importés ou mis à jour dans Firestore.`);
  }catch(err){
    console.error(err);
    alert('Import impossible. Vérifiez les règles Firestore V3.');
  }finally{
    seedServicesBtn.disabled = false;
    seedServicesBtn.textContent = 'Importer services & tarifs';
  }
}

function switchTab(tab){
  currentCollection = tab;
  document.querySelectorAll('.tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  loadCollection();
}

async function countCollection(name){
  try{
    const snap = await modules.getDocs(modules.collection(db, name));
    return snap.size;
  }catch { return '—'; }
}

async function renderStats(){
  const names = ['messages','reservations','users','slots','services','payments','emailLogs'];
  const counts = {};
  for (const name of names) counts[name] = await countCollection(name);
  summaryEl.innerHTML = '';
  recordsEl.innerHTML = `<div class="admin-summary">
    <div class="metric"><strong>${counts.messages}</strong><span>Messages</span></div>
    <div class="metric"><strong>${counts.reservations}</strong><span>Réservations</span></div>
    <div class="metric"><strong>${counts.users}</strong><span>Clients / membres</span></div>
    <div class="metric"><strong>${counts.slots}</strong><span>Créneaux</span></div>
    <div class="metric"><strong>${counts.services}</strong><span>Services</span></div>
    <div class="metric"><strong>${counts.payments}</strong><span>Paiements suivis</span></div>
    <div class="metric"><strong>${counts.emailLogs}</strong><span>Logs emails</span></div>
  </div><p class="payment-note"><strong>Note :</strong> les paiements en ligne ne sont pas activés dans cette version GitHub Pages + Firebase. L’onglet Paiements sert au suivi manuel ou à une future intégration Stripe/bancontact via backend sécurisé.</p>`;
  rows = [];
}

init().catch(err => {
  console.error(err);
  warning.hidden = false;
  warning.insertAdjacentHTML('beforeend', `<p class="msg">${esc(err.message)}</p>`);
});
