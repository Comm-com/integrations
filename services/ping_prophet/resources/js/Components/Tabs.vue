<template>
  <div>
    <div class="sm:hidden">
      <label for="tabs" class="sr-only">Select a tab</label>
      <select id="tabs" @change="onTabChange($event)" name="tabs" class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
        <option v-for="tab in tabs" :key="tab.name" :selected="tab.current">{{ tab.name }}</option>
      </select>
    </div>
    <div class="hidden sm:block">
      <div class="pb-5">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <a v-for="tab in tabs" :key="tab.name" @click="onClickTabChange(tab)" :href="tab.href" :class="[tab.current ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700', 'rounded-md px-3 py-2 text-sm font-medium']" :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}</a>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import {ref} from "vue";
const emit = defineEmits(['change'])
const onTabChange = (event) => {
  const selectedTabName = event.target.value
  const selectedTab = props.tabs.find((tab) => tab.name === selectedTabName)
  emit('change', selectedTab)
}
const onClickTabChange = (tab) => {
  emit('change', tab)
}

defineProps({
  tabs: Array,
});
</script>