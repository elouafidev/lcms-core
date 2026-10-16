<?php

declare(strict_types=1);

namespace Plugins\Seo\Services;

use Illuminate\Support\Facades\Schema;
use Plugins\Seo\Models\SeoSetting;

class SeoMetaManager
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $canonical = null;

    private ?string $ogTitle = null;

    private ?string $ogDescription = null;

    private string $ogType = 'website';

    private ?string $ogImage = null;

    private ?string $ogUrl = null;

    private ?string $siteName = null;

    /**
     * @var array<string, string>
     */
    private array $customMetas = [];

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCanonical(?string $canonical): self
    {
        $this->canonical = $canonical;

        return $this;
    }

    public function getCanonical(): ?string
    {
        return $this->canonical;
    }

    public function setOgTitle(?string $ogTitle): self
    {
        $this->ogTitle = $ogTitle;

        return $this;
    }

    public function getOgTitle(): ?string
    {
        return $this->ogTitle;
    }

    public function setOgDescription(?string $ogDescription): self
    {
        $this->ogDescription = $ogDescription;

        return $this;
    }

    public function getOgDescription(): ?string
    {
        return $this->ogDescription;
    }

    public function setOgType(string $ogType): self
    {
        $this->ogType = $ogType;

        return $this;
    }

    public function getOgType(): string
    {
        return $this->ogType;
    }

    public function setOgImage(?string $ogImage): self
    {
        $this->ogImage = $ogImage;

        return $this;
    }

    public function getOgImage(): ?string
    {
        return $this->ogImage;
    }

    public function setOgUrl(?string $ogUrl): self
    {
        $this->ogUrl = $ogUrl;

        return $this;
    }

    public function getOgUrl(): ?string
    {
        return $this->ogUrl;
    }

    public function setSiteName(?string $siteName): self
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    public function setMeta(string $name, string $content): self
    {
        $this->customMetas[$name] = $content;

        return $this;
    }

    public function getMeta(string $name): ?string
    {
        return $this->customMetas[$name] ?? null;
    }

    /**
     * Clear all runtime metadata.
     */
    public function reset(): void
    {
        $this->title = null;
        $this->description = null;
        $this->canonical = null;
        $this->ogTitle = null;
        $this->ogDescription = null;
        $this->ogType = 'website';
        $this->ogImage = null;
        $this->ogUrl = null;
        $this->siteName = null;
        $this->customMetas = [];
    }

    /**
     * Generate HTML meta and link tags.
     */
    public function renderHtml(): string
    {
        $hasSettingsTable = Schema::hasTable('plugin_seo_settings');

        $title = $this->title;
        if ($title === null && $hasSettingsTable) {
            $title = SeoSetting::get('default_title');
        }

        $description = $this->description;
        if ($description === null && $hasSettingsTable) {
            $description = SeoSetting::get('default_description');
        }

        $canonical = $this->canonical;
        if ($canonical === null && $hasSettingsTable) {
            $canonical = SeoSetting::get('default_canonical');
        }

        $ogTitle = $this->ogTitle ?? $title;
        $ogDescription = $this->ogDescription ?? $description;
        $ogUrl = $this->ogUrl ?? $canonical;

        $ogImage = $this->ogImage;
        if ($ogImage === null && $hasSettingsTable) {
            $ogImage = SeoSetting::get('default_og_image');
        }

        $siteName = $this->siteName;
        if ($siteName === null && $hasSettingsTable) {
            $siteName = SeoSetting::get('site_name');
        }

        $tags = [];

        if ($description !== null && $description !== '') {
            $tags[] = '<meta name="description" content="'.htmlspecialchars($description, ENT_QUOTES, 'UTF-8').'">';
        }

        if ($canonical !== null && $canonical !== '') {
            $tags[] = '<link rel="canonical" href="'.htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8').'">';
        }

        if ($ogTitle !== null && $ogTitle !== '') {
            $tags[] = '<meta property="og:title" content="'.htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8').'">';
        }

        if ($ogDescription !== null && $ogDescription !== '') {
            $tags[] = '<meta property="og:description" content="'.htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8').'">';
        }

        $tags[] = '<meta property="og:type" content="'.htmlspecialchars($this->ogType, ENT_QUOTES, 'UTF-8').'">';

        if ($ogUrl !== null && $ogUrl !== '') {
            $tags[] = '<meta property="og:url" content="'.htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8').'">';
        }

        if ($ogImage !== null && $ogImage !== '') {
            $tags[] = '<meta property="og:image" content="'.htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8').'">';
        }

        if ($siteName !== null && $siteName !== '') {
            $tags[] = '<meta property="og:site_name" content="'.htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8').'">';
        }

        foreach ($this->customMetas as $name => $content) {
            $tags[] = '<meta name="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'" content="'.htmlspecialchars($content, ENT_QUOTES, 'UTF-8').'">';
        }

        return implode("\n", $tags);
    }
}
