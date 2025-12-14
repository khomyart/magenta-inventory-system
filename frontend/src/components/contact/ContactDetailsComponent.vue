<script setup>
import { computed } from "vue";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  contact: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue"]);

const isShown = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

function getPlatformColor(platform) {
  const colors = {
    call: 'primary',
    sms: 'primary',
    email: 'primary',
    telegram: 'blue',
    viber: 'purple',
    whatsapp: 'green',
    instagram: 'pink',
    other: 'cyan'
  };
  return colors[platform] || 'grey';
}

function getPlatformLabel(platform) {
  const labels = {
    call: 'Дзвінок',
    sms: 'SMS',
    email: 'Email',
    telegram: 'Telegram',
    viber: 'Viber',
    whatsapp: 'Whatsapp',
    instagram: 'Instagram',
    other: 'Інша'
  };
  return labels[platform] || platform;
}
</script>

<template>
  <q-dialog v-model="isShown">
    <q-card style="min-width: 450px">
      <q-card-section>
        <div class="text-h6 flex items-center">
          <q-icon name="person" color="primary" size="md" class="q-mr-sm"/>
          Картка контакту
        </div>
      </q-card-section>
      <q-separator></q-separator>

      <q-card-section v-if="contact" class="q-pt-md">
        <div class="contact-details-card">
          <!-- Name -->
          <div class="detail-section">
            <div class="detail-label">Ім'я</div>
            <div class="detail-value">{{ contact.name }}</div>
          </div>

          <!-- Phone -->
          <div class="detail-section" v-if="contact.phone">
            <div class="detail-label">
              <q-icon name="phone" size="18px" class="q-mr-xs" />
              Телефон
            </div>
            <div class="detail-value phone-container">
              <a :href="`tel:${contact.phone}`" class="phone-link">
                {{ contact.phone }}
              </a>
              <q-btn
                flat
                dense
                round
                size="sm"
                color="primary"
                icon="call"
                :href="`tel:${contact.phone}`"
                class="q-ml-sm"
              >
                <q-tooltip class="bg-black text-body2">
                  Подзвонити
                </q-tooltip>
              </q-btn>
            </div>
          </div>

          <!-- Email -->
          <div class="detail-section" v-if="contact.email">
            <div class="detail-label">
              <q-icon name="email" size="18px" class="q-mr-xs" />
              Email
            </div>
            <div class="detail-value phone-container">
              <a :href="`mailto:${contact.email}`" class="phone-link">
                {{ contact.email }}
              </a>
              <q-btn
                flat
                dense
                round
                size="sm"
                color="primary"
                icon="mail"
                :href="`mailto:${contact.email}`"
                class="q-ml-sm"
              >
                <q-tooltip class="bg-black text-body2">
                  Написати email
                </q-tooltip>
              </q-btn>
            </div>
          </div>

          <!-- Address -->
          <div class="detail-section" v-if="contact.address">
            <div class="detail-label">
              <q-icon name="location_on" size="18px" class="q-mr-xs" />
              Адреса
            </div>
            <div class="detail-value">{{ contact.address }}</div>
          </div>

          <!-- Additional Info -->
          <div class="detail-section" v-if="contact.additional_info">
            <div class="detail-label">
              <q-icon name="info" size="18px" class="q-mr-xs" />
              Додаткова інформація
            </div>
            <div class="detail-value">{{ contact.additional_info }}</div>
          </div>

          <!-- Preferred Platforms -->
          <div class="detail-section" v-if="contact.preferred_platforms && contact.preferred_platforms.length > 0">
            <div class="detail-label">
              <q-icon name="chat" size="18px" class="q-mr-xs" />
              Бажані платформи зв'язку
            </div>
            <div class="detail-value">
              <div class="row q-gutter-xs q-mt-xs">
                <q-chip
                  v-for="platform in contact.preferred_platforms"
                  :key="platform"
                  :color="getPlatformColor(platform)"
                  text-color="white"
                  size="sm"
                >
                  {{ getPlatformLabel(platform) }}
                </q-chip>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat color="black" v-close-popup><b>Закрити</b></q-btn>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.contact-details-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detail-section {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.detail-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.detail-label {
  font-size: 0.85em;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
}

.detail-value {
  font-size: 1em;
  color: #333;
  word-wrap: break-word;
}

.phone-container {
  display: flex;
  align-items: center;
}

.phone-link {
  color: #1976d2;
  text-decoration: none;
  transition: all 0.2s ease;
}

.phone-link:hover {
  color: #1565c0;
  text-decoration: underline;
}
</style>
