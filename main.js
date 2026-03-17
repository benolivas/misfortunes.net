const SEEN_KEY      = 'mf_seen_v1';
const TS_KEY        = 'mf_seen_ts_v1';
const EXPIRY_DAYS   = 7;
const FINAL_FORTUNE = "Congratulations. You&#8217;ve read every single misfortune.";

document.addEventListener("DOMContentLoaded", function () {
    const ageVerified = Cookies.get('ageVerified');
    if (ageVerified === "true") {
        document.getElementById("age-verify").classList.add("hidden");
    }

    // Load the first fortune on page ready
    myFunction();

    // Subsequent fortunes on click
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

// -- Seen list helpers ------------------------------------

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
}

// -- Fortune logic ----------------------------------------

function myFunction() {
    const el   = document.getElementById('fortuneText');
    const seen = loadSeen();

    // First ever visit - show the newest fortune (last in array)
    if (seen.length === 0 && !localStorage.getItem(TS_KEY)) {
        const newestIndex = fortuneArray.length - 1;
        el.innerHTML = fortuneArray[newestIndex];
        saveSeen([newestIndex]);
        localStorage.setItem(TS_KEY, Date.now().toString());
        return;
    }

    // All fortunes seen - show final fortune then reset
    const unseen = fortuneArray
        .map((_, i) => i)
        .filter(i => !seen.includes(i));

    if (unseen.length === 0) {
        el.innerHTML = FINAL_FORTUNE;
        clearSeen();
        return;
    }

    // Pick randomly from unseen
    const pick = unseen[Math.floor(Math.random() * unseen.length)];
    el.innerHTML = fortuneArray[pick];
    seen.push(pick);
    saveSeen(seen);
}
