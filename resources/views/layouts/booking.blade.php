<!DOCTYPE html>
<html lang="{{ $htmlLang ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $salonName ?? 'SalonDesk' }} · {{ __('booking.page_title') }}</title>
    @livewireStyles
    <style>
        :root {
            color-scheme: light;
            --ink: #241c19;
            --muted: #6d5e57;
            --paper: #f6f1ea;
            --card: #fffaf6;
            --line: #e4d8ce;
            --accent: #9c3d4a;
            --accent-dark: #742c36;
            --accent-soft: #f8e7df;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Avenir Next", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, #f8e7df, transparent 28rem),
                var(--paper);
        }
        a { color: var(--accent-dark); }
        .wrap { max-width: 920px; margin: 0 auto; padding: 28px 16px 72px; }
        .top { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; }
        .mark { font-family: Palatino, "Iowan Old Style", Georgia, serif; font-size: 1.25rem; letter-spacing: -0.03em; text-decoration: none; color: var(--ink); }
        .salon { margin: 28px 0 0; font-family: Palatino, "Iowan Old Style", Georgia, serif; font-size: clamp(2.2rem, 6vw, 3.6rem); line-height: 0.95; letter-spacing: -0.04em; }
        .meta { color: var(--muted); margin: 8px 0 0; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 16px; margin-top: 16px; }
        h2 { margin: 0 0 12px; font-size: 1.05rem; }
        .services, .slots { display: grid; gap: 10px; }
        .services { grid-template-columns: 1fr; }
        @media (min-width: 720px) {
            .services { grid-template-columns: 1fr 1fr; }
            .slots { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        button, .choice {
            font: inherit;
            text-align: left;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: white;
            color: var(--ink);
            padding: 12px 14px;
            min-height: 44px;
            cursor: pointer;
        }
        button.is-selected, .choice.is-selected { border-color: var(--accent); background: var(--accent-soft); }
        .choice strong, button strong { display: block; }
        .muted { color: var(--muted); font-size: 0.92rem; }
        label { display: block; font-size: 0.85rem; font-weight: 650; margin: 12px 0 6px; }
        input, select, textarea {
            width: 100%;
            font: inherit;
            font-size: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: white;
            color: var(--ink);
        }
        textarea { min-height: 88px; resize: vertical; }
        .primary {
            margin-top: 16px;
            background: var(--accent);
            color: white;
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 700;
            width: 100%;
        }
        @media (min-width: 720px) {
            .primary { width: auto; }
        }
        .error { color: var(--accent-dark); font-size: 0.92rem; margin: 8px 0 0; }
        .proposal { background: var(--accent-soft); border-radius: 14px; padding: 12px 14px; margin-top: 12px; }
        .staff-name { margin: 14px 0 8px; font-size: 0.95rem; }
        .row { display: grid; gap: 12px; }
        @media (min-width: 720px) {
            .row.two { grid-template-columns: 1fr 1fr; }
        }
        footer { margin-top: 28px; color: var(--muted); font-size: 0.85rem; }
    </style>
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
