<script setup lang="ts">
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Code,
    Heading2,
    Heading3,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Strikethrough,
    Undo2,
} from '@lucide/vue';
import { onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void;
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            link: { openOnClick: false, autolink: true },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose-editor min-h-72 w-full px-4 py-3 outline-none',
        },
    },
    onUpdate: ({ editor: instance }) => {
        emit('update:modelValue', instance.isEmpty ? '' : instance.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        const instance = editor.value;

        if (instance === undefined || instance.getHTML() === value) {
            return;
        }

        instance.commands.setContent(value, { emitUpdate: false });
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function setLink() {
    const instance = editor.value;

    if (instance === undefined) {
        return;
    }

    const current = String(instance.getAttributes('link').href ?? '');
    const url = window.prompt('Link URL', current);

    if (url === null) {
        return;
    }

    if (url === '') {
        instance.chain().focus().extendMarkRange('link').unsetLink().run();

        return;
    }

    instance
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run();
}
</script>

<template>
    <div
        class="overflow-hidden rounded-md border border-input focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
    >
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-0.5 border-b bg-muted/40 p-1"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('bold') ? 'bg-accent' : ''"
                aria-label="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('italic') ? 'bg-accent' : ''"
                aria-label="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('strike') ? 'bg-accent' : ''"
                aria-label="Strikethrough"
                @click="editor.chain().focus().toggleStrike().run()"
            >
                <Strikethrough class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('code') ? 'bg-accent' : ''"
                aria-label="Inline code"
                @click="editor.chain().focus().toggleCode().run()"
            >
                <Code class="h-4 w-4" />
            </Button>

            <span class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="
                    editor.isActive('heading', { level: 2 }) ? 'bg-accent' : ''
                "
                aria-label="Heading 2"
                @click="
                    editor.chain().focus().toggleHeading({ level: 2 }).run()
                "
            >
                <Heading2 class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="
                    editor.isActive('heading', { level: 3 }) ? 'bg-accent' : ''
                "
                aria-label="Heading 3"
                @click="
                    editor.chain().focus().toggleHeading({ level: 3 }).run()
                "
            >
                <Heading3 class="h-4 w-4" />
            </Button>

            <span class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('bulletList') ? 'bg-accent' : ''"
                aria-label="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('orderedList') ? 'bg-accent' : ''"
                aria-label="Numbered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('blockquote') ? 'bg-accent' : ''"
                aria-label="Quote"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                aria-label="Divider"
                @click="editor.chain().focus().setHorizontalRule().run()"
            >
                <Minus class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="editor.isActive('link') ? 'bg-accent' : ''"
                aria-label="Link"
                @click="setLink"
            >
                <LinkIcon class="h-4 w-4" />
            </Button>

            <span class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="!editor.can().undo()"
                aria-label="Undo"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo2 class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="!editor.can().redo()"
                aria-label="Redo"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo2 class="h-4 w-4" />
            </Button>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>
