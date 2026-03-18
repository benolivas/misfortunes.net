# misfortunes.net

A fortune cookie site with an attitude. Prediction engine aesthetic, surveillance data vibes, 1,003 curated fortunes. Built with vanilla HTML/CSS/JS, no frameworks.

## Live site

[misfortunes.net](https://misfortunes.net)

## Structure

```
misfortunes.net/
├── index.html          # Main page — all logic and styles inline, self-contained
├── fortuneArray.js     # All fortunes as {text, added} objects
├── ip.php              # Returns visitor IP + geolocation (ip-api.com)
├── style.css           # Legacy — no longer used, safe to ignore
├── .htaccess           # CORS headers, cache rules, security
├── images/
│   └── fortune-cookie.png
└── editor/
    ├── index.html      # Fortune editor UI
    ├── auth.php        # Login handler, reads config.php
    ├── push.php        # Writes fortuneArray.js directly to server
    └── config.php      # Editor password — NOT in repo, manual deploy only
```

## Main site

The homepage is a single self-contained `index.html`. No external JS dependencies except js-cookie (CDN) and the IBM Plex Mono font. All fortune logic, styling, and UI are inline.

Design: Swiss grid, IBM Plex Mono, Helvetica Neue for fortune text. Parchment background, dark header/footer matching the editor. Signal history graph at the bottom.

Side panel shows:
- Visitor IP (masked) and location via `ip.php` + ip-api.com
- Behavioural profile inferred from localStorage (time of day, visit count, fortunes seen)
- Data broker count and corpus stats

## Fortune display logic

- First visit: shows the last fortune in the array (newest added)
- Subsequent clicks: serves new-since-last-visit fortunes first, then random unseen — no repeats
- Seen list tracked by fortune text in `localStorage` under `mf_seen_v2`, expires after 7 days
- Fortune number shown is the actual array index, not a seen count
- After all 1,003 fortunes seen: final message, then full reset

## Fortune format

```javascript
{text: 'Your procrastination skills are unmatched.', added: '2026-03-15'}
```

All fortunes are objects with `text` and `added` (ISO date). No `<br>` tags — line breaks are handled by the layout.

## Editor

Password-protected at `/editor/`. 

- Fetches live `fortuneArray.js` on login, always in sync with server
- Live preview matches main site typography (Helvetica Neue 800)
- Add, edit, delete fortunes with date stamping
- Filter by fit length or sort by newest
- Push to live — writes directly to `fortuneArray.js` on the server (bypasses git)

## Deploy

GitHub Actions deploys on every push to `main` via SSH to Dreamhost. The workflow uses `git reset --hard origin/main` to force-sync, since `fortuneArray.js` is managed by the editor directly on the server and would otherwise block a standard `git pull`.

Secrets required: `SSH_HOST`, `SSH_USERNAME`, `SSH_PRIVATE_KEY`, `SSH_PATH`.

Note: `fortuneArray.js` changes made via the editor are not automatically committed to GitHub. Sync manually when needed:

```bash
cd ~/websites/misfortunes.net
git add fortuneArray.js
git commit -m "sync fortuneArray"
git push origin main
```

## Local setup

```bash
git clone https://github.com/benolivas/misfortunes.net.git
cd misfortunes.net
```

Create `editor/config.php` manually (not in repo):

```php
<?php
define('EDITOR_PASSWORD', 'your_password_here');
define('TOKEN_EXPIRY', 28800);
```

## Hosting

Dreamhost shared hosting, Apache, PHP 8.3.

## Not in this repo

- `editor/config.php` — editor password, lives on server and local machines only
- `editor/.token` — session token generated on login
