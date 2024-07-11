<script setup>
import {ref, onMounted} from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import {UTable, UDropdown, UCard, UButton, ULink} from "nuxt-ui-vue";
import { Link } from '@inertiajs/vue3'

const integrations = [
  {
    id: 1,
    company: 'Comm.com',
    is_active: false,
    description: 'Comm.com is a messaging platform that helps businesses connect with their customers. With PingProphet you can validate your contacts within the platform.'
  }
];

const columns = [
  {
    key: 'company',
    label: 'Company',
  },
  {
    key: 'description',
    label: 'Description',
  },
  {
    key: 'is_active',
    label: '',
    component: UButton,
    props: {
      options: [
        {label: 'Disable', value: true},
        {label: 'Activate', value: false},
      ],
    },
  },
];
</script>

<template>
  <AppLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Integrations
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <UCard>
          <div class="overflow-x-auto">
            <UTable :rows="integrations" :columns="columns">
              <template #description-data="{row}">
                <p class="text-sm text-gray-500 dark:text-gray-400 whitespace-normal">{{ row.description }}</p>
              </template>
              <template #is_active-data="{row}">
                <Link :href="'/integrations/' + row.id">
                  <UButton :value="row.is_active" :label="row.is_active ? 'Disable': 'Activate'"/>
                </Link>
              </template>
            </UTable>
          </div>
        </UCard>
      </div>
    </div>
  </AppLayout>
</template>
