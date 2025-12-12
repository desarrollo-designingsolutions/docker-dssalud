<script setup lang="ts">
import List2275 from "./Components/Components2275/List2275.vue";
import ListOld from "./Components/ComponentsOld/ListOld.vue";


definePage({
  path: "Filing/New/List/:type/:id",
  name: "Filing-New-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "filing.new.list",
  },
});

const route = useRoute()
const type = route.params.type;
</script>

<template>
  <div>
    <ListOld v-if="type == 'FILING_TYPE_001'"></ListOld>
    <List2275 v-if="type == 'FILING_TYPE_002'"></List2275>
  </div>
</template>
