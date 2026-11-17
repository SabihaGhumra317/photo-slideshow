/* Poll for approved photos every 2.5s. Approved newcomers interrupt immediately,
   then the returning visitor's other photos play, then the shared library. */
(() => {
  const layers = [document.getElementById('layerA'), document.getElementById('layerB')];
  const caption = document.getElementById('caption');
  const empty = document.getElementById('emptyState');
  let slides = [], priority = [], known = new Set(), initialized = false;
  let currentId = null, active = 0, duration = 8000, currentVisitor = null;
  let regularIndex = 0, timer = null, lastChange = 0;
  let pendingRequest = false;

  function preload(url) { const image = new Image(); image.src = url; }
  function schedule(ms) { clearTimeout(timer); timer = setTimeout(nextSlide, ms); }
  function display(slide) {
    if (!slide) { empty.hidden = false; layers.forEach(l => l.classList.remove('visible')); caption.textContent = ''; currentId = null; clearTimeout(timer); return; }
    empty.hidden = true; currentId = slide.id; currentVisitor = slide.visitor;
    const next = 1-active;
    layers[next].style.backgroundImage = `url(${JSON.stringify(slide.url)})`;
    layers[next].classList.add('visible'); layers[active].classList.remove('visible');
    active = next; caption.textContent = slide.name; lastChange = Date.now();
    const upcoming = priority[0] || slides[(regularIndex % (slides.length || 1))];
    if (upcoming) preload(upcoming.url);
    schedule(duration);
  }
  function nextSlide() {
    if (!slides.length) return display(null);
    while (priority.length && !slides.some(s => s.id === priority[0].id)) priority.shift();
    if (priority.length) return display(priority.shift());
    // Once an approved newcomer plays, its visitor's older photos play next.
    let selection;
    if (currentVisitor !== null) {
      const their = slides.filter(s => s.visitor === currentVisitor && s.id !== currentId);
      if (their.length) {
        const match = their.find(s => !playedForVisitor.has(s.id));
        if (match) { playedForVisitor.add(match.id); return display(match); }
      }
    }
    playedForVisitor.clear(); currentVisitor = null;
    selection = slides[regularIndex++ % slides.length];
    display(selection);
  }
  const playedForVisitor = new Set();
  async function poll() {
    if (pendingRequest) return;
    pendingRequest = true;
    try {
      const response = await fetch(`api/slides.php?since=${encodeURIComponent([...known].sort((a,b)=>b-a)[0] || 0)}`, {cache:'no-store'});
      if (!response.ok) throw new Error('Could not fetch slideshow');
      const data = await response.json();
      if (!Array.isArray(data.slides)) throw new Error('Invalid slide list');
      duration = Math.max(3000, Math.min(60000, Number(data.duration || 8)*1000));
      const incoming = data.slides;
      const incomingIds = new Set(incoming.map(s => s.id));
      // Recalculate complete state every poll, including deletions and hiding.
      const newSlides = initialized ? incoming.filter(s => !known.has(s.id)) : [];
      slides = incoming; known = incomingIds;
      priority = priority.filter(s => incomingIds.has(s.id));
      if (!incomingIds.has(currentId)) { currentId = null; playedForVisitor.clear(); }
      if (initialized && newSlides.length) {
        priority.push(...newSlides.sort((a,b) => a.approved_at.localeCompare(b.approved_at) || a.id-b.id));
        playedForVisitor.clear(); currentVisitor = null;
        // Interrupt the currently playing photo within the polling interval.
        nextSlide();
      } else if (!slides.length) display(null);
      else if (currentId === null) nextSlide();
      initialized = true;
    } catch (error) { console.warn('Slide polling:', error.message); }
    finally { pendingRequest = false; }
  }
  poll(); setInterval(poll, 2500);
})();
