<?php

namespace Database\Seeders;

use App\Modules\School\Models\School;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use Illuminate\Database\Seeder;

/**
 * Publishes the standard public pages referenced by the site navigation
 * (About/History/Mission/Administration, Staff/Teachers, Online admission,
 * Gallery/Video, Contact, Notices) with real block content, so the whole public
 * site is navigable right after install. Staff/notices/stats blocks pull live
 * data seeded by DemoDataSeeder.
 */
class WebsitePagesSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) {
            return;
        }
        $sid = $school->id;

        $links = [
            ['label' => 'Notices', 'url' => '/notices'],
            ['label' => 'Staff', 'url' => '/staff'],
            ['label' => 'Online admission', 'url' => '/online-admission'],
            ['label' => 'Contact', 'url' => '/contact'],
        ];

        // ── About / identity pages ──────────────────────────────────────────
        $this->page($sid, 'history', 'Short History', 'sidebar',
            [
                ['type' => 'heading', 'data' => ['text' => 'A proud history']],
                ['type' => 'richtext', 'data' => ['html' =>
                    '<p>Green Valley Model School, founded in 1985 in Natipota, Damurhuda, Chuadanga, is a traditional '
                    . 'institution that has played an important role in spreading education for decades. Known for its '
                    . 'dedicated teachers, well-planned curriculum, and disciplined learning environment, the school gives '
                    . 'equal importance to academic excellence and the moral and physical development of its students.</p>']],
            ],
            [
                ['type' => 'quick_links', 'data' => ['heading' => 'Quick links', 'links' => $links]],
                ['type' => 'contact_info', 'data' => ['heading' => 'Contact']],
            ],
        );

        $this->page($sid, 'about', 'At a Glance', 'full', [
            ['type' => 'heading', 'data' => ['text' => 'At a glance']],
            ['type' => 'richtext', 'data' => ['html' =>
                '<p>We offer education from class Six to Ten with a focus on academic excellence, discipline, and '
                . 'character. Our campus provides a safe, supportive environment where every student can thrive.</p>']],
            ['type' => 'stats', 'data' => ['heading' => 'Our school in numbers']],
        ]);

        $this->page($sid, 'mission', 'Mission & Vision', 'full', [
            ['type' => 'heading', 'data' => ['text' => 'Mission & vision']],
            ['type' => 'richtext', 'data' => ['html' =>
                '<h5>Our mission</h5><p>To nurture curious minds and build a community of lifelong learners through '
                . 'quality education and strong values.</p><h5>Our vision</h5><p>To be a leading institution recognised '
                . 'for academic excellence, integrity, and service to the community.</p>']],
        ]);

        $this->page($sid, 'administration', 'Administration', 'full', [
            ['type' => 'heading', 'data' => ['text' => 'Administration']],
            ['type' => 'richtext', 'data' => ['html' => '<p>Our administrative team leads the school with dedication and care.</p>']],
            ['type' => 'staff', 'data' => ['heading' => 'Administrative team']],
        ]);

        // ── Staff pages ─────────────────────────────────────────────────────
        $this->page($sid, 'staff', 'All Staff', 'full', [
            ['type' => 'staff', 'data' => ['heading' => 'All staff']],
        ]);
        $this->page($sid, 'teachers', 'Teachers', 'full', [
            ['type' => 'staff', 'data' => ['heading' => 'Our teachers']],
        ]);

        // ── Online admission ────────────────────────────────────────────────
        $this->page($sid, 'online-admission', 'Online Admission', 'full', [
            ['type' => 'heading', 'data' => ['text' => 'Online admission']],
            ['type' => 'richtext', 'data' => ['html' =>
                '<p>Admission for the new academic year is now open for classes Six to Ten. '
                . 'Please apply online or visit the school office during working hours.</p>']],
            ['type' => 'admission_form', 'data' => ['heading' => 'Apply for admission', 'intro' => 'Start your online application below.']],
        ]);

        // ── Galleries ───────────────────────────────────────────────────────
        $this->page($sid, 'gallery', 'Photo Gallery', 'full', [
            ['type' => 'heading', 'data' => ['text' => 'Photo gallery']],
            ['type' => 'gallery_photo', 'data' => ['images' => array_map(
                fn ($i) => "https://picsum.photos/seed/greenvalley{$i}/600/400", range(1, 8),
            )]],
        ]);
        $this->page($sid, 'video', 'Video Gallery', 'full', [
            ['type' => 'heading', 'data' => ['text' => 'Video gallery']],
            ['type' => 'gallery_video', 'data' => ['videos' => [
                'https://www.youtube.com/embed/aqz-KE-bpKQ',
                'https://www.youtube.com/embed/ScMzIvxBSi4',
            ]]],
        ]);

        // ── Contact ─────────────────────────────────────────────────────────
        $this->page($sid, 'contact', 'Contact', 'sidebar',
            [
                ['type' => 'contact', 'data' => ['heading' => 'Get in touch']],
            ],
            [
                ['type' => 'contact_info', 'data' => ['heading' => 'Contact details']],
                ['type' => 'office_hours', 'data' => ['heading' => 'Office hours', 'lines' => [
                    ['label' => 'Sunday – Thursday', 'value' => '8:00 AM – 4:00 PM'],
                    ['label' => 'Friday – Saturday', 'value' => 'Closed'],
                ]]],
            ],
        );

        // ── Notices ─────────────────────────────────────────────────────────
        $this->page($sid, 'notices', 'Notices', 'full', [
            ['type' => 'heading', 'data' => ['text' => 'Notices']],
            ['type' => 'notices', 'data' => ['heading' => 'All notices', 'limit' => 20]],
        ]);
    }

    /**
     * Create (or refresh) a published page with a single layout revision.
     *
     * @param  array<int, array{type: string, data: array}>  $blocks
     * @param  array<int, array{type: string, data: array}>  $sidebar
     */
    private function page(int $sid, string $slug, string $title, string $template, array $blocks, array $sidebar = []): void
    {
        $page = Page::updateOrCreate(
            ['school_id' => $sid, 'slug' => $slug],
            ['title' => $title, 'status' => 'published', 'is_homepage' => false],
        );

        PageLayout::where('page_id', $page->id)->delete();
        PageLayout::create([
            'school_id'    => $sid,
            'page_id'      => $page->id,
            'layout_json'  => ['template' => $template, 'blocks' => $blocks, 'sidebar' => $sidebar],
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
