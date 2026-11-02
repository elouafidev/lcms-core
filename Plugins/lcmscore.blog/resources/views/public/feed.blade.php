<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><![CDATA[{{ config('app.name', 'LCMS Core') }} - Blog]]></title>
    <link>{{ url('/blog') }}</link>
    <description><![CDATA[Dernières actualités et articles publiés sur {{ config('app.name', 'LCMS Core') }}]]></description>
    <language>{{ app()->getLocale() }}</language>
    <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
    <atom:link href="{{ url('/blog/feed.xml') }}" rel="self" type="application/rss+xml" />
    @foreach($posts as $post)
      <item>
        <title><![CDATA[{{ $post->title }}]]></title>
        <link>{{ url('/blog/' . $post->slug) }}</link>
        <guid isPermaLink="true">{{ url('/blog/' . $post->slug) }}</guid>
        <pubDate>{{ $post->published_at ? $post->published_at->toRfc2822String() : ($post->created_at ? $post->created_at->toRfc2822String() : now()->toRfc2822String()) }}</pubDate>
        @if($post->category)
          <category><![CDATA[{{ $post->category->name }}]]></category>
        @endif
        @if($post->author)
          <author>{{ $post->author->email }} ({{ $post->author->name }})</author>
        @endif
        <description><![CDATA[{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 200) }}]]></description>
      </item>
    @endforeach
  </channel>
</rss>
