export type PostStatus = 'draft' | 'published';

export type BlogAuthor = {
    id: number;
    name: string;
    slug: string | null;
    avatar: string | null;
};

export type BlogPostCard = {
    id: number;
    title: string;
    slug: string;
    summary: string;
    cover_url: string | null;
    reading_minutes: number;
    published_at: string | null;
    author: BlogAuthor | null;
};

export type BlogPostDetail = Omit<BlogPostCard, 'author'> & {
    excerpt: string | null;
    content: string | null;
    updated_at: string | null;
    is_live: boolean;
    author:
        | (BlogAuthor & { headline: string | null; is_instructor: boolean })
        | null;
};

export type AdminPost = {
    id: number;
    title: string;
    slug: string;
    status: PostStatus;
    is_live: boolean;
    published_at: string | null;
    updated_at: string | null;
    cover_url: string | null;
    author: string | null;
};

export type PostFormValues = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;
    status: PostStatus;
    cover_url: string | null;
    published_at: string | null;
    is_live: boolean;
};
