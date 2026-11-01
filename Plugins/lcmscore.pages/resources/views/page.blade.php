<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #ffffff; color: #1e293b; margin: 0; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { font-size: 2.25rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; }
        .content { font-size: 1.125rem; line-height: 1.75; color: #334155; }
    </style>
</head>
<body>
    <div class="container">
        <article>
            @if($page->parent)
                <nav style="margin-bottom: 1rem; font-size: 0.9rem; color: #64748b;">
                    <a href="/page/{{ $page->parent->slug }}" style="color: #2563eb; text-decoration: none;">{{ $page->parent->title }}</a>
                    <span style="margin: 0 0.5rem;">/</span>
                    <span>{{ $page->title }}</span>
                </nav>
            @endif

            <h1>{{ $page->title }}</h1>

            @if($page->featured_image)
                <div style="margin-bottom: 2rem; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <img src="{{ $page->featured_image }}" alt="{{ $page->title }}" style="width: 100%; max-height: 400px; object-fit: cover; display: block;">
                </div>
            @endif

            <div class="content">
                {!! $page->content !!}
            </div>
        </article>
    </div>
</body>
</html>
