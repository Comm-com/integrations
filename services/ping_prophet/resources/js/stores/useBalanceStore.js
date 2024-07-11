import {defineStore} from "pinia";

defineStore('balance', {
    state: () => ({
        balance: 0,
        currentTab: 'balance',
    }),
    
})