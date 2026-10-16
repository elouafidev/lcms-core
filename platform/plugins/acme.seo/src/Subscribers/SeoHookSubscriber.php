<?php

declare(strict_types=1);

namespace Plugins\Seo\Subscribers;

use App\Core\Contracts\HookSubscriberInterface;
use App\Core\PluginRegistry\PluginRegistry;
use Plugins\Seo\Services\SeoMetaManager;

class SeoHookSubscriber implements HookSubscriberInterface
{
    public function __construct(
        private SeoMetaManager $metaManager,
        private PluginRegistry $registry
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getSubscribedHooks(): array
    {
        return [
            'filters' => [
                'cms.head' => 'filterHead',
            ],
        ];
    }

    /**
     * Filter the cms.head HTML to inject SEO metadata when the plugin is active.
     */
    public function filterHead(mixed $headContent = ''): string
    {
        $head = is_string($headContent) ? $headContent : '';

        // Only inject if acme.seo is currently active
        if (! $this->registry->isActive('acme.seo')) {
            return $head;
        }

        $seoHtml = $this->metaManager->renderHtml();
        if ($seoHtml === '') {
            return $head;
        }

        return $head === '' ? $seoHtml : $head."\n".$seoHtml;
    }
}
