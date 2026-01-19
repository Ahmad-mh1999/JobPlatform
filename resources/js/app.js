import './bootstrap';

const apiBase = '/api';
function getToken() { try { return localStorage.getItem('token'); } catch { return null; } }
function authHeaders() {
  const t = getToken();
  return t ? { 'Authorization': 'Bearer ' + t, 'Content-Type': 'application/json' } : { 'Content-Type': 'application/json' };
}
async function fetchJSON(url, options = {}) {
  const res = await fetch(url, options);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'request_failed');
  return data;
}
function ensureRoot() {
  let root = document.getElementById('app');
  if (!root) {
    root = document.createElement('div');
    root.id = 'app';
    document.body.appendChild(root);
  }
  return root;
}
function renderDashboardShell(root) {
  root.innerHTML = `
  <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,sans-serif;background:#f6f7f9;min-height:100vh;margin:0;padding:24px">
    <div style="max-width:1100px;margin:0 auto">
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:16px">
        <div style="padding:12px 16px;font-weight:600;border-bottom:1px solid #e5e7eb">ملف الموظف</div>
        <div style="padding:16px">
          <div id="profileInfo" style="display:grid;gap:8px"></div>
          <div style="color:#6b7280;font-size:13px">يتم جلب بياناتك تلقائياً باستخدام رمز الدخول.</div>
        </div>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:16px">
        <div style="padding:12px 16px;font-weight:600;border-bottom:1px solid #e5e7eb">المهارات</div>
        <div style="padding:16px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="color:#6b7280;font-size:13px">القائمة الحالية</div>
            <button id="refreshSkills" style="padding:10px 14px;border:none;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer">تحديث</button>
          </div>
          <div id="skillsList" style="display:grid;gap:8px"></div>
          <hr />
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
              <label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">اختيار مهارة</label>
              <select id="skillSelect" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px"></select>
            </div>
            <div>
              <label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">المستوى</label>
              <select id="skillLevel" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px">
                <option value="beginner">مبتدئ</option>
                <option value="intermediate">متوسط</option>
                <option value="advanced">متقدم</option>
                <option value="expert">خبير</option>
              </select>
            </div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
            <button id="addSkillBtn" style="padding:10px 14px;border:none;border-radius:6px;background:#16a34a;color:#fff;cursor:pointer">إضافة مهارة</button>
          </div>
        </div>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:16px">
        <div style="padding:12px 16px;font-weight:600;border-bottom:1px solid #e5e7eb">التعليم</div>
        <div style="padding:16px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="color:#6b7280;font-size:13px">القائمة الحالية</div>
            <button id="refreshEducation" style="padding:10px 14px;border:none;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer">تحديث</button>
          </div>
          <div id="educationList" style="display:grid;gap:8px"></div>
          <hr />
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">الدرجة</label><input id="eduDegree" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">التخصص</label><input id="eduField" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">المؤسسة</label><input id="eduInstitution" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">الموقع</label><input id="eduLocation" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">تاريخ البداية</label><input id="eduStart" type="date" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">تاريخ النهاية</label><input id="eduEnd" type="date" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
          </div>
          <div style="margin-top:8px"><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">الوصف</label><textarea id="eduDesc" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px"></textarea></div>
          <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
            <button id="addEducationBtn" style="padding:10px 14px;border:none;border-radius:6px;background:#16a34a;color:#fff;cursor:pointer">إضافة تعليم</button>
          </div>
        </div>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:16px">
        <div style="padding:12px 16px;font-weight:600;border-bottom:1px solid #e5e7eb">الخبرات</div>
        <div style="padding:16px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="color:#6b7280;font-size:13px">القائمة الحالية</div>
            <button id="refreshExperiences" style="padding:10px 14px;border:none;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer">تحديث</button>
          </div>
          <div id="experiencesList" style="display:grid;gap:8px"></div>
          <hr />
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">المسمى الوظيفي</label><input id="expTitle" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">اسم الشركة</label><input id="expCompany" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">الموقع</label><input id="expLocation" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">تاريخ البداية</label><input id="expStart" type="date" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
            <div><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">تاريخ النهاية</label><input id="expEnd" type="date" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px" /></div>
          </div>
          <div style="margin-top:8px"><label style="display:block;font-size:13px;margin-bottom:4px;color:#374151">الوصف</label><textarea id="expDesc" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px"></textarea></div>
          <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
            <button id="addExperienceBtn" style="padding:10px 14px;border:none;border-radius:6px;background:#16a34a;color:#fff;cursor:pointer">إضافة خبرة</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  `;
}
async function loadProfile() {
  const el = document.getElementById('profileInfo');
  try {
    const resp = await fetchJSON(apiBase + '/profile/me', { headers: authHeaders() });
    const p = resp.data.profile;
    el.innerHTML = `
      <div style="padding:12px;border:1px solid #e5e7eb;border-radius:6px;background:#fafafa">الاسم: ${p.user?.name || ''}</div>
      <div style="padding:12px;border:1px solid #e5e7eb;border-radius:6px;background:#fafafa">المسمى الوظيفي: ${p.title || ''}</div>
      <div style="padding:12px;border:1px solid #e5e7eb;border-radius:6px;background:#fafafa">الموقع: ${p.location || ''}</div>
      <div style="padding:12px;border:1px solid #e5e7eb;border-radius:6px;background:#fafafa">سنوات الخبرة: ${p.years_of_experience ?? ''}</div>
    `;
    renderSkills(p.skills || []);
    renderEducation(p.education || []);
    renderExperiences(p.experiences || []);
  } catch (e) {
    el.innerHTML = `<div style="color:#6b7280;font-size:13px">تعذر جلب البروفايل. الرجاء التأكد من تسجيل الدخول.</div>`;
  }
}
function renderSkills(skills) {
  const list = document.getElementById('skillsList');
  if (!list) return;
  list.innerHTML = '';
  skills.forEach(s => {
    const item = document.createElement('div');
    item.style.padding = '12px';
    item.style.border = '1px solid #e5e7eb';
    item.style.borderRadius = '6px';
    item.style.background = '#fafafa';
    item.innerHTML = `
      <div style="display:flex;gap:8px;align-items:center"><strong>${s.name}</strong><span style="color:#6b7280;font-size:13px">المستوى: ${s.pivot?.level || ''}</span></div>
      <div style="display:flex;gap:8px;margin-top:8px">
        <button style="padding:10px 14px;border:none;border-radius:6px;background:#dc2626;color:#fff;cursor:pointer" onclick="window.__removeSkill(${s.id})">حذف</button>
      </div>
    `;
    list.appendChild(item);
  });
}
function renderEducation(edu) {
  const list = document.getElementById('educationList');
  if (!list) return;
  list.innerHTML = '';
  edu.forEach(e => {
    const item = document.createElement('div');
    item.style.padding = '12px';
    item.style.border = '1px solid #e5e7eb';
    item.style.borderRadius = '6px';
    item.style.background = '#fafafa';
    item.innerHTML = `
      <div style="display:flex;gap:8px;align-items:center"><strong>${e.degree}</strong><span style="color:#6b7280;font-size:13px">${e.field_of_study} - ${e.institution}</span></div>
      <div style="color:#6b7280;font-size:13px">${e.start_date || ''} - ${e.end_date || ''}</div>
    `;
    list.appendChild(item);
  });
}
function renderExperiences(exps) {
  const list = document.getElementById('experiencesList');
  if (!list) return;
  list.innerHTML = '';
  exps.forEach(e => {
    const item = document.createElement('div');
    item.style.padding = '12px';
    item.style.border = '1px solid #e5e7eb';
    item.style.borderRadius = '6px';
    item.style.background = '#fafafa';
    item.innerHTML = `
      <div style="display:flex;gap:8px;align-items:center"><strong>${e.job_title}</strong><span style="color:#6b7280;font-size:13px">${e.company_name}</span></div>
      <div style="color:#6b7280;font-size:13px">${e.start_date || ''} - ${e.end_date || ''}</div>
      <div style="color:#6b7280;font-size:13px">${e.description || ''}</div>
    `;
    list.appendChild(item);
  });
}
async function loadSkillsCatalog() {
  const sel = document.getElementById('skillSelect');
  if (!sel) return;
  const resp = await fetchJSON(apiBase + '/skills', { headers: authHeaders() });
  sel.innerHTML = '';
  resp.data.skills.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.id;
    opt.textContent = s.name;
    sel.appendChild(opt);
  });
}
async function addSkill() {
  const skill_id = document.getElementById('skillSelect').value;
  const level = document.getElementById('skillLevel').value;
  await fetchJSON(apiBase + '/profiles/addskills', {
    method: 'POST',
    headers: authHeaders(),
    body: JSON.stringify({ skill_id, level })
  });
  await loadProfile();
}
async function removeSkill(skillId) {
  await fetchJSON(apiBase + '/profiles/skills/' + skillId, {
    method: 'DELETE',
    headers: authHeaders()
  });
  await loadProfile();
}
async function addEducation() {
  const degree = document.getElementById('eduDegree').value;
  const field_of_study = document.getElementById('eduField').value;
  const institution = document.getElementById('eduInstitution').value;
  const location = document.getElementById('eduLocation').value;
  const start_date = document.getElementById('eduStart').value;
  const end_date = document.getElementById('eduEnd').value;
  const description = document.getElementById('eduDesc').value;
  await fetchJSON(apiBase + '/profiles/addeducation', {
    method: 'POST',
    headers: authHeaders(),
    body: JSON.stringify({ degree, field_of_study, institution, location, start_date, end_date, description })
  });
  await loadProfile();
}
async function addExperience() {
  const job_title = document.getElementById('expTitle').value;
  const company_name = document.getElementById('expCompany').value;
  const location = document.getElementById('expLocation').value;
  const start_date = document.getElementById('expStart').value;
  const end_date = document.getElementById('expEnd').value;
  const description = document.getElementById('expDesc').value;
  await fetchJSON(apiBase + '/profiles/addexperiences', {
    method: 'POST',
    headers: authHeaders(),
    body: JSON.stringify({ job_title, company_name, location, start_date, end_date, description })
  });
  await loadProfile();
}
function bindEvents() {
  const addSkillBtn = document.getElementById('addSkillBtn');
  const addEducationBtn = document.getElementById('addEducationBtn');
  const addExperienceBtn = document.getElementById('addExperienceBtn');
  const refreshSkills = document.getElementById('refreshSkills');
  const refreshEducation = document.getElementById('refreshEducation');
  const refreshExperiences = document.getElementById('refreshExperiences');
  if (addSkillBtn) addSkillBtn.addEventListener('click', addSkill);
  if (addEducationBtn) addEducationBtn.addEventListener('click', addEducation);
  if (addExperienceBtn) addExperienceBtn.addEventListener('click', addExperience);
  if (refreshSkills) refreshSkills.addEventListener('click', loadProfile);
  if (refreshEducation) refreshEducation.addEventListener('click', loadProfile);
  if (refreshExperiences) refreshExperiences.addEventListener('click', loadProfile);
  window.__removeSkill = removeSkill;
}
function route() {
  const hash = window.location.hash || '#/profile';
  const root = ensureRoot();
  if (hash === '#/dashboard') {
    window.location.hash = '#/profile';
    return;
  }
  if (hash === '#/profile') {
    renderDashboardShell(root);
    bindEvents();
    loadSkillsCatalog().catch(()=>{});
    loadProfile();
  } else {
    root.innerHTML = `
      <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,sans-serif;background:#f6f7f9;min-height:100vh;margin:0;padding:24px">
        <div style="max-width:800px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px">
          <h1 style="margin:0 0 12px">مرحباً</h1>
          <p style="color:#6b7280">استخدم الرابط #/profile لعرض صفحة البروفايل.</p>
        </div>
      </div>
    `;
  }
}
window.addEventListener('hashchange', route);
window.addEventListener('DOMContentLoaded', () => {
  if (getToken() && (!window.location.hash || window.location.hash === '#/' || window.location.hash === '#/dashboard')) {
    window.location.hash = '#/profile';
  }
  route();
});
