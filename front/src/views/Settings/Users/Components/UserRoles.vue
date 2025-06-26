<template>
  <v-card>
<v-card-text>


<div v-if="availableRoles.length > 0">
  <div  v-for="role in availableRoles" :key="role.name" class="d-flex align-center py-2">
    <div class="flex-grow-1">
      <div class="font-weight-medium">{{ role.name }}</div>
      <div v-if="role.description" class="text-caption text-grey">{{ role.description }}</div>
    </div>
    <v-switch
    :value="role.name"
    v-model="selectedRoles"
    :loading="loadingRoles"
    :disabled="loadingRoles"
    @change="updateUserRoles"
    color="primary"
    hide-details
    class="ml-2"
    ></v-switch>
  </div>
</div>
  <div v-else-if="loadingRoles">
    Cargando roles...
  </div>
  <div v-else-if="errorRoles">
    Error al cargar los roles.
  </div>
  <div v-else>
    No hay roles disponibles.
  </div>
  </v-card-text>
  </v-card>
</template>

<script setup>
import axios from '@/services/axios';
import { ref, onMounted } from 'vue';

const props = defineProps({
  userId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['roles-updated']);

const availableRoles = ref([]);
const selectedRoles = ref([]);
const loadingRoles = ref(false);
const errorRoles = ref(false);

const fetchRoles = async () => {
  loadingRoles.value = true;
  errorRoles.value = false;
  try {
    const response = await axios.get('/api/roles');
    availableRoles.value = response.data.data.data; // Accedemos al array de roles dentro de 'data.data'
  } catch (error) {
    console.error('Error al cargar los roles:', error);
    errorRoles.value = true;
  } finally {
    loadingRoles.value = false;
  }
};

const fetchUserRoles = async () => {
  loadingRoles.value = true;
  errorRoles.value = false;
  try {
    const response = await axios.get(`/api/users/${props.userId}/roles`);
    selectedRoles.value = response.data.data.map(role => role.name);
  } catch (error) {
    console.error('Error al cargar los roles del usuario:', error);
    errorRoles.value = true;
  } finally {
    loadingRoles.value = false;
  }
};

const updateUserRoles = async (newRoles) => {
  try {
    const response = await axios.put(`/api/users/${props.userId}/roles`, { roles: selectedRoles.value });
    if (response.status === 200) {
      emit('roles-updated', newRoles);
      // Puedes mostrar un mensaje de éxito aquí
    } else {
      console.error('Error al actualizar los roles del usuario:', response);
      // Puedes mostrar un mensaje de error aquí
    }
  } catch (error) {
    console.error('Error al actualizar los roles del usuario:', error);
    // Puedes mostrar un mensaje de error aquí
  }
};

onMounted(async () => {
  await fetchRoles();
  await fetchUserRoles();
});
</script>
