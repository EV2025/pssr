
import { getFirebase, clean, makeCode, levelFromAttendance } from './firebase-portal.js';

const form = document.getElementById('register-form');
const msg = document.getElementById('register-msg');
function show(text, ok=false){ msg.hidden=false; msg.textContent=text; msg.style.color=ok?'#356b42':'#9b2f2f'; msg.scrollIntoView({behavior:'smooth',block:'nearest'}); }

form.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(form);
  const password = String(fd.get('password')||'');
  if (password !== String(fd.get('password2')||'')) return show('Les mots de passe ne correspondent pas.');
  const data = {
    displayName: clean(fd.get('displayName'),120),
    email: clean(fd.get('email'),180).toLowerCase(),
    phone: clean(fd.get('phone'),60),
    ageRange: clean(fd.get('ageRange'),60),
    situation: clean(fd.get('situation'),180),
    activityLevel: clean(fd.get('activityLevel'),80),
    needs: clean(fd.get('needs'),1500),
    goals: clean(fd.get('goals'),1500),
    role: 'member',
    memberCode: makeCode('MEMBRE'),
    journeyLevel: levelFromAttendance(0),
    attendanceCount: 0
  };
  if (!data.displayName || !/^\S+@\S+\.\S+$/.test(data.email)) return show('Nom ou email invalide.');
  const btn = form.querySelector('button'); btn.disabled = true;
  try{
    const fb = await getFirebase();
    const cred = await fb.createUserWithEmailAndPassword(fb.auth, data.email, password);
    await fb.updateProfile(cred.user, { displayName: data.displayName });
    await fb.setDoc(fb.doc(fb.db, 'users', cred.user.uid), {
      ...data,
      uid: cred.user.uid,
      createdAt: fb.serverTimestamp(),
      updatedAt: fb.serverTimestamp()
    });
    await fb.setDoc(fb.doc(fb.db, 'passports', cred.user.uid), {
      uid: cred.user.uid,
      displayName: data.displayName,
      email: data.email,
      memberCode: data.memberCode,
      journeyLevel: data.journeyLevel,
      attendanceCount: 0,
      badges: ['Bienvenue PSSR'],
      updatedAt: fb.serverTimestamp()
    }, { merge:true });
    show(`Compte créé. Votre code membre est ${data.memberCode}. Redirection vers l’espace membre…`, true);
    setTimeout(()=> location.href='./member/dashboard.html', 1200);
  }catch(err){
    console.error(err);
    show(err.code === 'auth/email-already-in-use' ? 'Un compte existe déjà avec cet email.' : 'Inscription impossible : ' + (err.message || 'erreur Firebase'));
  }finally{ btn.disabled = false; }
});
