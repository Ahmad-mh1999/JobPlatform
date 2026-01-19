<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد الموظف</title>
    @vite(['resources/js/app.js','resources/css/app.css'])
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, sans-serif; margin: 0; background: #f6f7f9; }
        .container { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 16px; }
        .card-header { padding: 12px 16px; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        .card-body { padding: 16px; }
        .list { display: grid; gap: 8px; }
        .item { padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background: #fafafa; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        label { display: block; font-size: 13px; margin-bottom: 4px; color: #374151; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; }
        button { padding: 10px 14px; border: none; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer; }
        .muted { color: #6b7280; font-size: 13px; }
        .actions { display: flex; gap: 8px; }
        .danger { background: #dc2626; }
        .success { background: #16a34a; }
        .flex { display: flex; gap: 8px; align-items: center; }
        .section-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">ملف الموظف</div>
            <div class="card-body">
                <div id="profileInfo" class="list"></div>
                <div class="muted">يتم جلب بياناتك تلقائياً باستخدام رمز الدخول.</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">المهارات</div>
            <div class="card-body">
                <div class="section-actions">
                    <div class="muted">القائمة الحالية</div>
                    <button id="refreshSkills">تحديث</button>
                </div>
                <div id="skillsList" class="list"></div>
                <hr />
                <div class="grid-2">
                    <div>
                        <label>اختيار مهارة</label>
                        <select id="skillSelect"></select>
                    </div>
                    <div>
                        <label>المستوى</label>
                        <select id="skillLevel">
                            <option value="beginner">مبتدئ</option>
                            <option value="intermediate">متوسط</option>
                            <option value="advanced">متقدم</option>
                            <option value="expert">خبير</option>
                        </select>
                    </div>
                </div>
                <div class="flex">
                    <button id="addSkillBtn" class="success">إضافة مهارة</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">التعليم</div>
            <div class="card-body">
                <div class="section-actions">
                    <div class="muted">القائمة الحالية</div>
                    <button id="refreshEducation">تحديث</button>
                </div>
                <div id="educationList" class="list"></div>
                <hr />
                <div class="row">
                    <div>
                        <label>الدرجة</label>
                        <input id="eduDegree" />
                    </div>
                    <div>
                        <label>التخصص</label>
                        <input id="eduField" />
                    </div>
                    <div>
                        <label>المؤسسة</label>
                        <input id="eduInstitution" />
                    </div>
                    <div>
                        <label>الموقع</label>
                        <input id="eduLocation" />
                    </div>
                    <div>
                        <label>تاريخ البداية</label>
                        <input id="eduStart" type="date" />
                    </div>
                    <div>
                        <label>تاريخ النهاية</label>
                        <input id="eduEnd" type="date" />
                    </div>
                </div>
                <div>
                    <label>الوصف</label>
                    <textarea id="eduDesc"></textarea>
                </div>
                <div class="flex">
                    <button id="addEducationBtn" class="success">إضافة تعليم</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">الخبرات</div>
            <div class="card-body">
                <div class="section-actions">
                    <div class="muted">القائمة الحالية</div>
                    <button id="refreshExperiences">تحديث</button>
                </div>
                <div id="experiencesList" class="list"></div>
                <hr />
                <div class="row">
                    <div>
                        <label>المسمى الوظيفي</label>
                        <input id="expTitle" />
                    </div>
                    <div>
                        <label>اسم الشركة</label>
                        <input id="expCompany" />
                    </div>
                    <div>
                        <label>الموقع</label>
                        <input id="expLocation" />
                    </div>
                    <div>
                        <label>تاريخ البداية</label>
                        <input id="expStart" type="date" />
                    </div>
                    <div>
                        <label>تاريخ النهاية</label>
                        <input id="expEnd" type="date" />
                    </div>
                </div>
                <div>
                    <label>الوصف</label>
                    <textarea id="expDesc"></textarea>
                </div>
                <div class="flex">
                    <button id="addExperienceBtn" class="success">إضافة خبرة</button>
                </div>
            </div>
        </div>
    </div>

    <script>
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
        async function loadProfile() {
            const el = document.getElementById('profileInfo');
            try {
                const resp = await fetchJSON(apiBase + '/profile/me', { headers: authHeaders() });
                const p = resp.data.profile;
                el.innerHTML = `
                    <div class="item">الاسم: ${p.user?.name || ''}</div>
                    <div class="item">المسمى الوظيفي: ${p.title || ''}</div>
                    <div class="item">الموقع: ${p.location || ''}</div>
                    <div class="item">سنوات الخبرة: ${p.years_of_experience ?? ''}</div>
                `;
                renderSkills(p.skills || []);
                renderEducation(p.education || []);
                renderExperiences(p.experiences || []);
            } catch (e) {
                el.innerHTML = `<div class="muted">تعذر جلب البروفايل. الرجاء التأكد من تسجيل الدخول.</div>`;
            }
        }
        function renderSkills(skills) {
            const list = document.getElementById('skillsList');
            list.innerHTML = '';
            skills.forEach(s => {
                const item = document.createElement('div');
                item.className = 'item';
                item.innerHTML = `
                    <div class="flex"><strong>${s.name}</strong><span class="muted">المستوى: ${s.pivot?.level || ''}</span></div>
                    <div class="actions">
                        <button class="danger" data-id="${s.id}" onclick="removeSkill(${s.id})">حذف</button>
                    </div>
                `;
                list.appendChild(item);
            });
        }
        function renderEducation(edu) {
            const list = document.getElementById('educationList');
            list.innerHTML = '';
            edu.forEach(e => {
                const item = document.createElement('div');
                item.className = 'item';
                item.innerHTML = `
                    <div class="flex"><strong>${e.degree}</strong><span class="muted">${e.field_of_study} - ${e.institution}</span></div>
                    <div class="muted">${e.start_date || ''} - ${e.end_date || ''}</div>
                `;
                list.appendChild(item);
            });
        }
        function renderExperiences(exps) {
            const list = document.getElementById('experiencesList');
            list.innerHTML = '';
            exps.forEach(e => {
                const item = document.createElement('div');
                item.className = 'item';
                item.innerHTML = `
                    <div class="flex"><strong>${e.job_title}</strong><span class="muted">${e.company_name}</span></div>
                    <div class="muted">${e.start_date || ''} - ${e.end_date || ''}</div>
                    <div class="muted">${e.description || ''}</div>
                `;
                list.appendChild(item);
            });
        }
        async function loadSkillsCatalog() {
            const sel = document.getElementById('skillSelect');
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
        document.getElementById('addSkillBtn').addEventListener('click', addSkill);
        document.getElementById('addEducationBtn').addEventListener('click', addEducation);
        document.getElementById('addExperienceBtn').addEventListener('click', addExperience);
        document.getElementById('refreshSkills').addEventListener('click', loadProfile);
        document.getElementById('refreshEducation').addEventListener('click', loadProfile);
        document.getElementById('refreshExperiences').addEventListener('click', loadProfile);
        loadSkillsCatalog().catch(()=>{});
        loadProfile();
    </script>
</body>
</html>
