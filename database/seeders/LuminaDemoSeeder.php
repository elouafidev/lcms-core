<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Models\User;
use Core\Themes\ThemeManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LuminaDemoSeeder extends Seeder
{
    /**
     * Run all demo content seeders for Lumina Lifestyle theme.
     */
    public function run(): void
    {
        /** @var User|null $existingUser */
        $existingUser = User::query()->first();
        if ($existingUser instanceof User) {
            $author = $existingUser;
        } else {
            /** @var User $author */
            $author = User::factory()->create([
                'name' => 'Éléonore de Lumina',
                'email' => 'eleonore@lumina-lifestyle.local',
            ]);
        }

        $this->seedI18n();
        $this->seedMenus();
        $this->seedForms();
        $this->seedBlocks();
        $this->seedPages();
        $this->seedBlog($author);
        $this->seedCommerce();
        $this->seedNewsletter();
        $this->seedAnalytics();
        $this->activateTheme();
    }

    /**
     * 1. Seed Languages (lcmscore.i18n)
     */
    private function seedI18n(): void
    {
        if (! Schema::hasTable('plugin_i18n_languages') || ! class_exists(\Plugins\I18n\Models\Language::class)) {
            return;
        }

        \Plugins\I18n\Models\Language::query()->firstOrCreate(
            ['code' => 'fr'],
            [
                'name' => 'Français',
                'native_name' => 'Français',
                'direction' => 'ltr',
                'is_default' => true,
                'is_active' => true,
                'order' => 1,
            ]
        );

        \Plugins\I18n\Models\Language::query()->firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_default' => false,
                'is_active' => true,
                'order' => 2,
            ]
        );
    }

    /**
     * 2. Seed Navigation Menus (lcmscore.menus)
     */
    private function seedMenus(): void
    {
        if (! Schema::hasTable('plugin_menus') || ! class_exists(\Plugins\Menus\Models\Menu::class)) {
            return;
        }

        // Main Navigation
        /** @var \Plugins\Menus\Models\Menu $mainMenu */
        $mainMenu = \Plugins\Menus\Models\Menu::query()->firstOrCreate(
            ['slug' => 'main-nav'],
            [
                'name' => 'Navigation Principale',
                'description' => 'Menu d\'en-tête principal du thème Lumina Lifestyle',
                'is_active' => true,
            ]
        );

        $mainItems = [
            ['title' => 'Accueil', 'url' => '/', 'order' => 1],
            ['title' => 'Boutique', 'url' => '/shop', 'order' => 2],
            ['title' => 'Le Journal', 'url' => '/blog', 'order' => 3],
            ['title' => 'Notre Histoire', 'url' => '/page/a-propos', 'order' => 4],
            ['title' => 'Contact', 'url' => '/page/contact', 'order' => 5],
        ];

        foreach ($mainItems as $item) {
            \Plugins\Menus\Models\MenuItem::query()->firstOrCreate(
                ['menu_id' => $mainMenu->id, 'url' => $item['url']],
                [
                    'title' => $item['title'],
                    'target' => '_self',
                    'order' => $item['order'],
                    'is_active' => true,
                ]
            );
        }

        // Footer Navigation
        /** @var \Plugins\Menus\Models\Menu $footerMenu */
        $footerMenu = \Plugins\Menus\Models\Menu::query()->firstOrCreate(
            ['slug' => 'footer-nav'],
            [
                'name' => 'Pied de Page — Liens Légaux',
                'description' => 'Menu secondaire de pied de page',
                'is_active' => true,
            ]
        );

        $footerItems = [
            ['title' => 'Mentions Légales', 'url' => '/page/mentions-legales', 'order' => 1],
            ['title' => 'Confidentialité', 'url' => '/page/confidentialite', 'order' => 2],
            ['title' => 'Conditions Générales de Vente', 'url' => '/page/cgv', 'order' => 3],
        ];

        foreach ($footerItems as $item) {
            \Plugins\Menus\Models\MenuItem::query()->firstOrCreate(
                ['menu_id' => $footerMenu->id, 'url' => $item['url']],
                [
                    'title' => $item['title'],
                    'target' => '_self',
                    'order' => $item['order'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * 3. Seed Contact Form (lcmscore.forms)
     */
    private function seedForms(): void
    {
        if (! Schema::hasTable('plugin_forms') || ! class_exists(\Plugins\Forms\Models\Form::class)) {
            return;
        }

        \Plugins\Forms\Models\Form::query()->firstOrCreate(
            ['slug' => 'contact'],
            [
                'name' => 'Contact & Service Client Lumina',
                'description' => 'Formulaire de contact officiel pour demandes sur mesure et suivi client.',
                'fields' => [
                    [
                        'name' => 'name',
                        'label' => 'Votre Nom complet',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'Jeanne Martin',
                    ],
                    [
                        'name' => 'email',
                        'label' => 'Adresse e-mail',
                        'type' => 'email',
                        'required' => true,
                        'placeholder' => 'jeanne@example.com',
                    ],
                    [
                        'name' => 'subject',
                        'label' => 'Sujet de votre message',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Suivi de commande',
                            'Question sur une création artisanale',
                            'Proposition de collaboration ou presse',
                            'Autre demande',
                        ],
                    ],
                    [
                        'name' => 'message',
                        'label' => 'Votre Message',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Bonjour, j\'aimerais en savoir plus sur...',
                    ],
                ],
                'submit_label' => 'Envoyer votre message',
                'success_message' => 'Merci pour votre message ! Notre équipe vous répondra sous 24 à 48 heures ouvrées.',
                'recipient_email' => 'contact@lumina-lifestyle.local',
                'send_notification' => false,
                'is_active' => true,
                'honeypot_enabled' => true,
            ]
        );
    }

    /**
     * 4. Seed Modular Content Blocks (lcmscore.blocks)
     */
    private function seedBlocks(): void
    {
        if (! Schema::hasTable('plugin_blocks') || ! class_exists(\Plugins\Blocks\Models\Block::class)) {
            return;
        }

        // Hero block
        \Plugins\Blocks\Models\Block::query()->firstOrCreate(
            ['slug' => 'home-hero'],
            [
                'name' => 'Bannière d\'accueil Hero',
                'type' => 'hero',
                'is_active' => true,
                'order' => 1,
                'content' => [
                    'badge' => 'Slow Living & Artisanat Raisonné',
                    'title' => 'L\'art de vivre avec simplicité, conscience et beauté.',
                    'subtitle' => 'Découvrez nos collections d\'objets façonnés à la main et nos récits dédiés au bien-être au quotidien.',
                    'primary_btn_text' => 'Explorer la boutique',
                    'primary_btn_url' => '/shop',
                    'secondary_btn_text' => 'Lire le Journal',
                    'secondary_btn_url' => '/blog',
                    'background_image' => 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=1200&q=80',
                    'style' => 'dark',
                ],
            ]
        );

        // Brand values block
        \Plugins\Blocks\Models\Block::query()->firstOrCreate(
            ['slug' => 'brand-values'],
            [
                'name' => 'Piliers & Engagements',
                'type' => 'features',
                'is_active' => true,
                'order' => 2,
                'content' => [
                    'title' => 'Nos Engagements Éthiques',
                    'subtitle' => 'Une démarche transparente et durable de l\'atelier jusqu\'à votre intérieur.',
                    'items' => [
                        [
                            'icon' => 'bi bi-feather',
                            'title' => 'Artisanat Familial',
                            'description' => 'Des pièces uniques tournées ou tissées par des artisans reconnus pour leur savoir-faire.',
                        ],
                        [
                            'icon' => 'bi bi-recycle',
                            'title' => 'Matières Organiques',
                            'description' => 'Coton bio GOTS, lin européen et céramiques garanties sans solvants nocifs.',
                        ],
                        [
                            'icon' => 'bi bi-box2-heart',
                            'title' => 'Transport Responsable',
                            'description' => 'Emballages éco-conçus sans plastique et compensations carbone systématiques.',
                        ],
                    ],
                ],
            ]
        );

        // Testimonials block
        \Plugins\Blocks\Models\Block::query()->firstOrCreate(
            ['slug' => 'testimonials'],
            [
                'name' => 'Avis Clients & Retours d\'expérience',
                'type' => 'testimonials',
                'is_active' => true,
                'order' => 3,
                'content' => [
                    'title' => 'Ce que disent nos clients',
                    'subtitle' => 'Des retours authentiques de passionnés de design et d\'art de vivre.',
                    'items' => [
                        [
                            'author' => 'Camille L.',
                            'role' => 'Architecte d\'intérieur, Lyon',
                            'rating' => 5,
                            'quote' => 'Les tasses en grès apportent une poésie immédiate à mon rituel du matin. La texture brute est remarquable.',
                            'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
                        ],
                        [
                            'author' => 'Julien R.',
                            'role' => 'Designer graphique, Bordeaux',
                            'rating' => 5,
                            'quote' => 'Le plaid en lin lavé est d\'une douceur incomparable. Colis arrivé très vite, protégé avec soin sans aucun plastique.',
                            'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
                        ],
                        [
                            'author' => 'Sarah M.',
                            'role' => 'Abonnée au Journal, Paris',
                            'rating' => 5,
                            'quote' => 'Le Journal de Lumina est ma lecture du dimanche préférée. Des interviews inspirantes et des conseils réalistes.',
                            'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * 5. Seed CMS Pages (lcmscore.pages)
     */
    private function seedPages(): void
    {
        if (! Schema::hasTable('plugin_pages') || ! class_exists(\Plugins\Pages\Models\Page::class)) {
            return;
        }

        $pages = [
            [
                'title' => 'Notre Histoire — L\'Atelier Lumina',
                'slug' => 'a-propos',
                'template' => 'default',
                'status' => 'published',
                'featured_image' => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=1000&q=80',
                'content' => '<p class="lead">Fondé avec la conviction qu\'habiter un espace harmonieux commence par des choix conscients, <strong>Lumina Lifestyle</strong> célèbre les matières vraies et le temps long.</p>
<p>Nous collaborons exclusivement avec des artisans céramistes, tisserands et ciriers qui partagent notre rigueur esthétique et environnementale. Chaque objet que vous trouverez dans notre boutique est pensé pour traverser les saisons sans jamais perdre son âme.</p>
<h3>Notre Philosophie : Le Slow Living au Quotidien</h3>
<p>Ralentir, apprécier la texture d\'un lin brut, la patine d\'une céramique tournée à la main, savourer un café extrait lentement : voilà ce qui guide notre ligne éditoriale et nos créations.</p>',
            ],
            [
                'title' => 'Nous Contacter',
                'slug' => 'contact',
                'template' => 'contact',
                'status' => 'published',
                'featured_image' => null,
                'content' => '<p>Vous pouvez également nous rendre visite directement à notre atelier-galerie situé en plein cœur de Paris, du mardi au samedi.</p>',
            ],
            [
                'title' => 'Mentions Légales & Éditeur',
                'slug' => 'mentions-legales',
                'template' => 'default',
                'status' => 'published',
                'featured_image' => null,
                'content' => '<p>Le site <strong>Lumina Lifestyle</strong> est édité par la société LCMS Éditions SAS au capital de 10 000 €, immatriculée au RCS de Paris sous le numéro 987 654 321.</p>
<p><strong>Siège social :</strong> 14 rue des Artisans, 75003 Paris, France.<br><strong>Directeur de la publication :</strong> Éléonore de Lumina.<br><strong>Hébergement :</strong> Serveurs cloud éco-responsables alimentés en énergies 100% renouvelables.</p>',
            ],
            [
                'title' => 'Politique de Confidentialité & RGPD',
                'slug' => 'confidentialite',
                'template' => 'default',
                'status' => 'published',
                'featured_image' => null,
                'content' => '<p>Conformément au Règlement Général sur la Protection des Données (RGPD), nous traitons vos données personnelles avec une sobriété stricte. Notre mesure d\'audience ne dépose aucun cookie traceur sur votre terminal.</p>
<p>Vos coordonnées de commande sont exclusivement utilisées pour l\'expédition de votre colis et ne sont jamais cédées à des tiers publicitaires.</p>',
            ],
            [
                'title' => 'Conditions Générales de Vente (CGV)',
                'slug' => 'cgv',
                'template' => 'default',
                'status' => 'published',
                'featured_image' => null,
                'content' => '<p>Les présentes conditions régissent les ventes conclues sur la boutique en ligne Lumina Lifestyle. Tous nos prix sont exprimés en euros toutes taxes comprises (TTC).</p>
<p><strong>Livraison :</strong> Les commandes sont expédiées sous 24 à 48 heures ouvrées par transporteur éco-certifié.<br><strong>Délai de rétractation :</strong> Vous disposez de 30 jours à compter de la réception de vos articles pour demander un retour sans frais.</p>',
            ],
        ];

        foreach ($pages as $p) {
            \Plugins\Pages\Models\Page::query()->firstOrCreate(
                ['slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'template' => $p['template'],
                    'status' => $p['status'],
                    'featured_image' => $p['featured_image'],
                    'content' => $p['content'],
                    'published_at' => now(),
                ]
            );
        }
    }

    /**
     * 6. Seed Blog Categories, Tags, and Articles (lcmscore.blog)
     */
    private function seedBlog(User $author): void
    {
        if (! Schema::hasTable('plugin_blog_posts') || ! class_exists(\Plugins\Blog\Models\Post::class)) {
            return;
        }

        // Categories
        /** @var \Plugins\Blog\Models\Category $catArtDeVivre */
        $catArtDeVivre = \Plugins\Blog\Models\Category::query()->firstOrCreate(
            ['slug' => 'art-de-vivre'],
            ['name' => 'Art de Vivre', 'description' => 'Chroniques sur le slow living, rituels matinaux et sérénité.']
        );

        /** @var \Plugins\Blog\Models\Category $catArtisanat */
        $catArtisanat = \Plugins\Blog\Models\Category::query()->firstOrCreate(
            ['slug' => 'artisanat-d-art'],
            ['name' => 'Artisanat d\'Art', 'description' => 'Rencontres au cœur des ateliers céramiques et textiles.']
        );

        /** @var \Plugins\Blog\Models\Category $catDesign */
        $catDesign = \Plugins\Blog\Models\Category::query()->firstOrCreate(
            ['slug' => 'tendances-inspirations'],
            ['name' => 'Tendances & Inspirations', 'description' => 'Conseils d\'aménagement doux, lumière naturelle et matières.']
        );

        // Tags
        /** @var \Plugins\Blog\Models\Tag $tagSlow */
        $tagSlow = \Plugins\Blog\Models\Tag::query()->firstOrCreate(['slug' => 'slow-living'], ['name' => 'Slow-Living']);
        /** @var \Plugins\Blog\Models\Tag $tagCeramique */
        $tagCeramique = \Plugins\Blog\Models\Tag::query()->firstOrCreate(['slug' => 'ceramique'], ['name' => 'Céramique']);
        /** @var \Plugins\Blog\Models\Tag $tagLin */
        $tagLin = \Plugins\Blog\Models\Tag::query()->firstOrCreate(['slug' => 'lin-lave'], ['name' => 'Lin-Lavé']);
        /** @var \Plugins\Blog\Models\Tag $tagDeco */
        $tagDeco = \Plugins\Blog\Models\Tag::query()->firstOrCreate(['slug' => 'decoration'], ['name' => 'Décoration']);
        /** @var \Plugins\Blog\Models\Tag $tagEthique */
        $tagEthique = \Plugins\Blog\Models\Tag::query()->firstOrCreate(['slug' => 'eco-design'], ['name' => 'Éco-Design']);

        $articles = [
            [
                'title' => 'Les bienfaits insoupçonnés du café filtre en extraction lente',
                'slug' => 'bienfaits-cafe-filtre-extraction-lente',
                'category_id' => $catArtDeVivre->id,
                'excerpt' => 'Prendre quatre minutes chaque matin pour verser l\'eau chaude en cercles concentriques : une méditation accessible qui transforme votre réveil.',
                'featured_image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80',
                'reading_time' => 4,
                'content' => '<p class="lead">Dans un monde dominé par l\'instantanéité et les capsules métalliques, redécouvrir l\'extraction manuelle du café (V60, Chemex ou cafetière à piston) s\'apparente à un manifeste de douceur.</p>
<p>L\'extraction douce permet de révéler des notes florales et fruitées invisibles sous une forte pression. Mais au-delà de la tasse, c\'est le rituel qui compte : observer la mouture gonfler au contact de l\'eau à 92°C, sentir les arômes s\'épanouir dans la cuisine silencieuse.</p>
<h2>Le choix du moulin et de la mouture</h2>
<p>Pour un résultat d\'exception, la fraîcheur du grain prime toujours. Privilégiez des grains de spécialité issus de petites plantations éco-responsables torréfiés localement.</p>',
                'tags' => [$tagSlow->id, $tagEthique->id],
            ],
            [
                'title' => 'Rencontre avec nos céramistes : la magie du grès tourné main',
                'slug' => 'rencontre-ceramistes-magie-gres-tourne-main',
                'category_id' => $catArtisanat->id,
                'excerpt' => 'Immersion dans un atelier de poterie au cœur de la Bourgogne, où la terre prend forme au rythme des gestes séculaires.',
                'featured_image' => 'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?auto=format&fit=crop&w=800&q=80',
                'reading_time' => 5,
                'content' => '<p class="lead">Chaque matin, Marie et Lucas préparent la terre : un grès noir chamotté riche en minéraux naturels qui donnera aux tasses Lumina leur grain si particulier.</p>
<p>« Quand le tour tourne, tout s\'arrête. Il faut être parfaitement centré dans son corps pour que l\'argile se centre à son tour », nous confie Marie tout en modelant la panse d\'une théière.</p>
<h2>Une cuisson à 1280°C pour une solidité à toute épreuve</h2>
<p>Contrairement à la faïence, le grès se vitrifie à haute température, ce qui le rend imperméable, résistant aux chocs et compatible avec le lave-vaisselle sans altérer sa teinte minérale.</p>',
                'tags' => [$tagCeramique->id, $tagEthique->id],
            ],
            [
                'title' => 'Pourquoi le lin lavé est la matière textile la plus écologique',
                'slug' => 'pourquoi-lin-lave-matiere-ecologique',
                'category_id' => $catArtisanat->id,
                'excerpt' => 'Cultivé sans irrigation ni OGM en Europe occidentale, le lin est l\'allié naturel d\'une chambre apaisante et respirante.',
                'featured_image' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=800&q=80',
                'reading_time' => 6,
                'content' => '<p class="lead">De la Normandie à la Flandre, la France et la Belgique produisent plus de 80% du lin mondial. Cette plante robuste n\'a besoin que de l\'eau de pluie et de la richesse de nos sols pour grandir.</p>
<p>En plus de son bilan carbone exemplaire, le lin est thermorégulateur : frais en été, enveloppant et chaud en hiver. Son froissé naturel libère enfin de la corvée du repassage !</p>',
                'tags' => [$tagLin->id, $tagDeco->id, $tagEthique->id],
            ],
            [
                'title' => 'Minimalisme chaleureux : quand la simplicité rencontre le confort',
                'slug' => 'minimalisme-chaleureux-simplicite-confort',
                'category_id' => $catDesign->id,
                'excerpt' => 'Fini le minimalisme clinique et froid des années 2010. Place au "Warm Minimalism", fait de boiseries brutes, de textiles texturés et de lumière tamisée.',
                'featured_image' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=800&q=80',
                'reading_time' => 5,
                'content' => '<p class="lead">Épurer son salon ne signifie pas vivre dans une boîte blanche impersonnelle. Le secret réside dans le contraste des textures naturelles.</p>
<p>Associez une table en chêne massif huilé, une céramique mate, quelques branches séchées et un éclairage d\'ambiance doux (2700K) pour métamorphoser votre intérieur en havre de paix.</p>',
                'tags' => [$tagDeco->id, $tagSlow->id],
            ],
            [
                'title' => 'Les senteurs de saison : bougies naturelles et accords boisés',
                'slug' => 'senteurs-saison-bougies-naturelles-accords-boises',
                'category_id' => $catArtDeVivre->id,
                'excerpt' => 'Comment la cire végétale de soja et les huiles essentielles de cèdre et d\'ambre diffusent une énergie bienfaisante chez soi.',
                'featured_image' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=800&q=80',
                'reading_time' => 3,
                'content' => '<p class="lead">Contrairement aux bougies conventionnelles à base de paraffine issue du pétrole, nos cires végétales de soja et de colza brûlent proprement, sans émanations toxiques.</p>
<p>L\'ambre apporte une note ronde et réconfortante, tandis que le cèdre de l\'Atlas ancre la pièce dans une quiétude végétale profonde.</p>',
                'tags' => [$tagSlow->id, $tagDeco->id],
            ],
            [
                'title' => 'Créer un coin lecture baigné de lumière naturelle',
                'slug' => 'creer-un-coin-lecture-lumiere-naturelle',
                'category_id' => $catDesign->id,
                'excerpt' => 'Quelques astuces simples d\'agencement pour transformer une alcôve ou un rebord de fenêtre en havre littéraire.',
                'featured_image' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80',
                'reading_time' => 4,
                'content' => '<p class="lead">Un fauteuil profond, un plaid en lin et une étagère basse à portée de main suffisent à délimiter un espace dédié à l\'évasion.</p>
<p>Orientez toujours votre assise à 45 degrés de la fenêtre pour éviter les éblouissements tout en profitant des déclins du soleil.</p>',
                'tags' => [$tagDeco->id, $tagSlow->id],
            ],
        ];

        $dayOffset = 1;
        foreach ($articles as $art) {
            /** @var \Plugins\Blog\Models\Post $post */
            $post = \Plugins\Blog\Models\Post::query()->firstOrCreate(
                ['slug' => $art['slug']],
                [
                    'author_id' => $author->id,
                    'category_id' => $art['category_id'],
                    'title' => $art['title'],
                    'excerpt' => $art['excerpt'],
                    'content' => $art['content'],
                    'featured_image' => $art['featured_image'],
                    'reading_time' => $art['reading_time'],
                    'status' => 'published',
                    'is_featured' => true,
                    'views_count' => 100 + ($dayOffset * 35),
                    'published_at' => now()->subDays($dayOffset++),
                ]
            );

            $post->tags()->syncWithoutDetaching($art['tags']);
        }
    }

    /**
     * 7. Seed E-Commerce Products & Orders (lcmscore.commerce-lite)
     */
    private function seedCommerce(): void
    {
        if (! Schema::hasTable('plugin_commerce_products') || ! class_exists(\Plugins\CommerceLite\Models\Product::class)) {
            return;
        }

        $products = [
            [
                'name' => 'Tasse en Grès Tourné Main',
                'slug' => 'tasse-gres-tourne-main',
                'sku' => 'CER-MUG-01',
                'price' => 2600, // 26.00 €
                'stock' => 18,
                'description' => 'Tasse façonnée à la main en grès chamotté. Émail naturel satiné couleur ivoire moucheté. Compatible lave-vaisselle et micro-ondes.',
                'image' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Bougie Cire Végétale « Ambre & Cèdre »',
                'slug' => 'bougie-cire-vegetale-ambre-cedre',
                'sku' => 'CAN-AMB-02',
                'price' => 2250, // 22.50 €
                'stock' => 35,
                'description' => 'Cire de soja 100% naturelle coulée à la main en pot d\'ambre réutilisable. Mèche en coton non blanchi. Durée de combustion : 45 heures.',
                'image' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Plaid en Lin Lavé Naturel',
                'slug' => 'plaid-lin-lave-naturel',
                'sku' => 'TEX-LIN-03',
                'price' => 7900, // 79.00 €
                'stock' => 8,
                'description' => 'Plaid 140x200cm en pur lin cultivé en Normandie. Teinte sable naturel, franges délicates faites à la main. Thermorégulateur et ultra-doux.',
                'image' => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Gourde Isotherme Inox & Bois de Hêtre',
                'slug' => 'gourde-isotherme-inox-bois-hetre',
                'sku' => 'BOT-INOX-04',
                'price' => 3490, // 34.90 €
                'stock' => 22,
                'description' => 'Bouteille double paroi 650ml en acier inoxydable 18/8 sans BPA. Bouchon étanche en bois de hêtre certifié FSC. Maintient 12h au chaud et 24h au frais.',
                'image' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Carnet de Notes Papier Recyclé Relié Cuir',
                'slug' => 'carnet-papier-recycle-relie-cuir',
                'sku' => 'NOTE-RECY-05',
                'price' => 1800, // 18.00 €
                'stock' => 40,
                'description' => '160 pages en papier kraft recyclé 120g/m² idéal pour écriture à l\'encre et croquis. Couverture en cuir upcyclé avec lacet de fermeture.',
                'image' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Vase Minimaliste en Céramique Blanche',
                'slug' => 'vase-minimaliste-ceramique-blanche',
                'sku' => 'CER-VASE-06',
                'price' => 4500, // 45.00 €
                'stock' => 12,
                'description' => 'Vase d\'art aux lignes pures inspiré du design scandinave et wabi-sabi. Idéal pour bouquets séchés ou fleurs fraîches du jardin.',
                'image' => 'https://images.unsplash.com/photo-1612196808214-b8e1d6145a8c?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Infuseur à Thé en Verre Borosilicate',
                'slug' => 'infuseur-the-verre-borosilicate',
                'sku' => 'TEA-INF-07',
                'price' => 1650, // 16.50 €
                'stock' => 25,
                'description' => 'Filtre fin transparent résistant aux chocs thermiques. Permet aux feuilles entières de thé et tisanes de libérer tous leurs arômes.',
                'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Savon Surgras aux Huiles Essentielles de Lavande',
                'slug' => 'savon-surgras-lavande',
                'sku' => 'SOAP-LAV-08',
                'price' => 850, // 8.50 €
                'stock' => 50,
                'description' => 'Saponifié à froid en Provence avec de l\'huile d\'olive bio et du beurre de karité. Parfum relaxant de lavande vraie AOP.',
                'image' => 'https://images.unsplash.com/photo-1607006314644-849a941575ca?auto=format&fit=crop&w=600&q=80',
            ],
        ];

        /** @var list<\Plugins\CommerceLite\Models\Product> $createdProducts */
        $createdProducts = [];
        foreach ($products as $p) {
            /** @var \Plugins\CommerceLite\Models\Product $prod */
            $prod = \Plugins\CommerceLite\Models\Product::query()->firstOrCreate(
                ['sku' => $p['sku']],
                [
                    'name' => $p['name'],
                    'slug' => $p['slug'],
                    'price' => $p['price'],
                    'stock' => $p['stock'],
                    'description' => $p['description'],
                    'image' => $p['image'],
                    'is_active' => true,
                ]
            );
            $createdProducts[] = $prod;
        }

        // Test Orders for Admin Dashboard
        if (Schema::hasTable('plugin_commerce_orders') && class_exists(\Plugins\CommerceLite\Models\Order::class)) {
            /** @var \Plugins\CommerceLite\Models\Order $order1 */
            $order1 = \Plugins\CommerceLite\Models\Order::query()->firstOrCreate(
                ['order_number' => 'CMD-202611-1001'],
                [
                    'customer_name' => 'Sophie Laurent',
                    'customer_email' => 'sophie.laurent@example.com',
                    'customer_phone' => '0612345678',
                    'shipping_address' => '8 rue de Bretagne, 75003 Paris, France',
                    'total_amount' => 7450, // 74.50 €
                    'status' => 'paid',
                    'payment_method' => 'stripe_checkout',
                    'paid_at' => now()->subDays(2),
                ]
            );
            $order1->items()->firstOrCreate(
                ['product_name' => 'Tasse en Grès Tourné Main'],
                [
                    'product_id' => $createdProducts[0]->id,
                    'sku' => 'CER-MUG-01',
                    'price' => 2600,
                    'quantity' => 2,
                    'total' => 5200,
                ]
            );
            $order1->items()->firstOrCreate(
                ['product_name' => 'Bougie Cire Végétale « Ambre & Cèdre »'],
                [
                    'product_id' => $createdProducts[1]->id,
                    'sku' => 'CAN-AMB-02',
                    'price' => 2250,
                    'quantity' => 1,
                    'total' => 2250,
                ]
            );

            /** @var \Plugins\CommerceLite\Models\Order $order2 */
            $order2 = \Plugins\CommerceLite\Models\Order::query()->firstOrCreate(
                ['order_number' => 'CMD-202611-1002'],
                [
                    'customer_name' => 'Alexandre Dubois',
                    'customer_email' => 'alex.dubois@example.com',
                    'customer_phone' => '0698765432',
                    'shipping_address' => '15 avenue des Vosges, 67000 Strasbourg, France',
                    'total_amount' => 7900,
                    'status' => 'shipped',
                    'payment_method' => 'stripe_checkout',
                    'paid_at' => now()->subDays(5),
                ]
            );
            $order2->items()->firstOrCreate(
                ['product_name' => 'Plaid en Lin Lavé Naturel'],
                [
                    'product_id' => $createdProducts[2]->id,
                    'sku' => 'TEX-LIN-03',
                    'price' => 7900,
                    'quantity' => 1,
                    'total' => 7900,
                ]
            );

            /** @var \Plugins\CommerceLite\Models\Order $order3 */
            $order3 = \Plugins\CommerceLite\Models\Order::query()->firstOrCreate(
                ['order_number' => 'CMD-202611-1003'],
                [
                    'customer_name' => 'Chloé Vasseur',
                    'customer_email' => 'chloe.vasseur@example.com',
                    'customer_phone' => '0745231298',
                    'shipping_address' => '22 quai des Chartrons, 33000 Bordeaux, France',
                    'total_amount' => 3490,
                    'status' => 'pending',
                    'payment_method' => 'stripe_checkout',
                ]
            );
            $order3->items()->firstOrCreate(
                ['product_name' => 'Gourde Isotherme Inox & Bois de Hêtre'],
                [
                    'product_id' => $createdProducts[3]->id,
                    'sku' => 'BOT-INOX-04',
                    'price' => 3490,
                    'quantity' => 1,
                    'total' => 3490,
                ]
            );
        }
    }

    /**
     * 8. Seed Newsletter Subscribers (lcmscore.newsletter)
     */
    private function seedNewsletter(): void
    {
        if (! Schema::hasTable('plugin_newsletter_subscribers') || ! class_exists(\Plugins\Newsletter\Models\Subscriber::class)) {
            return;
        }

        $subscribers = [
            ['email' => 'lea.bernard@example.com', 'status' => 'confirmed', 'confirmed_at' => now()->subDays(10)],
            ['email' => 'thomas.moreau@example.com', 'status' => 'confirmed', 'confirmed_at' => now()->subDays(4)],
            ['email' => 'mathilde.petit@example.com', 'status' => 'confirmed', 'confirmed_at' => now()->subDays(1)],
            ['email' => 'antoine.roux@example.com', 'status' => 'pending', 'confirmed_at' => null],
            ['email' => 'clara.garcia@example.com', 'status' => 'pending', 'confirmed_at' => null],
        ];

        foreach ($subscribers as $s) {
            \Plugins\Newsletter\Models\Subscriber::query()->firstOrCreate(
                ['email' => $s['email']],
                [
                    'status' => $s['status'],
                    'token' => Str::random(32),
                    'source' => 'home_lead_form',
                    'confirmed_at' => $s['confirmed_at'],
                ]
            );
        }
    }

    /**
     * 9. Seed Analytics Pageviews (lcmscore.analytics)
     */
    private function seedAnalytics(): void
    {
        if (! Schema::hasTable('plugin_analytics_pageviews') || ! class_exists(\Plugins\Analytics\Models\PageView::class)) {
            return;
        }

        $paths = ['/', '/shop', '/blog', '/blog/bienfaits-cafe-filtre-extraction-lente', '/shop/tasse-gres-tourne-main', '/page/a-propos'];
        $devices = ['desktop', 'desktop', 'mobile', 'mobile', 'tablet'];
        $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge'];
        $referrers = ['google.com', 'instagram.com', 'pinterest.com', null];

        for ($i = 0; $i < 30; $i++) {
            $daysAgo = rand(0, 6);
            $createdAt = now()->subDays($daysAgo)->subHours(rand(1, 18));
            $visitorHash = hash('sha256', 'mock_visitor_'.rand(1, 15).'_'.$createdAt->format('Y-m-d'));

            \Plugins\Analytics\Models\PageView::query()->create([
                'path' => $paths[array_rand($paths)],
                'visitor_hash' => $visitorHash,
                'referrer' => $referrers[array_rand($referrers)],
                'referrer_host' => $referrers[array_rand($referrers)],
                'device' => $devices[array_rand($devices)],
                'browser' => $browsers[array_rand($browsers)],
                'status_code' => 200,
                'created_at' => $createdAt,
            ]);
        }
    }

    /**
     * 10. Activate Lumina Lifestyle theme
     */
    private function activateTheme(): void
    {
        if (app()->bound(ThemeManager::class)) {
            /** @var ThemeManager $themeManager */
            $themeManager = app()->make(ThemeManager::class);
            try {
                $themeManager->activate('lumina-lifestyle');
            } catch (\Throwable) {
                // Ignore if in test runner without theme discovery bound
            }
        }
    }
}
