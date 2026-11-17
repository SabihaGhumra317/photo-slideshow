/* Handles drag/drop, local image previews and real XHR upload progress. */
(() => {
  const form = document.getElementById('uploadForm');
  if (!form) return;
  const input = document.getElementById('files');
  const zone = document.getElementById('dropZone');
  const previews = document.getElementById('previews');
  const result = document.getElementById('uploadResult');
  const progressBox = document.getElementById('progressBox');
  const fill = document.getElementById('progressFill');
  const output = document.getElementById('progressText');
  const button = form.querySelector('button[type=submit]');
  let files = [];
  let maxFiles = Number(form.dataset.maxFiles) === 3 ? 3 : 1;
  const allowed = new Set(['image/jpeg', 'image/png', 'image/webp']);
  function setFiles(picked) {
    const selected = Array.from(picked);
    if (selected.length > maxFiles) {
      files = [];
      input.value = '';
      previews.replaceChildren();
      result.textContent = `You can upload only ${maxFiles} photo${maxFiles === 1 ? '' : 's'} at a time.`;
      result.className = 'error-msg';
      return;
    }
    files = selected;
    result.textContent = '';
    previews.replaceChildren();
    for (const file of files) {
      const img = document.createElement('img');
      img.alt = `Preview of ${file.name}`;
      img.src = URL.createObjectURL(file);
      img.onload = () => URL.revokeObjectURL(img.src);
      previews.append(img);
    }
  }
  input.addEventListener('change', () => setFiles(input.files));
  ['dragenter', 'dragover'].forEach(type => zone.addEventListener(type, event => {
    event.preventDefault(); zone.classList.add('drag-active');
  }));
  ['dragleave', 'drop'].forEach(type => zone.addEventListener(type, event => {
    event.preventDefault(); zone.classList.remove('drag-active');
  }));
  zone.addEventListener('drop', event => setFiles(event.dataTransfer.files));
  zone.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); input.click(); } });
  function confetti() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    for (let n = 0; n < 30; n++) {
      const dot = document.createElement('i'); dot.className = 'confetti';
      dot.style.left = `${Math.random() * 100}vw`;
      dot.style.backgroundColor = ['#7c3aed','#06b6d4','#f472b6','#eab308'][n % 4];
      dot.style.animationDelay = `${Math.random() * 0.35}s`;
      document.body.append(dot); setTimeout(() => dot.remove(), 2400);
    }
  }
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!files.length) { result.textContent = 'Select at least one photo.'; return; }
    if (files.length > maxFiles || files.some(f => !allowed.has(f.type) || f.size > 5*1024*1024 || f.size===0)) {
      result.textContent = `Choose 1–${maxFiles} JPG/PNG/WEBP photo(s), each no larger than 5 MB.`; return;
    }
    const data = new FormData();
    data.append('csrf', form.querySelector('[name=csrf]').value);
    files.forEach(f => data.append('photos[]', f));
    const xhr = new XMLHttpRequest(); xhr.open('POST', form.action, true);
    button.disabled = true; progressBox.hidden = false; fill.style.width = '0%'; output.textContent = '0%';
    xhr.upload.onprogress = event => {
      if (!event.lengthComputable) return;
      const percent = Math.round(event.loaded / event.total * 100);
      fill.style.width = `${percent}%`; output.textContent = `${percent}%`;
    };
    xhr.onload = () => {
      button.disabled = false;
      let response;
      try { response = JSON.parse(xhr.responseText); } catch { response = {error: 'Server returned an unexpected response.'}; }
      if (xhr.status >= 200 && xhr.status < 300 && response.ok) {
        result.textContent = '✓ ' + response.message;
        result.className = 'success-msg';
        input.value = ''; files = []; previews.replaceChildren(); confetti();
        maxFiles = 1; // After the first successful request, every new request is one photo.
        input.multiple = false; form.dataset.maxFiles = '1';
        const hint = zone.querySelector('small');
        if (hint) hint.textContent = 'JPG / PNG / WEBP · 5 MB each · 1 photo per request';
      } else { result.textContent = response.error || 'Upload failed. Try again.'; result.className = 'error-msg'; }
    };
    xhr.onerror = () => { button.disabled = false; result.textContent = 'Network error. Please try again.'; result.className = 'error-msg'; };
    xhr.send(data);
  });
})();
