// Client-side helpers. The server (PHP) is always the authority for every rule.
document.addEventListener('DOMContentLoaded', function () {

    // ------------------------------------------------------------ 1. matrix rain background
    (function matrixRain() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var canvas = document.createElement('canvas');
        canvas.id = 'matrix';
        document.body.appendChild(canvas);
        var ctx = canvas.getContext('2d');
        if (!ctx) return;
        var size = 16, cols, drops;
        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            cols = Math.floor(canvas.width / size);
            drops = [];
            for (var i = 0; i < cols; i++) drops[i] = Math.random() * canvas.height / size;
        }
        resize();
        window.addEventListener('resize', resize);
        var chars = '01$#%&@ABCDEF<>/\\{}[]';
        setInterval(function () {
            ctx.fillStyle = 'rgba(4, 8, 11, 0.12)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#39ff88';
            ctx.font = size + 'px monospace';
            for (var i = 0; i < drops.length; i++) {
                var ch = chars.charAt(Math.floor(Math.random() * chars.length));
                ctx.fillText(ch, i * size, drops[i] * size);
                if (drops[i] * size > canvas.height && Math.random() > 0.975) drops[i] = 0;
                drops[i] += 0.6;
            }
        }, 60);
    })();

    // ------------------------------------------------------------ 2. trim plain text inputs on blur
    document.querySelectorAll('form input[type="text"]').forEach(function (input) {
        input.addEventListener('blur', function () { input.value = input.value.trim(); });
    });

    // ------------------------------------------------------------ 3. confirm dialog before risky forms
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
        });
    });

    // ------------------------------------------------------------ 4. lockout countdown
    var countdown = document.getElementById('countdown');
    if (countdown) {
        var remaining = parseInt(countdown.getAttribute('data-seconds'), 10) || 0;
        var timer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                var item = countdown.closest('li');
                if (item) item.textContent = 'Lockout expired. You may try again.';
                return;
            }
            countdown.textContent = remaining;
        }, 1000);
    }

    // ------------------------------------------------------------ 5. live password strength meter
    var pw = document.getElementById('password');
    var bar = document.getElementById('meter-bar');
    if (!pw || !bar || !window.PW_CONFIG) return;

    var userEl = document.getElementById('username');
    var confirmEl = document.getElementById('confirm_password');
    var labelEl = document.getElementById('meter-label');
    var scoreEl = document.getElementById('meter-score');
    var minLen = window.PW_CONFIG.min;
    var common = window.PW_CONFIG.common;

    function isCommon(p) {
        var lower = p.toLowerCase();
        var map = { '@': 'a', '4': 'a', '0': 'o', '3': 'e', '$': 's', '5': 's', '!': 'i', '7': 't' };
        function leet(oneAs) {
            return lower.replace(/[@40135$!7]/g, function (c) { return c === '1' ? oneAs : map[c]; });
        }
        var candidates = [lower, leet('l'), leet('i')];
        for (var i = 0; i < common.length; i++) {
            for (var j = 0; j < candidates.length; j++) {
                if (common[i].length >= 5 ? candidates[j].indexOf(common[i]) !== -1 : candidates[j] === common[i]) return true;
            }
        }
        return false;
    }

    function containsUser(p, u) {
        return u.length >= 3 && p.toLowerCase().indexOf(u.toLowerCase()) !== -1;
    }

    // Same algorithm as password_score() in includes/bootstrap.php
    function score(p, u) {
        var len = p.length;
        if (len === 0) return { score: 0, label: '—', cls: '' };
        var s = Math.min(len, 20) * 3;
        if (/[A-Z]/.test(p)) s += 5;
        if (/[a-z]/.test(p)) s += 5;
        if (/[0-9]/.test(p)) s += 5;
        if (/[^A-Za-z0-9]/.test(p)) s += 10;
        if (len >= 16) s += 15;
        if (containsUser(p, u)) s -= 30;
        if (/(.)\1\1/.test(p)) s -= 10;
        if (isCommon(p)) s = Math.min(s, 15);
        s = Math.max(0, Math.min(100, s));
        var label = s < 40 ? 'Weak' : (s < 60 ? 'Fair' : (s < 80 ? 'Strong' : 'Excellent'));
        var cls = { Weak: 'bad', Fair: 'warn', Strong: 'cyan', Excellent: 'ok' }[label];
        return { score: s, label: label, cls: cls };
    }

    function setRule(id, ok) {
        var el = document.getElementById(id);
        if (el) el.classList.toggle('ok', !!ok);
    }

    function update() {
        var p = pw.value;
        var u = userEl ? userEl.value.trim() : '';
        var r = score(p, u);
        bar.style.width = r.score + '%';
        bar.className = 'meter-bar ' + r.cls;
        labelEl.textContent = r.label;
        labelEl.className = r.cls;
        scoreEl.textContent = r.score;

        setRule('rule-length', p.length >= minLen);
        setRule('rule-upper', /[A-Z]/.test(p));
        setRule('rule-lower', /[a-z]/.test(p));
        setRule('rule-digit', /[0-9]/.test(p));
        setRule('rule-symbol', /[^A-Za-z0-9]/.test(p));
        setRule('rule-user', p.length > 0 && !containsUser(p, u));
        setRule('rule-common', p.length > 0 && !isCommon(p));
        setRule('rule-match', p.length > 0 && confirmEl && confirmEl.value === p);
    }

    pw.addEventListener('input', update);
    if (userEl) userEl.addEventListener('input', update);
    if (confirmEl) confirmEl.addEventListener('input', update);

    // Show / hide the password while typing
    var toggle = document.getElementById('toggle-pw');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var showing = pw.type === 'text';
            pw.type = showing ? 'password' : 'text';
            toggle.textContent = showing ? 'Show' : 'Hide';
        });
    }
    update();
});
