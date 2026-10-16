<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Médiathèque - CMS Admin</title>
    <link rel="stylesheet" href="{{ asset('vendor/plugins/acme.media-library/css/media.css') }}">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; }
        .card { background: white; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-preview { height: 140px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; }
        .card-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card-body { padding: 0.75rem; font-size: 0.875rem; }
        .upload-form { background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Médiathèque</h1>
            <a href="/admin/plugins" style="color: #4f46e5; text-decoration: none;">&larr; Retour aux Plugins</a>
        </header>

        @if(session('success'))
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="upload-form">
            <h3>Téléverser un média</h3>
            <form action="/admin/media/upload" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <input type="file" name="file" required accept="image/*,application/pdf">
                </div>
                <div style="margin-bottom: 1rem;">
                    <input type="text" name="alt_text" placeholder="Texte alternatif (optionnel)" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>
                <button type="submit" style="background: #4f46e5; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;">
                    Uploader
                </button>
            </form>
        </div>

        <div class="grid">
            @forelse($items as $item)
                <div class="card">
                    <div class="card-preview">
                        @if(str_starts_with($item['mime_type'], 'image/'))
                            <img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] ?? $item['name'] }}">
                        @else
                            <div style="font-size: 2rem;">📄</div>
                        @endif
                    </div>
                    <div class="card-body">
                        <strong title="{{ $item['name'] }}">{{ \Illuminate\Support\Str::limit($item['name'], 20) }}</strong>
                        <div style="color: #64748b; font-size: 0.75rem;">{{ round($item['size'] / 1024, 1) }} KB</div>
                    </div>
                </div>
            @empty
                <p style="color: #64748b;">Aucun média enregistré.</p>
            @endforelse
        </div>
    </div>
</body>
</html>

