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
            <h1>{{ $page->title }}</h1>
            <div class="content">
                {!! $page->content !!}
            </div>
        </article>
    </div>
</body>
</html>
