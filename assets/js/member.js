
import { getFirebase, esc, fmtDate, makeCode, levelLabel } from './firebase-portal.js';

const loginPanel = document.getElementById('login-panel');
const dashboard = document.getElementById('dashboard');
const passportPanel = document.getElementById('passport-panel');
const reservationsPanel = document.getElementById('my-reservations');
const slotsPanel = document.getElementById('slots');
const loginForm = document.getElementById('login-form');
const loginMsg = document.getElementById('login-msg');
const logoutBtn = document.getElementById('logout');
const reservationList = document.getElementById('reservation-list');
const slotList = document.getElementById('slot-list');
let fb, currentUser, profile;
function showLogin(text){ loginMsg.hidden=false; loginMsg.textContent=text; loginMsg.style.color='#9b2f2f'; }

async function init(){
  fb = await getFirebase();
  loginForm.addEventListener('submit', async e=>{ e.preventDefault(); const fd=new FormData(loginForm); try{ await fb.signInWithEmailAndPassword(fb.auth, fd.get('email'), fd.get('password')); }catch(err){ showLogin('Connexion refusée. Vérifiez email/mot de passe.'); }});
  logoutBtn.addEventListener('click', ()=> fb.signOut(fb.auth));
  document.getElementById('print-passport').addEventListener('click', ()=> window.print());
  fb.onAuthStateChanged(fb.auth, async user=>{
    currentUser = user;
    loginPanel.hidden = Boolean(user); dashboard.hidden = !user; passportPanel.hidden=!user; reservationsPanel.hidden=!user; slotsPanel.hidden=!user; logoutBtn.hidden=!user;
    if (user) await loadAll();
  });
}
async function loadAll(){ await loadProfile(); await loadReservations(); await loadSlots(); }
async function loadProfile(){
  const snap = await fb.getDoc(fb.doc(fb.db,'users',currentUser.uid));
  profile = snap.exists() ? snap.data() : {displayName: currentUser.displayName || currentUser.email, email: currentUser.email, memberCode:'—', journeyLevel:'ARF', attendanceCount:0, badges:['Bienvenue PSSR']};
  document.getElementById('welcome').textContent = `Bienvenue ${profile.displayName || ''}`;
  document.getElementById('level').textContent = profile.journeyLevel || 'ARF';
  document.getElementById('attendance-count').textContent = profile.attendanceCount || 0;
  document.getElementById('member-code').textContent = profile.memberCode || '—';
  document.getElementById('pass-name').textContent = profile.displayName || '—';
  document.getElementById('pass-email').textContent = profile.email || currentUser.email || '—';
  document.getElementById('pass-code').textContent = profile.memberCode || '—';
  document.getElementById('pass-level').textContent = levelLabel(profile.journeyLevel || 'ARF');
  document.getElementById('pass-att').textContent = profile.attendanceCount || 0;
  const badges = profile.badges || ['Bienvenue PSSR'];
  document.getElementById('badges').innerHTML = badges.map(b=>`<span class="badge">${esc(b)}</span>`).join('');
}
async function loadReservations(){
  try{
    const q = fb.query(fb.collection(fb.db,'reservations'), fb.where('uid','==',currentUser.uid), fb.orderBy('createdAt','desc'));
    fb.onSnapshot(q, snap=>{
      const rows = snap.docs.map(d=>({id:d.id,...d.data()}));
      reservationList.innerHTML = rows.length ? rows.map(r=>`<article class="record"><h3>${esc(r.creneau || r.slotTitle || r.reservationCode || r.id)}</h3><dl><dt>Référence</dt><dd>${esc(r.reservationCode || '—')}</dd><dt>Statut</dt><dd><span class="status-pill">${esc(r.status || 'nouvelle')}</span></dd><dt>Date</dt><dd>${esc(fmtDate(r.createdAt))}</dd><dt>Message</dt><dd>${esc(r.message || '')}</dd></dl></article>`).join('') : '<p>Aucune réservation membre.</p>';
    }, err=>{ reservationList.innerHTML = `<p class="msg">Lecture impossible : ${esc(err.message)}</p>`; });
  }catch(err){ reservationList.innerHTML = `<p class="msg">${esc(err.message)}</p>`; }
}
async function loadSlots(){
  const q = fb.query(fb.collection(fb.db,'slots'), fb.orderBy('order','asc'));
  fb.onSnapshot(q, snap=>{
    const rows = snap.docs.map(d=>({id:d.id,...d.data()})).filter(s=>s.active !== false);
    slotList.innerHTML = rows.length ? rows.map(s=>`<article class="slot-card"><h3>${esc(s.activity)}</h3><p class="slot-meta">${esc(s.day)} ${esc(s.start)}–${esc(s.end)} · ${esc(s.public || '')}</p><p>${esc(s.location || '')}</p><button class="btn small" data-book="${esc(s.id)}">Réserver</button></article>`).join('') : '<p>Aucun créneau importé. L’admin doit cliquer sur “Importer les créneaux officiels”.</p>';
    slotList.querySelectorAll('[data-book]').forEach(btn=>btn.addEventListener('click',()=>book(rows.find(s=>s.id===btn.dataset.book))));
  }, err=>{ slotList.innerHTML = `<p class="msg">Lecture impossible : ${esc(err.message)}</p>`; });
}
async function book(slot){
  if (!slot) return;
  const code = makeCode('PSSR');
  await fb.addDoc(fb.collection(fb.db,'reservations'), {
    uid: currentUser.uid,
    nom: profile.displayName || currentUser.displayName || '',
    email: profile.email || currentUser.email || '',
    tel: profile.phone || '',
    creneau: `${slot.day} ${slot.start}–${slot.end} — ${slot.activity}`,
    slotId: slot.id,
    slotTitle: slot.activity,
    reservationCode: code,
    status: 'nouvelle',
    source: '/member/dashboard.html',
    createdAt: fb.serverTimestamp()
  });
  await fb.addDoc(fb.collection(fb.db,'emailLogs'), {type:'reservation-receipt',status:'to_send',email:profile.email || currentUser.email || '',reservationCode:code,createdAt:fb.serverTimestamp()});
  alert(`Réservation enregistrée. Référence : ${code}`);
}
init().catch(err=>{ console.error(err); showLogin('Erreur Firebase : '+err.message); });
