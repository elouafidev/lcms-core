<?php

declare(strict_types=1);

namespace Plugins\Search\DTO;

class SearchResult
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly string $snippet,
        public readonly ?string $date = null
    ) {}

    /**
     * @return array{title: string, url: string, type: string, type_label: string, snippet: string, date: ?string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'type' => $this->type,
            'type_label' => $this->typeLabel,
            'snippet' => $this->snippet,
            'date' => $this->date,
        ];
    }
}
