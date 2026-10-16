<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration SEO — Administration</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.5; padding: 2rem; background: #f8fafc; color: #1e293b; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.25rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #3b82f6; color: white; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Configuration SEO</h1>

    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; max-width: 600px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <h2>Paramètres par défaut</h2>
        <form action="/admin/seo" method="POST">
            @csrf
            <div class="form-group">
                <label for="site_name">Nom du site</label>
                <input type="text" id="site_name" name="site_name" value="{{ $settings['site_name'] ?? '' }}" placeholder="ex: Mon Super CMS">
            </div>
            <div class="form-group">
                <label for="default_title">Titre par défaut (meta title / og:title)</label>
                <input type="text" id="default_title" name="default_title" value="{{ $settings['default_title'] ?? '' }}" placeholder="ex: Accueil - Mon Super CMS">
            </div>
            <div class="form-group">
                <label for="default_description">Méta-description par défaut</label>
                <textarea id="default_description" name="default_description" rows="3" placeholder="Description générale du site web">{{ $settings['default_description'] ?? '' }}</textarea>
            </div>
            <div class="form-group">
                <label for="default_canonical">URL Canonique par défaut</label>
                <input type="text" id="default_canonical" name="default_canonical" value="{{ $settings['default_canonical'] ?? '' }}" placeholder="ex: https://monsite.fr">
            </div>
            <div class="form-group">
                <label for="default_og_image">Image OpenGraph par défaut</label>
                <input type="text" id="default_og_image" name="default_og_image" value="{{ $settings['default_og_image'] ?? '' }}" placeholder="ex: /assets/og-cover.png">
            </div>
            <button type="submit" class="btn">Enregistrer les paramètres</button>
        </form>
    </div>
</body>
</html>
