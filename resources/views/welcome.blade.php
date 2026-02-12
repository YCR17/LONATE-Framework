@extends('layouts.app')

@section('content')
    <section style="display:flex;flex-direction:column;gap:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <h2 style="margin:0;font-size:18px;color:var(--text)">MiniLaravel</h2>
                <p style="margin-top:6px;color:var(--muted)">Framework MVC PHP yang ringan dan mudah digunakan</p>
            </div>
            <div style="text-align:right;color:var(--muted);font-size:13px">Minimal • Modern</div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Routing</h3>
                <p>Sistem routing kecil namun fleksibel — grup, middleware, prefix.</p>
            </div>
            <div class="card">
                <h3>Views</h3>
                <p>Blade-like templating yang sederhana dan cache-friendly.</p>
            </div>
            <div class="card">
                <h3>Database</h3>
                <p>Migration, seeder, dan QueryBuilder/Eloquent-lite untuk produktivitas.</p>
            </div>
            <div class="card">
                <h3>Structure</h3>
                <p>Konvensi MVC yang rapi — controllers, middleware, views.</p>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <a class="btn primary" href="/users">Lihat Users</a>
            <a class="btn" href="/api/users">API Users</a>
        </div>

        <div class="card" style="margin-top:12px;background:transparent;border:1px solid var(--glass);">
            <h3>Quick Start</h3>
            <ol style="margin:8px 0 0 16px;color:var(--muted)">
                <li>Edit <code>routes/web.php</code></li>
                <li>Buat controller di <code>app/Http/Controllers/</code></li>
                <li>Buat model di <code>app/Models/</code></li>
                <li>Buat view di <code>resources/views/</code></li>
            </ol>
        </div>
    </section>
@endsection
