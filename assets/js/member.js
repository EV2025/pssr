
import { getFirebase, esc, fmtDate, makeCode, levelLabel } from './firebase-portal.js';

const loginPanel = document.getElementById('login-panel');
const dashboard = document.getElementById('dashboard');
const participantPanel = document.getElementById('participant-panel');
const journeyPanel = document.getElementById('journey-panel');
const passportPanel = document.getElementById('passport-panel');
const reservationsPanel = document.getElementById('my-reservations');
const slotsPanel = document.getElementById('slots');
const gdprPanel = document.getElementById('gdpr-panel');
const loginForm = document.getElementById('login-form');
const loginMsg = document.getElementById('login-msg');
const logoutBtn = document.getElementById('logout');
const reservationList = document.getElementById('reservation-list');
const slotList = document.getElementById('slot-list');
const gdprForm = document.getElementById('gdpr-form');
const gdprMsg = document.getElementById('gdpr-msg');
let fb, currentUser, profile;

const steps = [
  {key:'CAND', title:'Candidature — dépôt & éligibilité', short:'Candidature', desc:'Présenter le dispositif, recueillir le consentement, vérifier les critères et planifier l’entrée.'},
  {key:'ARF', title:'Ateliers de Remise en Forme', short:'ARF', desc:'Sensibiliser, informer et remettre progressivement le corps en mouvement.'},
  {key:'BSS', title:'Bilan Socio-Sportif', short:'BSS', desc:'Faire le point sur la santé, l’activité, les freins, les motivations et le plan individuel.'},
  {key:'PDS', title:'Parcours Découverte Sportive', short:'PDS', desc:'Découvrir des disciplines, tester des activités et recueillir les retours d’expérience.'},
  {key:'APA', title:'Activité Physique Adaptée', short:'APA', desc:'Installer une pratique régulière, adaptée et progressive.'},
  {key:'CPE', title:'Concertation Partagée d’Engagement', short:'CPE', desc:'Coordonner sport, santé et social pour lever les obstacles.'},
  {key:'SRS', title:'Suivi Renforcé Solution', short:'SRS', desc:'Maintenir une pratique durable et ajuster l’accompagnement dans le temps.'}
];
function showLogin(text){ loginMsg.hidden=false; loginMsg.textContent=text; loginMsg.style.color='#9b2f2f'; }
function activeStepKey(){ return profile?.journeyLevel || profile?.currentStep || 'CAND'; }
function stepIndex(key){ return Math.max(0, steps.findIndex(s=>s.key===key)); }
function showGdpr(text, ok=false){ gdprMsg.hidden=false; gdprMsg.textContent=text; gdprMsg.style.color=ok?'#356b42':'#9b2f2f'; }

async function init(){
  fb = await getFirebase();
  loginForm.addEventListener('submit', async e=>{ e.preventDefault(); const fd=new FormData(loginForm); try{ await fb.signInWithEmailAndPassword(fb.auth, fd.get('email'), fd.get('password')); }catch(err){ showLogin('Connexion refusée. Vérifiez email/mot de passe.'); }});
  logoutBtn.addEventListener('click', ()=> fb.signOut(fb.auth));
  document.getElementById('print-passport').addEventListener('click', ()=> window.print());
  gdprForm.addEventListener('submit', sendGdprRequest);
  fb.onAuthStateChanged(fb.auth, async user=>{
    currentUser = user;
    const show = Boolean(user);
    loginPanel.hidden = show; dashboard.hidden = !show; participantPanel.hidden=!show; journeyPanel.hidden=!show; passportPanel.hidden=!show; reservationsPanel.hidden=!show; slotsPanel.hidden=!show; gdprPanel.hidden=!show; logoutBtn.hidden=!show;
    if (user) await loadAll();
  });
}
async function loadAll(){ await loadProfile(); await loadReservations(); await loadSlots(); }
async function loadProfile(){
  const snap = await fb.getDoc(fb.doc(fb.db,'users',currentUser.uid));
  profile = snap.exists() ? snap.data() : {displayName: currentUser.displayName || currentUser.email, email: currentUser.email, memberCode:'—', journeyLevel:'CAND', attendanceCount:0, badges:['Bienvenue PSSR']};
  const current = activeStepKey();
  document.getElementById('welcome').textContent = `Bienvenue ${profile.displayName || ''}`;
  document.getElementById('level').textContent = current;
  document.getElementById('attendance-count').textContent = profile.attendanceCount || 0;
  document.getElementById('member-code').textContent = profile.memberCode || '—';
  document.getElementById('pass-name').textContent = profile.displayName || '—';
  document.getElementById('pass-email').textContent = profile.email || currentUser.email || '—';
  document.getElementById('pass-code').textContent = profile.memberCode || '—';
  document.getElementById('pass-level').textContent = levelLabel(current) || current;
  document.getElementById('pass-att').textContent = profile.attendanceCount || 0;
  const badges = profile.badges || ['Bienvenue PSSR'];
  document.getElementById('badges').innerHTML = badges.map(b=>`<span class="badge">${esc(b)}</span>`).join('');
  renderParticipant();
  renderJourney();
}
function renderParticipant(){
  const rows = [
    ['Nom', profile.displayName || '—'],
    ['Email', profile.email || currentUser.email || '—'],
    ['Téléphone', profile.phone || '—'],
    ['Référent·e social·e', profile.referent || profile.socialReferent || 'À compléter'],
    ['Session', profile.session || 'À confirmer'],
    ['Statut', profile.status || 'Inscrit'],
    ['Modules souhaités', profile.modules || '—']
  ];
  document.getElementById('participant-kv').innerHTML = rows.map(([k,v])=>`<div><strong>${esc(k)}</strong></div><div>${esc(v)}</div>`).join('');
}
function renderJourney(){
  const current = activeStepKey();
  const idx = stepIndex(current);
  const stepWrap = document.getElementById('journey-steps');
  const panels = document.getElementById('journey-panels');
  stepWrap.innerHTML = steps.map((s,i)=>`<li class="pssr-step" role="tab" aria-selected="${i===idx}" data-step="${esc(s.key)}" data-active="${i===idx}"><strong>${esc(s.short)}</strong><br><span>${esc(s.title)}</span></li>`).join('');
  panels.innerHTML = steps.map((s,i)=>`<div class="pssr-panel ${i===idx?'active':''}" id="pssr-panel-${esc(s.key)}" role="tabpanel"><h4>${esc(s.title)}</h4><p>${esc(s.desc)}</p><div class="tag-row"><span>Prévue : ${esc(profile?.journeyDates?.[s.key]?.planned || '—')}</span><span>Réalisée : ${esc(profile?.journeyDates?.[s.key]?.done || '—')}</span></div></div>`).join('');
  stepWrap.querySelectorAll('.pssr-step').forEach(tile=>{
    tile.addEventListener('click',()=>{
      const key = tile.dataset.step;
      stepWrap.querySelectorAll('.pssr-step').forEach(x=>{x.dataset.active='false'; x.setAttribute('aria-selected','false');});
      tile.dataset.active='true'; tile.setAttribute('aria-selected','true');
      panels.querySelectorAll('.pssr-panel').forEach(p=>p.classList.remove('active'));
      document.getElementById('pssr-panel-'+key)?.classList.add('active');
    });
  });
}
async function loadReservations(){
  try{
    const q = fb.query(fb.collection(fb.db,'reservations'), fb.where('uid','==',currentUser.uid), fb.orderBy('createdAt','desc'));
    fb.onSnapshot(q, snap=>{
      const rows = snap.docs.map(d=>({id:d.id,...d.data()}));
      reservationList.innerHTML = rows.length ? rows.map(r=>`<article class="record"><h3>${esc(r.creneau || r.modules || r.slotTitle || r.reservationCode || r.id)}</h3><dl><dt>Référence</dt><dd>${esc(r.reservationCode || '—')}</dd><dt>Statut</dt><dd><span class="status-pill">${esc(r.status || 'nouvelle')}</span></dd><dt>Date</dt><dd>${esc(fmtDate(r.createdAt))}</dd><dt>Modules</dt><dd>${esc(r.modules || '—')}</dd><dt>Message</dt><dd>${esc(r.message || '')}</dd></dl></article>`).join('') : '<p>Aucune réservation membre.</p>';
    }, err=>{ reservationList.innerHTML = `<p class="msg">Lecture impossible : ${esc(err.message)}</p>`; });
  }catch(err){ reservationList.innerHTML = `<p class="msg">${esc(err.message)}</p>`; }
}
async function loadSlots(){
  const q = fb.query(fb.collection(fb.db,'slots'), fb.orderBy('order','asc'));
  fb.onSnapshot(q, snap=>{
    const rows = snap.docs.map(d=>({id:d.id,...d.data()})).filter(s=>s.active !== false);
    slotList.innerHTML = rows.length ? rows.map(s=>`<article class="slot-card"><h3>${esc(s.activity)}</h3><p class="slot-meta">${esc(s.day)} ${esc(s.start)}–${esc(s.end)} · ${esc(s.public || '')}</p><p>${esc(s.location || '')}</p><button class="btn small" data-book="${esc(s.id)}">Réserver</button></article>`).join('') : '<p>Aucun créneau importé. L’équipe PSSR confirmera les disponibilités après votre demande.</p>';
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
    modules: slot.activity,
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
async function sendGdprRequest(e){
  e.preventDefault();
  try{
    const fd = new FormData(gdprForm);
    await fb.addDoc(fb.collection(fb.db,'consents'), {
      uid: currentUser.uid,
      email: profile.email || currentUser.email || '',
      displayName: profile.displayName || '',
      type: 'gdpr_deletion_request',
      reason: String(fd.get('reason')||'').slice(0,1500),
      status: 'nouveau',
      createdAt: fb.serverTimestamp()
    });
    gdprForm.reset();
    showGdpr('Votre demande a bien été transmise à l’équipe PSSR.', true);
  }catch(err){ showGdpr('Impossible d’envoyer la demande : '+(err.message||'erreur Firebase')); }
}
init().catch(err=>{ console.error(err); showLogin('Erreur Firebase : '+err.message); });
