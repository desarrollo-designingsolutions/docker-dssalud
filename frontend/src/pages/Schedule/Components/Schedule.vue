<script setup lang="ts">
import ModalFormConciliation from '@/pages/Schedule/Components/ModalFormConciliation.vue'
import ModalViewEventConciliation from '@/pages/Schedule/Components/ModalViewEventConciliation.vue'
import { useAuthenticationStore } from "@/stores/useAuthenticationStore"
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import timeGridPlugin from '@fullcalendar/timegrid'
import FullCalendar from '@fullcalendar/vue3'
import { debounce } from 'lodash'
import moment from 'moment'


const authenticationStore = useAuthenticationStore();
const refCalendar = ref()
const events = ref<Array<object>>([])
const loading = reactive({
  calendar: false
});
const typeEvents = ref<Array<{
  value: string
  title: string
  color: string
}>>([])
const usersTasks = ref<Array<{
  value: string
  title: string
}>>([])

const user_id = ref()
const selectedDate = ref(moment().toISOString().substr(0, 10))
const selectedCalendars = ref([])
const options = reactive({
  plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  timeZone: 'America/Bogota',
  headerToolbar: {
    left: "prev,next,today",
    center: "title",
    right: "dayGridMonth,timeGridFourDay,timeGridDay,listYear"
  },
  views: {
    timeGridFourDay: {
      type: 'timeGrid',
      duration: { days: 7 },
    }
  },
  buttonText: {
    month: 'Mes',
    week: 'Semana',
    day: 'Dia',
    today: 'Hoy',
    list: 'Lista'
  },

  locale: 'es',
  editable: false,
  selectable: false,
  weekends: true,
  navLinks: false,

  events: [],

  displayEventTime: false,

  dayMaxEvents: 4, // Límite de eventos por día


  // Personalizar el texto de "more"
  moreLinkText: (eventCount) => `más (${eventCount})`,

 eventContent: function (arg: any) {
    const htmls = `
      <div data-event-id="${arg.event.id}" data-action="edit" style="width: 100%; background: ${arg.backgroundColor}; color:white; padding: 0 10px; cursor:pointer;"  >
        <b ><p data-event-id="${arg.event.id}" data-action="edit"  style="margin: 0px !important;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">${arg.event._def.title}</p></b>
      </div>`;

    return { html:htmls};
  },

  dateClick: (daySelected: any) => {
    const cal = daySelected.view.calendar

    const day = daySelected.dateStr.substring(0, 10)

    cal.unselect()
    openModalFormConciliation(null, day);
  },

  eventClick: async (arg: any) => {
    const eventId = arg.jsEvent.target.dataset.eventId

    if (eventId && arg.event._def.extendedProps.type === 'event') {
      openModalViewEventConciliation(eventId);
    }
  },



  customButtons: {
    prev: {
      text: 'Prev',
      async click() {
        loading.calendar = true
        const calendarApi = refCalendar.value.getApi();
        calendarApi.prev(); // Cambiar al mes anterior
        debouncedClick(); // Llamamos a la función debounced
      }
    },
    next: {
      text: 'Next',
      click() {
        const calendarApi = refCalendar.value.getApi();
        calendarApi.next(); // Cambiar al siguiente mes
        debouncedClick(); // Llamamos a la función debounced
      }
    },
    dayGridMonth: {
      text: 'Mes',
      async click() {
        const calendarApi = refCalendar.value.getApi();
        calendarApi.changeView('dayGridMonth'); // Cambiar a la vista de mes
        debouncedClick(); // Llamamos a la función debounced
      }
    },
    timeGridDay: {
      text: 'Día',
      async click() {
        loading.calendar = true

        const calendarApi = refCalendar.value.getApi();
        calendarApi.changeView('timeGridDay'); // Cambiar a la vista de día
        debouncedClick(); // Llamamos a la función debounced

      }
    },
    timeGridFourDay: {
      text: 'Semana',
      async click() {
        loading.calendar = true

        const calendarApi = refCalendar.value.getApi();
        calendarApi.changeView('timeGridFourDay'); // Cambiar a la vista de semana
        debouncedClick(); // Llamamos a la función debounced
      }
    },
    listYear: {
      text: 'Lista',
      async click() {
        loading.calendar = true

        const calendarApi = refCalendar.value.getApi();
        calendarApi.changeView('listYear'); // Cambiar a la vista de semana
        debouncedClick(); // Llamamos a la función debounced
      }
    },
  },

  setEvents: function (eventsArray) {
    // Sort events by the start date in descending order
    eventsArray.sort((a, b) => new Date(b.start) - new Date(a.start));
    this.events = eventsArray; // Assign sorted events to the calendar
  }

})

const fetchCalendarEvents = async (params: object | null) => {
  loading.calendar = true;

  let url = '/schedule/index';
  let dateStart = null;
  let dateEnd = null;
  if (params?.start && params?.end) {
    dateStart = new Date(params?.start); // Convertir la cadena de texto a un objeto Date
    dateStart = dateStart.toISOString().split("T")[0]; // Convertir la fecha a formato ISO
    dateEnd = new Date(params?.end); // Convertir la cadena de texto a un objeto Date
    dateEnd = dateEnd.toISOString().split("T")[0]; // Convertir la fecha a formato ISO
  }

  const { data, response } = await useAxios(url).get({
    params: {
      dateStart: dateStart,
      dateFinal: dateEnd,
      company_id: authenticationStore.company.id,
      user_id: authenticationStore.user.role_id == ROLE_SUPERADMIN_UUID ? null : authenticationStore.user.id,
      users_ids: usersTasks.value.length > 0 ? JSON.stringify(usersTasks.value) : null,
    }
  });

  if (response.status == 200 && data) {
    events.value = data.events;
    typeEvents.value = data.typeEventEnum_arrayInfo;
    if (selectedCalendars.value.length == 0) {
      selectedCalendars.value = typeEvents.value.map(i => i.value)
    }
  }

  loading.calendar = false;

  return data;
};

const deleteData = async () => {
  loadEventsRange();  
};

const loadEventsRange = async () => {
  setTimeout(async () => {
    const calendarApi = refCalendar.value.getApi();

    const renderRange = calendarApi.currentData.dateProfile.renderRange
    await fetchCalendarEvents(renderRange);
    updateCheckSelecteds()
  }, 1000);
}
watch(events, () => {
  options.events = events.value;
});

// Observar cambios en selectedDate y actualizar el FullCalendar
watch(selectedDate, (newDate) => {
  const calendarInstance = refCalendar.value.getApi(); // Obtener la instancia de FullCalendar
  calendarInstance.gotoDate(newDate); // Ir a la fecha seleccionada en FullCalendar
  calendarInstance.render(); // Renderizar el calendario para aplicar cambios
});



// 👉 Check all
const checkAll = computed({
  /*
    GET: Return boolean `true` => if length of options matches length of selected filters => Length matches when all events are selected
    SET: If value is `true` => then add all available options in selected filters => Select All
          Else if => all filters are selected (by checking length of both array) => Empty Selected array  => Deselect All
  */
  get: () => selectedCalendars.value.length === typeEvents.value.length,
  set: val => {

    if (val) {
      selectedCalendars.value = typeEvents.value.map(i => i.value)
    }

    else if (selectedCalendars.value.length === typeEvents.value.length)
      selectedCalendars.value = []
  },
})

// Observar cambios en selectedDate y actualizar el FullCalendar
watchArray([selectedCalendars, user_id], (newVal) => {
  updateCheckSelecteds()
});

const updateCheckSelecteds = () => {
  // Suponiendo que options.events es tu array de eventos y selectedCalendars es tu array de calendarios seleccionados
  let filteredEvents = options.events.filter(event => {
    // Si no tiene type_event_id (por ejemplo, es un feriado), inclúyelo siempre
    if (!event.type_event_id) return true;

    // Si tiene type_event_id, verifica si está seleccionado
    return selectedCalendars.value.includes(event.type_event_id);
  });

  // if (user_id.value) {
  //   filteredEvents = filteredEvents.filter(event => {

  //     return user_id.value == event.user_id;
  //   });
  // }
  // Limpiar eventos existentes en el calendario
  const calendarInstance = refCalendar.value.getApi(); // Obtener la instancia de FullCalendar
  calendarInstance.getEvents().forEach(event => event.remove());

  // Agregar eventos filtrados al calendario
  filteredEvents.forEach(event => calendarInstance.addEvent(event));
}


loading.calendar = true

onMounted(async () => {

  await loadEventsRange()
})



const onViewChanged = (event) => {
  const currentView = event.view.type; // Obtener el tipo de vista actual

  // Aquí puedes agregar cualquier acción extra que desees realizar
  // Por ejemplo, ejecutar una función adicional dependiendo de la vista seleccionada
  if (currentView === 'dayGridMonth') {
    // Realizar alguna acción específica para la vista de mes
  } else if (currentView === 'timeGridWeek') {
    // Realizar alguna acción específica para la vista de semana
  } else if (currentView === 'timeGridDay') {
    // Realizar alguna acción específica para la vista de día
  }
};

// Crear la función debounced fuera del click
const debouncedClick = debounce(async () => {
  await loadEventsRange();
}, 500);

// ModalViewEventConciliation
const refModalViewEventConciliation = ref();

const openModalViewEventConciliation = (eventId: any) => {
  refModalViewEventConciliation.value.openModal(eventId);
};

// ModalFormConciliation
const refModalFormConciliation = ref();

const openModalFormConciliation = (eventId: any = null, day: any = null) => {
  if (!day) {
    day = selectedDate.value.substring(0, 10)
  }
  refModalFormConciliation.value.openModal(eventId, day);
};

const openModalFormConciliationToReschedule = (eventId: any = null) => {
  refModalFormConciliation.value.openModalToReschedule(eventId);
};
</script>

<template>
  <div>
    <AppCardActions noActions>

      <VLayout style="z-index: 0;">
        <!-- 👉 Navigation drawer -->
        <VNavigationDrawer width="292" absolute touchless location="start" class="calendar-add-event-drawer">
          <div style="margin: 1.4rem;">
            <VBtn block prepend-icon="tabler-calendar" @click="openModalFormConciliation()">
              Crear Evento
            </VBtn>
          </div>
          <VDivider />

          <div class="d-flex align-center justify-center pa-2 mb-3">
            <AppDateTimePicker v-model="selectedDate" :config="{ inline: true }" class="calendar-date-picker" />
          </div>

          <VDivider />
          <div class="pa-7" v-if="usersTasks.length > 1">
            <AppSelect v-model="user_id" :items="usersTasks" label="Seleccione usuario" />
          </div>
          <VDivider />
          <div class="pa-7">
            <p class="text-sm text-uppercase text-disabled mb-3">
              FILTRO
            </p>

            <div class="d-flex flex-column calendars-checkbox">
              <VCheckbox v-model="checkAll" label="Todos" />
              <VCheckbox v-for="(item, index) in typeEvents" :key="index" v-model="selectedCalendars" :value="item.value"
                 :color="item.color" :label="item.title" />
            </div>
          </div>
        </VNavigationDrawer>

        <VMain>
          <VCard flat>
            <FullCalendar ref="refCalendar" :options="options" @viewDidChange="onViewChanged" />
          </VCard>
        </VMain>
      </VLayout>

    </AppCardActions>

    <ModalViewEventConciliation ref="refModalViewEventConciliation" @modify="openModalFormConciliation($event)" @delete="deleteData" @reschedule="openModalFormConciliationToReschedule"/>
    <ModalFormConciliation ref="refModalFormConciliation" @execute="loadEventsRange()" />
  </div>
</template>


<style lang="scss">
@use "@core/scss/template/libs/full-calendar";

.calendars-checkbox {
  .v-label {
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
    opacity: var(--v-high-emphasis-opacity);
  }
}

.calendar-add-event-drawer {
  &.v-navigation-drawer:not(.v-navigation-drawer--temporary) {
    border-end-start-radius: 0.375rem;
    border-start-start-radius: 0.375rem;
  }
}

.calendar-date-picker {
  display: none;

  +.flatpickr-input {
    +.flatpickr-calendar.inline {
      border: none;
      box-shadow: none;

      .flatpickr-months {
        border-block-end: none;
      }
    }
  }

  & ~ .flatpickr-calendar .flatpickr-weekdays {
    margin-block: 0 4px;
  }
}
</style>

<style lang="scss" scoped>
.v-layout {
  overflow: visible !important;

  .v-card {
    overflow: visible;
  }
}
</style>
