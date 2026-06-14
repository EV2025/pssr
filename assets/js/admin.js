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
const collectionTitle = document.getElementById('collection-title');

let auth, db;
let currentCollection = 'messages';
let rows = [];
let modules = {};
let unsub = null;

function setMsg(text, ok = false){
  loginMsg.hidden = false;
  loginMsg.textContent = text;
  loginMsg.style.color = ok ? '#356b42' : '#9b2f2f';
}

function fmtDate(v){
  try { return v?.toDate ? v.toDate().toLocaleString('fr-BE') : (v || ''); }
  catch { return ''; }
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

  seedBtn.addEventListener('click', seedPages);

  modules.onAuthStateChanged(auth, user => {
    loginPanel.hidden = Boolean(user);
    dashboardPanel.hidden = !user;
    logoutBtn.hidden = !user;
    if (user) loadCollection();
    if (!user && unsub) unsub();
  });
}

function loadCollection(){
  if (unsub) unsub();
  collectionTitle.textContent = currentCollection === 'messages' ? 'Messages reçus' : currentCollection === 'reservations' ? 'Réservations reçues' : 'Contenu des pages';
  recordsEl.innerHTML = '<p>Chargement…</p>';

  let q;
  try {
    q = modules.query(modules.collection(db, currentCollection), modules.orderBy('createdAt', 'desc'));
  } catch {
    q = modules.collection(db, currentCollection);
  }

  unsub = modules.onSnapshot(q, snap => {
    rows = snap.docs.map(d => ({ id: d.id, ...d.data() }));
    renderRows();
  }, err => {
    recordsEl.innerHTML = `<p class="msg">Lecture impossible : ${esc(err.message)}</p>`;
  });
}

function renderRows(){
  if (!rows.length){
    recordsEl.innerHTML = '<p>Aucune donnée pour cette collection.</p>';
    return;
  }

  recordsEl.innerHTML = rows.map(r => {
    const title = r.nom || r.title || r.email || r.slug || r.id;
    const entries = Object.entries(r).filter(([k]) => k !== 'id');
    return `<article class="record"><h3>${esc(title)}</h3><dl>${entries.map(([k,v]) => `<dt>${esc(k)}</dt><dd>${esc(k === 'createdAt' || k === 'updatedAt' ? fmtDate(v) : v)}</dd>`).join('')}</dl></article>`;
  }).join('');
}

exportBtn.addEventListener('click', () => {
  if (!rows.length) return;
  const keys = [...new Set(rows.flatMap(r => Object.keys(r)))];
  const csv = [
    keys.join(','),
    ...rows.map(r => keys.map(k => '"' + String(k === 'createdAt' || k === 'updatedAt' ? fmtDate(r[k]) : (r[k] ?? '')).replace(/"/g, '""') + '"').join(','))
  ].join('\\n');
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
    currentCollection = 'pages';
    document.querySelectorAll('.tab').forEach(b => b.classList.toggle('active', b.dataset.tab === 'pages'));
    loadCollection();
    alert(`${count} pages importées ou mises à jour dans Firestore.`);
  }catch(err){
    console.error(err);
    alert('Import impossible. Vérifiez que vous êtes admin et que les règles Firestore sont installées.');
  }finally{
    seedBtn.disabled = false;
    seedBtn.textContent = 'Importer le contenu WordPress';
  }
}

init().catch(err => {
  console.error(err);
  warning.hidden = false;
  warning.insertAdjacentHTML('beforeend', `<p class="msg">${esc(err.message)}</p>`);
});
