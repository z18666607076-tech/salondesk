<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SalonDesk</title>
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
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Avenir Next", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, #f8e7df, transparent 32rem),
                var(--paper);
        }
        main { max-width: 980px; margin: 0 auto; padding: 48px 24px 72px; }
        header { display: flex; justify-content: space-between; gap: 16px; align-items: center; }
        .mark { font-family: Palatino, "Iowan Old Style", Georgia, serif; font-size: 1.4rem; letter-spacing: -0.03em; }
        nav a { color: var(--accent-dark); margin-left: 16px; text-decoration: none; font-weight: 600; }
        h1 { font-family: Palatino, "Iowan Old Style", Georgia, serif; font-size: clamp(2.6rem, 6vw, 4.6rem); line-height: 0.95; letter-spacing: -0.04em; margin: 56px 0 16px; max-width: 12ch; }
        .lede { font-size: 1.15rem; max-width: 42rem; color: var(--muted); }
        .actions { display: flex; gap: 12px; margin-top: 28px; flex-wrap: wrap; }
        .button { background: var(--accent); color: white; text-decoration: none; padding: 12px 16px; border-radius: 999px; font-weight: 650; }
        .button.secondary { background: transparent; color: var(--ink); border: 1px solid var(--line); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-top: 48px; }
        article { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 20px; }
        h2 { margin: 0 0 8px; font-size: 1.1rem; }
        p { margin: 0; color: var(--muted); }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--ink); }
        footer { margin-top: 40px; color: var(--muted); font-size: 0.92rem; }
    </style>
</head>
<body>
<main>
    <header>
        <div class="mark">SalonDesk</div>
        <nav>
            <a href="/book/glow-studio">Book</a>
            <a href="/admin">Admin</a>
            <a href="/docs/api">API docs</a>
        </nav>
    </header>
    <h1>Bookings for every chair in the salon.</h1>
    <p class="lede">A multi-tenant appointment platform for salons and service businesses. Each business is a tenant, with its own staff, services, Stripe subscription, and an assistant that turns “a haircut with Anna next Tuesday afternoon” into an open slot.</p>
    <div class="actions">
        <a class="button" href="/book/glow-studio">Book Glow Studio</a>
        <a class="button secondary" href="/admin">Open the admin panel</a>
    </div>
    <section class="grid">
        <article>
            <h2>Glow Studio</h2>
            <p>Pro trial, English, SGD, Asia/Singapore. Owner <code>maya@glow-studio.test</code>, receptionist <code>rina@glow-studio.test</code>, stylist <code>anna@glow-studio.test</code>. Password <code>password</code>. Booking page <a href="/book/glow-studio">/book/glow-studio</a>.</p>
        </article>
        <article>
            <h2>Northshore Nails</h2>
            <p>Basic plan, Simplified Chinese, CNY, Asia/Shanghai. Owner <code>lina@northshore-nails.test</code>. Password <code>password</code>. Booking page <a href="/book/northshore-nails">/book/northshore-nails</a>.</p>
        </article>
        <article>
            <h2>What to try</h2>
            <p>Book a Haircut on the public page, then open the shared calendar at <code>/admin/glow-studio/calendar</code> as Maya or Rina.</p>
        </article>
    </section>
    <footer>Local demo data is seeded by Docker. Stripe and OpenAI stay optional; both have fake drivers.</footer>
</main>
</body>
</html>
