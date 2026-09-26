<script setup lang="ts">
// Social network marks for the footer. @lucide/vue v1 dropped brand icons, so these
// are drawn in the same 24px, 2px-stroke outline style as the lucide set.
defineProps<{
    network: string;
}>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="network === 'instagram'">
            <rect x="2" y="2" width="20" height="20" rx="5" />
            <circle cx="12" cy="12" r="4" />
            <path d="M17.5 6.5h.01" />
        </template>
        <template v-else-if="network === 'youtube'">
            <path
                d="M2.5 17a24.1 24.1 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.6 49.6 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.1 24.1 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.6 49.6 0 0 1-16.2 0A2 2 0 0 1 2.5 17"
            />
            <path d="m10 15 5-3-5-3z" />
        </template>
        <template v-else-if="network === 'facebook'">
            <path
                d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"
            />
        </template>
        <template v-else-if="network === 'linkedin'">
            <path
                d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"
            />
            <rect x="2" y="9" width="4" height="12" />
            <circle cx="4" cy="4" r="2" />
        </template>
        <template v-else>
            <!-- TikTok -->
            <path d="M15 3a5 5 0 0 0 5 5" />
            <path d="M15 3v12a5 5 0 1 1-5-5" />
        </template>
    </svg>
</template>
