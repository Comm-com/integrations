<script setup>
import {UButton, UCard, UTable} from "nuxt-ui-vue";
import {Link} from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const integration =   {
  id: 1,
  company: 'Comm.com',
  is_active: false,
  description: 'Comm.com is a messaging platform that helps businesses connect with their customers. With PingProphet you can validate your contacts within the platform.'
};
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
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ integration.company }}
          </h2>

          <p class="text-sm mt-5 text-gray-500 dark:text-gray-400 whitespace-normal">{{ integration.description }}</p>

        </UCard>
      </div>
    </div>
  </AppLayout></template>

<style scoped>

</style>