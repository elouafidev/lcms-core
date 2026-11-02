<?php

declare(strict_types=1);

namespace Plugins\Search\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Plugins\Blocks\Models\Block;
use Plugins\Blog\Models\Post;
use Plugins\Pages\Models\Page;
use Plugins\Search\DTO\SearchResult;
use Plugins\Search\Models\SearchLog;

class SearchEngine
{
    /**
     * Execute global unified search across all publishable CMS entities.
     *
     * @return list<SearchResult>
     */
    public function search(
        string $term,
        ?string $typeFilter = null,
        int $limit = 20,
        bool $recordLog = true,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        $cleanTerm = trim($term);
        if ($cleanTerm === '') {
            return [];
        }

        $results = [];

        // 1. Search Pages
        if ($this->shouldSearchType('page', $typeFilter) && Schema::hasTable('plugin_pages') && class_exists(Page::class)) {
            $pages = Page::query()
                ->where('status', 'published')
                ->where(function (Builder $q) use ($cleanTerm): void {
                    $termLike = '%'.$cleanTerm.'%';
                    $q->where('title', 'like', $termLike)
                        ->orWhere('slug', 'like', $termLike)
                        ->orWhere('content', 'like', $termLike);
                })
                ->take($limit)
                ->get();

            foreach ($pages as $page) {
                /** @var Page $page */
                $results[] = new SearchResult(
                    title: $page->title,
                    url: '/page/'.$page->slug,
                    type: 'page',
                    typeLabel: 'Page',
                    snippet: $this->generateSnippet((string) $page->content, $cleanTerm),
                    date: $page->published_at ? $page->published_at->format('d/m/Y') : ($page->created_at ? $page->created_at->format('d/m/Y') : null)
                );
            }
        }

        // 2. Search Blog Posts
        if ($this->shouldSearchType('post', $typeFilter) && Schema::hasTable('plugin_blog_posts') && class_exists(Post::class)) {
            $posts = Post::queryPublished()
                ->where(function (Builder $q) use ($cleanTerm): void {
                    $termLike = '%'.$cleanTerm.'%';
                    $q->where('title', 'like', $termLike)
                        ->orWhere('slug', 'like', $termLike)
                        ->orWhere('excerpt', 'like', $termLike)
                        ->orWhere('content', 'like', $termLike);
                })
                ->take($limit)
                ->get();

            foreach ($posts as $post) {
                /** @var Post $post */
                $content = $post->excerpt ?: (string) $post->content;
                $results[] = new SearchResult(
                    title: $post->title,
                    url: '/blog/'.$post->slug,
                    type: 'post',
                    typeLabel: 'Article',
                    snippet: $this->generateSnippet($content, $cleanTerm),
                    date: $post->published_at ? $post->published_at->format('d/m/Y') : ($post->created_at ? $post->created_at->format('d/m/Y') : null)
                );
            }
        }

        // 3. Search Content Blocks
        if ($this->shouldSearchType('block', $typeFilter) && Schema::hasTable('plugin_blocks') && class_exists(Block::class)) {
            $blocks = Block::query()
                ->where('is_active', true)
                ->where(function (Builder $q) use ($cleanTerm): void {
                    $termLike = '%'.$cleanTerm.'%';
                    $q->where('name', 'like', $termLike)
                        ->orWhere('slug', 'like', $termLike)
                        ->orWhere('content', 'like', $termLike);
                })
                ->take($limit)
                ->get();

            foreach ($blocks as $block) {
                /** @var Block $block */
                $contentStr = json_encode($block->content, JSON_UNESCAPED_UNICODE) ?: '';
                $results[] = new SearchResult(
                    title: $block->name,
                    url: '/admin/blocks/'.$block->id.'/edit',
                    type: 'block',
                    typeLabel: 'Bloc',
                    snippet: $this->generateSnippet($contentStr, $cleanTerm),
                    date: $block->created_at ? $block->created_at->format('d/m/Y') : null
                );
            }
        }

        if ($recordLog && Schema::hasTable('plugin_search_logs')) {
            SearchLog::record($cleanTerm, count($results), $ip, $userAgent);
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Determine if a specific content type should be searched.
     */
    private function shouldSearchType(string $type, ?string $filter): bool
    {
        if ($filter === null || $filter === '' || $filter === 'all') {
            return true;
        }

        return $type === $filter;
    }

    /**
     * Generate a contextual text snippet with the searched keyword highlighted.
     */
    public function generateSnippet(string $rawContent, string $term, int $radius = 80): string
    {
        $clean = strip_tags($rawContent);
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        $pos = mb_stripos($clean, $term);
        if ($pos === false) {
            return Str::limit($clean, 140);
        }

        $start = max(0, $pos - $radius);
        $length = mb_strlen($term) + ($radius * 2);
        $snippet = mb_substr($clean, $start, $length);

        if ($start > 0) {
            $snippet = '...'.$snippet;
        }
        if (($start + $length) < mb_strlen($clean)) {
            $snippet .= '...';
        }

        // Highlight matched keyword
        $escapedTerm = preg_quote($term, '/');

        return preg_replace('/('.$escapedTerm.')/iu', '<mark class="bg-warning-subtle text-dark px-1 rounded">$1</mark>', $snippet) ?? $snippet;
    }
}
