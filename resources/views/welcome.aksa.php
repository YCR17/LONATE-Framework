@extends('layouts.app')

@section('content')
    <section style="display:flex;flex-direction:column;gap:16px;align-items:flex-start;">
        <div>
            <h2 style="margin:0;font-size:20px;color:var(--text)">Aksa</h2>
            <p style="margin-top:8px;color:var(--muted)">Sebuah framework MVC PHP minimal dan modern — cepat dipahami, mudah dikembangkan.</p>
        </div>

        <div class="card" style="width:100%;padding:20px;">
            <h3 style="margin-bottom:8px">Mulai cepat</h3>
            <ol style="margin:0 0 0 18px;color:var(--muted)">
                <li>Edit <code>routes/web.php</code></li>
                <li>Buat controller di <code>app/Http/Controllers/</code></li>
                <li>Buat view di <code>resources/views/</code></li>
            </ol>
        </div>

        <div style="color:var(--muted);font-size:13px">Versi ringkas tanpa tautan demo — fokus pada pengembangan aplikasi Anda.</div>
    </section>
@endsection