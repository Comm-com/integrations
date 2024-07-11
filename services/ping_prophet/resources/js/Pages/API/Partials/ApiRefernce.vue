<script setup>
import FormSection from '@/Components/FormSection.vue';
</script>

<template>
    <div>
        <!-- Generate API Token -->
        <FormSection :is-content="true">
            <template #title>
                API Reference
            </template>

            <template #description>
                View the API endpoints to send requests to <a class="inline-flex items-center font-semibold text-indigo-700 dark:text-indigo-300" href="/docs/api">here</a>
            </template>

            <template #form>
            </template>
        </FormSection>
    </div>
</template>
