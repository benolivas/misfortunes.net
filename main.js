const SEEN_KEY      = 'mf_seen_v2';
const TS_KEY        = 'mf_seen_ts_v2';
const VISIT_KEY     = 'mf_last_visit_v2';
const EXPIRY_DAYS   = 7;
const FINAL_FORTUNE = "Congratulations. You&#8217;ve read every single misfortune.";

document.addEventListener("DOMContentLoaded", function () {
    const ageVerified = Cookies.get('ageVerified');
    if (ageVerified === "true") {
        document.getElementById("age-verify").classList.add("hidden");
    }

    // Only trigger fortune cycling when clicking the fortune container
    document.querySelector('.container').addEventListener('click', myFunction);
});

function overAge() {
    document.getElementById("age-verify").classList.add("hidden");
    Cookies.set('ageVerified', 'true', { expires: 1, path: '/' });
}

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}

// -- Format helpers (backward compat: handles both strings and objects) --------

function getText(f) {
    return typeof f === 'string' ? f : f.text;
}

function getAdded(f) {
    return typeof f === 'string' ? '2026-03-15' : f.added;
}

// -- Seen list helpers (v2: tracks by text, not index) -------------------------

function loadSeen() {
    try {
        const ts  = parseInt(localStorage.getItem(TS_KEY), 10);
        const age = (Date.now() - ts) / (1000 * 60 * 60 * 24);
        if (!ts || age > EXPIRY_DAYS) {
            clearSeen();
            return [];
        }
        return JSON.parse(localStorage.getItem(SEEN_KEY) || '[]');
    } catch(e) {
        return [];
    }
}

function saveSeen(seen) {
    localStorage.setItem(SEEN_KEY, JSON.stringify(seen));
    if (!localStorage.getItem(TS_KEY)) {
        localStorage.setItem(TS_KEY, Date.now().toString());
    }
}

function clearSeen() {
    localStorage.removeItem(SEEN_KEY);
    localStorage.removeItem(TS_KEY);
    localStorage.removeItem(VISIT_KEY);
}

// -- Render helper (strip <br> for large-format display) ----
function renderFortune(el, text) {
    el.innerHTML = text.replace(/<br\s*\/?>/gi, ' ').replace(/  +/g, ' ').trim();
}

// -- Fortune logic (four-bucket system) ----------------------------------------

function myFunction() {
    const el   = document.getElementById('fortuneText');
    const seen = loadSeen();

    // Record last visit date at start of this visit (before picking),
    // then update to today so next visit uses today as the cutoff.
    const today         = new Date().toISOString().split('T')[0];
    const lastVisitDate = localStorage.getItem(VISIT_KEY) || null;
    localStorage.setItem(VISIT_KEY, today);

    // BUCKET 1 — First ever visit: show the fortune with the latest added date.
    if (seen.length === 0 && !localStorage.getItem(TS_KEY)) {
        const newest = fortuneArray.reduce((a, b) =>
            getAdded(a) >= getAdded(b) ? a : b
        );
        const newestText = getText(newest);
        renderFortune(el, newestText);
        localStorage.setItem(TS_KEY, Date.now().toString());
        saveSeen([newestText]);
        return;
    }

    const seenSet = new Set(seen);

    // BUCKET 2 — New since last visit (added after lastVisitDate, not yet seen).
    // Only applies if we have a recorded last visit date.
    if (lastVisitDate) {
        const newUnseen = fortuneArray.filter(f =>
            getAdded(f) > lastVisitDate && !seenSet.has(getText(f))
        );
        if (newUnseen.length > 0) {
            const pick     = newUnseen[Math.floor(Math.random() * newUnseen.length)];
            const pickText = getText(pick);
            renderFortune(el, pickText);
            seen.push(pickText);
            saveSeen(seen);
            return;
        }
    }

    // BUCKET 3 — Unseen old fortunes.
    const oldUnseen = fortuneArray.filter(f => !seenSet.has(getText(f)));

    if (oldUnseen.length === 0) {
        // BUCKET 4 — All seen: show final fortune and reset.
        el.innerHTML = FINAL_FORTUNE;
        clearSeen();
        return;
    }

    const pick     = oldUnseen[Math.floor(Math.random() * oldUnseen.length)];
    const pickText = getText(pick);
    renderFortune(el, pickText);
    seen.push(pickText);
    saveSeen(seen);
}
