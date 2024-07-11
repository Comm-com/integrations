<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Tabs from "@/Components/Tabs.vue";
import {ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import {UTable} from "nuxt-ui-vue";
const tabs = ref([
  { name: 'Overview', href: '#', current: true },
  { name: 'Add funds', href: '#', current: false },
])
const page = usePage();

const onTabChange = (tab) => {
  tabs.value.forEach((t) => {
    t.current = t.name === tab.name
  })
  
  currentTab.value = tab.name
}
const currentTab = ref('Overview')
defineProps({
  transactions: Array,
})
</script>

<template>
    <AppLayout title="Balance">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Balance
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
              <Tabs :tabs="tabs" @change="onTabChange" />
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                  <UTable :data="transactions.data" v-if="currentTab === 'Overview'" />
                  <div v-else>
                    <h1>Add funds</h1>
                  </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
