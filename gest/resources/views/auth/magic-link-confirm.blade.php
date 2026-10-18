<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Connexion ARENA GYM</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0b0f14; color: #f4f6f8; font-family: Arial, sans-serif; }
        main { width: min(28rem, calc(100% - 3rem)); padding: 2rem; border: 1px solid #303944; border-radius: 1rem; background: #151b22; text-align: center; }
        h1 { color: #ff7a00; }
        button { margin-top: 1rem; padding: .85rem 1.2rem; border: 0; border-radius: .6rem; background: #ff7a00; color: #111; font-weight: 700; cursor: pointer; }
        input { box-sizing: border-box; width: 100%; margin-top: .75rem; padding: .8rem; border: 1px solid #303944; border-radius: .6rem; background: #0b0f14; color: #f4f6f8; }
    </style>
</head>
<body>
    <main>
        <h1>ARENA GYM</h1>
        <p>Confirmez pour ouvrir votre espace administrateur.</p>
        <form method="POST" action="{{ $consumeUrl }}">
            @csrf
            @if ($requiresTwoFactor)
                <label for="two_factor_code">Code 2FA ou code de secours</label>
                <input id="two_factor_code" name="two_factor_code" type="text" autocomplete="one-time-code" required>
            @endif
            <button type="submit">Continuer vers ma salle</button>
        </form>
    </main>
</body>
</html>
