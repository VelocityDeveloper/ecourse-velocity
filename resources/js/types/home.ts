export type HomeStats = {
    courses: number;
    lessons: number;
    students: number;
    instructors: number;
};

export type HomeCategory = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image_url: string | null;
    courses_count: number;
};

export type HomeInstructor = {
    id: number;
    name: string;
    slug: string;
    avatar: string | null;
    headline: string | null;
    courses_count: number;
};

/**
 * The homepage banner texts from Admin → Pengaturan Situs (defaults filled in by the server).
 */
export type HomeBanner = {
    hero_badge: string;
    hero_title: string;
    hero_highlight: string;
    hero_description: string;
    hero_image_url: string | null;
    cta_title: string;
    cta_description: string;
};

export type BannerTextKey =
    | 'hero_badge'
    | 'hero_title'
    | 'hero_highlight'
    | 'hero_description'
    | 'cta_title'
    | 'cta_description';

/**
 * The banner section of Admin → Pengaturan Situs: saved texts (null = default) and the defaults.
 */
export type BannerSettingsData = {
    texts: Record<BannerTextKey, string | null>;
    defaults: Record<BannerTextKey, string>;
    heroImageUrl: string | null;
};

/**
 * A promo slide from Admin → Pengaturan Situs → Banner Promo.
 */
export type HomeBannerSlide = {
    id: number;
    title: string;
    image_url: string;
    link_url: string | null;
};

/**
 * An alumni story on the homepage: from Admin → Pengaturan Situs → Testimoni,
 * or a course review while no testimonial has been added.
 */
export type HomeTestimonial = {
    id: string;
    name: string;
    subtitle: string | null;
    quote: string;
    rating: number;
    avatar: string | null;
};

/**
 * A testimonial as the admin page lists it.
 */
export type AdminTestimonial = {
    id: number;
    name: string;
    display_name: string;
    subtitle: string | null;
    quote: string;
    rating: number;
    photo_url: string | null;
    mask_name: boolean;
    is_active: boolean;
    sort_order: number;
};

/**
 * The public identity and contact details shared with every page (footer).
 */
export type PublicSite = {
    description: string;
    address: string | null;
    email: string | null;
    phone: string | null;
    whatsapp_url: string | null;
    hours: string | null;
    socials: { network: string; url: string }[];
};

/**
 * A promo banner as the admin page lists it.
 */
export type AdminBanner = HomeBannerSlide & {
    is_active: boolean;
    sort_order: number;
};
