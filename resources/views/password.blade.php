<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siła hasła</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f1f5f9;
            color: #1e293b;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 28px;
        }

        h1 { margin: 0 0 20px; font-size: 1.4rem; text-align: center; }

        h2 { margin: 0 0 10px; font-size: 1rem; color: #475569; }

        section { margin-bottom: 22px; }

        label { display: block; margin-bottom: 6px; font-size: 0.85rem; color: #64748b; }

        input {
            width: 100%;
            padding: 10px 12px;
            font-size: 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }

        input:focus { outline: none; border-color: #6366f1; }

        .row { display: flex; gap: 8px; align-items: stretch; }

        .row input { flex: 1; }

        button {
            padding: 10px 16px;
            font-size: 0.95rem;
            border: none;
            border-radius: 8px;
            background: #6366f1;
            color: #ffffff;
            cursor: pointer;
        }

        button:hover { background: #4f46e5; }

        button.secondary { background: #e2e8f0; color: #334155; }

        button.secondary:hover { background: #cbd5e1; }

        .result {
            margin-top: 18px;
            padding: 16px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .result-strength { font-size: 1.1rem; font-weight: 700; margin: 0 0 4px; }

        .result-score { margin: 0 0 10px; color: #475569; }

        ul { margin: 0; padding-left: 20px; }

        li { margin-bottom: 4px; }

        .error {
            margin-top: 18px;
            padding: 16px;
            border-radius: 8px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        [hidden] { display: none; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Siła hasła</h1>

        <section>
            <h2>Sprawdź hasło</h2>
            <label for="password-input">Hasło</label>
            <div class="row">
                <input type="password" id="password-input" autocomplete="off" placeholder="Wpisz hasło">
                <button type="button" id="toggle-visibility" class="secondary">pokaż</button>
            </div>
            <div class="row" style="margin-top: 10px;">
                <button type="button" id="check-button">Sprawdź</button>
            </div>
        </section>

        <section>
            <h2>Wygeneruj hasło</h2>
            <label for="length-input">Długość (8–64)</label>
            <div class="row">
                <input type="number" id="length-input" min="8" max="64" value="16">
                <button type="button" id="generate-button">Wygeneruj</button>
            </div>
        </section>

        <div class="result" id="result-box" hidden>
            <p class="result-strength" id="result-strength"></p>
            <p class="result-score" id="result-score"></p>
            <ul id="failed-rules"></ul>
        </div>

        <div class="error" id="error-box" hidden></div>
    </main>

    <script>
        const STRENGTH = {
            weak: { label: 'Słabe', color: '#dc2626' },
            medium: { label: 'Średnie', color: '#f59e0b' },
            strong: { label: 'Silne', color: '#16a34a' },
        };

        const passwordInput = document.getElementById('password-input');
        const toggleButton = document.getElementById('toggle-visibility');
        const checkButton = document.getElementById('check-button');
        const lengthInput = document.getElementById('length-input');
        const generateButton = document.getElementById('generate-button');
        const resultBox = document.getElementById('result-box');
        const resultStrength = document.getElementById('result-strength');
        const resultScore = document.getElementById('result-score');
        const failedRules = document.getElementById('failed-rules');
        const errorBox = document.getElementById('error-box');

        function showResult(data) {
            errorBox.hidden = true;
            const info = STRENGTH[data.strength] || { label: data.strength, color: '#1e293b' };
            resultStrength.textContent = info.label;
            resultStrength.style.color = info.color;
            resultScore.textContent = 'Wynik: ' + data.score + '/5';
            failedRules.innerHTML = '';
            data.failed_rules.forEach(function (rule) {
                const item = document.createElement('li');
                item.textContent = rule.message;
                failedRules.appendChild(item);
            });
            resultBox.hidden = false;
        }

        function showErrors(payload) {
            resultBox.hidden = true;
            const messages = Object.values(payload.errors || {}).reduce(function (all, group) {
                return all.concat(group);
            }, []);
            const list = document.createElement('ul');
            messages.forEach(function (message) {
                const item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);
            });
            errorBox.innerHTML = '';
            errorBox.appendChild(list);
            errorBox.hidden = false;
        }

        async function checkPassword() {
            const response = await fetch('/api/password/check', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ password: passwordInput.value }),
            });
            const payload = await response.json();
            if (response.status === 422) {
                showErrors(payload);
                return;
            }
            showResult(payload);
        }

        async function generatePassword() {
            const length = encodeURIComponent(lengthInput.value);
            const response = await fetch('/api/password/generate?length=' + length, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            if (response.status === 422) {
                showErrors(payload);
                return;
            }
            passwordInput.value = payload.password;
            checkPassword();
        }

        toggleButton.addEventListener('click', function () {
            const hidden = passwordInput.type === 'password';
            passwordInput.type = hidden ? 'text' : 'password';
            toggleButton.textContent = hidden ? 'ukryj' : 'pokaż';
        });

        checkButton.addEventListener('click', checkPassword);
        generateButton.addEventListener('click', generatePassword);
    </script>
</body>
</html>
