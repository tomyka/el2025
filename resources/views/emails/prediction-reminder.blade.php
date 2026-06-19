<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family:sans-serif;background:#f0f0f0;margin:0;padding:20px">
<div style="max-width:480px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">

    {{-- Header --}}
    <div style="background:#1a1a2e;padding:18px 24px;text-align:center">
        <span style="color:#fff;font-size:1rem;font-weight:700;letter-spacing:.5px">&#9917; SportBet</span>
    </div>

    {{-- Body --}}
    <div style="padding:28px 24px">
        <h2 style="margin:0 0 8px;font-size:1.05rem;color:#111">Nepamirškite prognozuoti!</h2>
        <p style="color:#555;font-size:.9rem;margin:0 0 20px">Artėja rungtynės — pateikite savo spėjimą laiku.</p>

        {{-- Match card --}}
        <div style="background:#f8f8f8;border-radius:8px;padding:16px 20px;text-align:center;margin-bottom:20px">
            <div style="display:flex;justify-content:center;align-items:center;gap:16px">
                <span style="font-weight:700;font-size:1rem">{{ $game->home_team->team }}</span>
                <span style="color:#aaa;font-size:.85rem">vs</span>
                <span style="font-weight:700;font-size:1rem">{{ $game->away_team->team }}</span>
            </div>
            <div style="color:#888;font-size:.82rem;margin-top:8px">
                &#128337;
                {{ \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d H:i') }} LT
            </div>
        </div>

        {{-- CTA button --}}
        <div style="text-align:center;margin-bottom:24px">
            <a href="{{ route('prediction.game.single', $game->id) }}"
               style="display:inline-block;background:#198754;color:#fff;padding:13px 36px;border-radius:6px;text-decoration:none;font-weight:700;font-size:.95rem">
                Prognozuoti
            </a>
        </div>

        <hr style="border:none;border-top:1px solid #eee;margin:0 0 16px">

        <p style="color:#bbb;font-size:.72rem;text-align:center;margin:0">
            <a href="{{ $unsubscribeUrl }}" style="color:#bbb;text-decoration:underline">
                Atsisakyti priminimų
            </a>
        </p>
    </div>
</div>
</body>
</html>
