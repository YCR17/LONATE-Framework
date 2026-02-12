<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aksa')</title>
    <style>
        :root{
            --bg: #0f1724;
            --surface: #0b1220;
            --muted: #9aa4b2;
            --accent: #4f46e5;
            --glass: rgba(255,255,255,0.04);
            --text: #e6eef8;
            --card: #0b1220;
            --radius: 12px;
            --shadow: 0 6px 30px rgba(2,6,23,0.6);
        }

        /* light theme */
        :root.light{
            --bg: #f6f8fb;
            --surface: #ffffff;
            --muted: #6b7280;
            --accent: #4f46e5;
            --glass: rgba(2,6,23,0.03);
            --text: #0b1220;
            --card: #ffffff;
            --shadow: 0 8px 30px rgba(2,6,23,0.08);
        }

        html,body{height:100%;}
        *{box-sizing:border-box}
        body{font-family:Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:linear-gradient(180deg, var(--bg), color-mix(in srgb, var(--bg) 85%, transparent)); color:var(--text); margin:0; padding:28px; transition:background .25s, color .25s}
        .app{max-width:980px;margin:36px auto;padding:32px;border-radius:16px;background:linear-gradient(180deg, rgba(255,255,255,0.02), transparent);box-shadow:var(--shadow);backdrop-filter: blur(6px);}
        header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
        .brand{display:flex;gap:14px;align-items:center}
        .logo{width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:600}
        h1{font-size:20px;margin:0}
        p.lead{margin:0;color:var(--muted)}
        .actions{display:flex;gap:10px;align-items:center}
        .btn{background:transparent;color:var(--accent);border:1px solid color-mix(in srgb,var(--accent) 20%, transparent);padding:8px 12px;border-radius:10px;text-decoration:none;font-weight:600}
        .btn.primary{background:linear-gradient(90deg,var(--accent),#7c3aed);color:white;border:none;box-shadow:0 6px 20px rgba(79,70,229,0.18)}
        main{padding:12px 0}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px}
        .card{background:var(--card);border-radius:12px;padding:18px;box-shadow:var(--shadow);border:1px solid var(--glass)}
        .card h3{margin:0 0 8px 0;font-size:15px;color:var(--text)}
        .card p{margin:0;color:var(--muted);font-size:13px}
        .footer{margin-top:24px;color:var(--muted);font-size:13px;display:flex;justify-content:space-between;align-items:center}
        .toggle{background:transparent;border:1px solid color-mix(in srgb,var(--muted) 16%, transparent);padding:6px 8px;border-radius:8px;color:var(--muted);cursor:pointer}

        /* prefer dark by default, support OS-level preference */
        @media (prefers-color-scheme: light){:root:not(.light){}}
    </style>
    <script>
        // theme: follow OS by default, allow toggle
        (function(){
            const root = document.documentElement;
            const stored = localStorage.getItem('ml_theme');
            if(stored === 'light') root.classList.add('light');
            if(stored === 'dark') root.classList.remove('light');
            window.toggleTheme = function(){
                const isLight = root.classList.toggle('light');
                localStorage.setItem('ml_theme', isLight ? 'light' : 'dark');
            }
        })();
    </script>
</head>
<body>
    <div class="app">
        <header>
            <div class="brand">
                <div class="logo">ML</div>
                <div>
                    <h1>Aksa</h1>
                    <p class="lead">A small, modern PHP MVC framework</p>
                </div>
            </div>
            <div class="actions">
                <button class="toggle" onclick="toggleTheme()">Toggle theme</button>
                <a class="btn" href="/docs">Docs</a>
                <a class="btn primary" href="/users">Users</a>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <div class="footer">
            <div>Simple • Fast • Minimal</div>
            <div>v1.0</div>
        </div>
    </div>
</body>
</html>