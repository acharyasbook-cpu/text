<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/PlatformRepository.php';

final class HeaderController
{
    public function __construct(
        private CourseRepository $courses = new CourseRepository(),
        private PlatformRepository $platform = new PlatformRepository(),
    ) {
    }

    /**
     * @return array{
     *   catalog:list<array>,
     *   logo_url:?string,
     *   site_name:string,
     *   site_name_te:string,
     *   site_tagline_te:string,
     *   user:?array,
     *   active_nav:string,
     *   active_course_slug:?string
     * }
     */
    public function build(string $activeNav = 'home', ?string $activeCourseSlug = null): array
    {
        $logoPath = $this->platform->logoPath();

        return [
            'catalog' => $this->courses->catalogForPublicSite(),
            'logo_url' => $logoPath ? acharya_media_url($logoPath) : null,
            'logo_cache_v' => public_media_cache_version($logoPath),
            'site_name' => $this->platform->siteName(),
            'site_name_te' => $this->platform->siteNameTe(),
            'site_tagline_te' => $this->platform->siteTaglineTe(),
            'user' => current_user(),
            'active_nav' => $activeNav,
            'active_course_slug' => $activeCourseSlug,
        ];
    }
}
