<?php

declare(strict_types=1);

namespace App\Core\Contracts;

interface HookSubscriberInterface
{
    /**
     * Get the declarative map of hooks this subscriber listens to.
     *
     * Example structure:
     * [
     *     'actions' => [
     *         'admin.init' => 'onAdminInit',
     *         'content.saved' => ['onContentSaved', 10],
     *     ],
     *     'filters' => [
     *         'content.title' => 'filterTitle',
     *         'seo.meta' => [['addKeywords', 10], ['sanitizeMeta', 20]],
     *     ],
     * ]
     *
     * @return array{
     *     actions?: array<string, string|array{0: string, 1?: int}|list<string|array{0: string, 1?: int}>>,
     *     filters?: array<string, string|array{0: string, 1?: int}|list<string|array{0: string, 1?: int}>>
     * }
     */
    public function getSubscribedHooks(): array;
}
