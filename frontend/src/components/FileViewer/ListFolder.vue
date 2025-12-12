<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface FileItem {
    name: string;
    path: string;
    children?: FileItem[];
    isFolder?: boolean;
    extension?: string;
    size?: number;
}

const fileItems = ref<FileItem[]>([]);
const loading = ref(true);

const getFiles = async () => {
    try {
        const { data, response } = await useApi(`/file/listfolders`).post({
            path: 'universidad'
        });

        if (response.value?.ok) {
            fileItems.value = transformData(data.value.data.contents.folders);
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        loading.value = false;
    }
};

const transformData = (folders: any[]): FileItem[] => {
    return folders.map(folder => ({
        name: folder.name,
        path: folder.path,
        isFolder: true,
        children: [
            ...transformData(folder.contents?.folders || []),
            ...(folder.contents?.files?.map(file => ({
                name: file.name,
                path: file.path,
                extension: file.extension,
                size: file.size
            })) || [])
        ]
    }));
};

const getIcon = (item: FileItem) => {
    if (item.isFolder) return 'tabler-folder';
    switch (item.extension) {
        case 'py': return 'tabler-brand-python';
        case 'html': return 'tabler-brand-html5';
        case 'zip': return 'tabler-zip';
        case 'xlsx': return 'tabler-file-spreadsheet';
        case 'txt': return 'tabler-file-text';
        default: return 'tabler-file';
    }
};

onMounted(getFiles);
</script>

<template>
    <v-container>
        <v-progress-circular v-if="loading" indeterminate color="primary" class="d-block mx-auto" />

        <v-treeview v-else :items="fileItems" item-key="path" open-on-click activatable>
            <template #prepend="{ item }">
                <v-icon :icon="getIcon(item)" />
            </template>

            <template #label="{ item }">
                <span class="text-body-2">{{ item.name }}</span>
                <v-chip v-if="!item.isFolder" size="x-small" class="ml-2" color="grey-lighten-2">
                    {{ item.size ? `${(item.size / 1024 / 1024).toFixed(2)} MB` : 'N/A' }}
                </v-chip>
            </template>
        </v-treeview>
    </v-container>
</template>
