<template>
  <tr :height="props.gap"></tr>
  <tr
    class="item"
    :class="{
      updated: isUpdated,
    }"
    :id="`item${props.itemInfo.id}`"
  >
    <td class="item-cell">
      <div>
        <q-btn icon="list" round flat>
          <q-menu :offset="[5, 5]">
            <q-list style="min-width: 100px">
              <q-item
                clickable
                v-close-popup
                v-if="props.itemInfo.images.length != 0"
                @click="showImage = !showImage"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" name="photo" left></q-icon>
                  <span>Показати зображення</span>
                </div>
              </q-item>
              <q-item
                v-if="props.allowenses.update == true"
                clickable
                v-close-popup
                @click="$emit('showEditDialog', props.itemInfo)"
              >
                <div class="context-menu-item">
                  <q-icon size="sm" name="edit" left></q-icon>
                  <span>Редагувати</span>
                </div>
              </q-item>
              <q-item
                v-if="props.allowenses.delete == true"
                clickable
                v-close-popup
                @click="
                  $emit(
                    'showRemoveDialog',
                    props.itemInfo.id,
                    props.itemInfo.name
                  )
                "
              >
                <div class="context-menu-item">
                  <q-icon size="sm" color="red" name="delete" left></q-icon>
                  <span>Видалити</span>
                </div>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.article, 'Артикль')"
      >
        <div class="item-text">
          {{ props.itemInfo.article }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.title, 'Назву')"
      >
        <div class="item-text">
          {{ props.itemInfo.title }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', priceForCopying, 'Ціну')"
      >
        <div class="item-text">
          {{ `${currencyIcon}${unconvertedPrice} ${convertedPrice}` }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.type_name, 'Тип')"
      >
        <div class="item-text">
          {{ props.itemInfo.type_name }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.gender, 'Гендер')"
      >
        <div class="item-text">
          {{ props.itemInfo.gender }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div
        :id="`size-of-item-${props.itemInfo.id}`"
        style="cursor: pointer"
        @click="
          $emit(
            'copyValue',
            `${props.itemInfo.size_name} - ${props.itemInfo.size_description}`,
            'Розмір'
          )
        "
      >
        <div class="item-text">
          {{ props.itemInfo.size_name }}
        </div>

        <div class="item-size">
          <q-tooltip
            :offset="[0, 5]"
            :target="`#size-of-item-${props.itemInfo.id}`"
            class="bg-black text-body2"
            anchor="center left"
            self="center right"
            >{{ props.itemInfo.size_description }}</q-tooltip
          >
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div class="item-color-container q-px-sm">
        <div
          :id="`color-of-item-${props.itemInfo.id}`"
          class="item-color q-px-sm"
          :style="`background-color: ${props.itemInfo.color_value};`"
          @click="$emit('copyValue', props.itemInfo.color_value, 'Колір')"
        >
          <span
            :style="`color: ${props.itemInfo.text_color_value}`"
            class="item-text"
            ><b>{{ props.itemInfo.color_article }}</b></span
          >
        </div>
        <q-tooltip
          :offset="[10, 5]"
          :target="`#color-of-item-${props.itemInfo.id}`"
          class="bg-black text-body2"
          anchor="center left"
          self="center right"
          >{{ props.itemInfo.color_name }}</q-tooltip
        >
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.amount, 'Кількість')"
      >
        <div class="item-text">
          {{ props.itemInfo.amount }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>

    <td class="item-cell">
      <div
        style="cursor: pointer"
        @click="$emit('copyValue', props.itemInfo.unit_name, 'Одиницю')"
      >
        <div class="item-text">
          {{ props.itemInfo.unit_name }}
        </div>
      </div>
    </td>
    <td class="separator-cell"><div></div></td>
  </tr>

  <q-dialog v-model="showImage" seamless>
    <q-card>
      <q-card-section class="row items-center q-pb-md">
        <div class="text-h6 flex items-center">
          <q-icon name="photo" color="black" size="md" /><b class="q-ml-sm">{{
            props.itemInfo.title
          }}</b>
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-card-section class="q-pa-md flex justify-center">
        <template v-if="props.itemInfo.images.length > 1">
          <q-carousel
            animated
            v-model="slide"
            arrows
            navigation
            infinite
            control-color="primary"
            style="width: 500px"
            class="flex justify-center"
          >
            <q-carousel-slide
              style="
                width: 300px;
                margin-top: -15px;
                background-size: contain;
                background-repeat: no-repeat;
              "
              v-for="(image, index) in props.itemInfo.images"
              :key="index"
              :name="index + 1"
              :img-src="`${props.appStore.imagesStoreUrl}/${image.src}`"
            />
          </q-carousel>
        </template>
        <img
          class="q-px-md"
          v-else
          style="width: 400px"
          :src="`${props.appStore.imagesStoreUrl}/${props.itemInfo.images[0].src}`"
          alt=""
        />
      </q-card-section>
      <q-card-section class="flex justify-center">
        <q-btn
          color="primary q-mr-md"
          @click="
            downloadImage(
              `${props.appStore.imagesStoreUrl}/${
                props.itemInfo.images[slide - 1].src
              }`
            )
          "
          >Завантажити</q-btn
        >
        <q-btn
          color="primary"
          @click="
            copyImage(
              `${props.appStore.imagesStoreUrl}/${
                props.itemInfo.images[slide - 1].src
              }`
            )
          "
          >Копіювати</q-btn
        >
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed, onUpdated, ref } from "vue";
import { useQuasar } from "quasar";

const $q = useQuasar();

const emit = defineEmits([
  "showEditDialog",
  "showRemoveDialog",
  "clearUpdatedItemId",
  "copyValue",
]);
const props = defineProps([
  "itemInfo",
  "gap",
  "updated",
  "allowenses",
  "appStore",
]);

let isUpdated = ref(false);
let showImage = ref(false);
let slide = ref(1);

const currencyIcon = computed(() => {
  let icon = "";

  switch (props.itemInfo.currency) {
    case "UAH":
      icon = "₴";
      break;
    case "USD":
      icon = "$";
      break;
    case "EUR":
      icon = "€";
      break;
    default:
      break;
  }

  return icon;
});

const unconvertedPrice = computed(() => {
  return parseFloat(props.itemInfo.unconverted_price).toFixed(2);
});

const convertedPrice = computed(() => {
  return props.itemInfo.currency !== "UAH"
    ? `(₴ ~${parseFloat(props.itemInfo.converted_price_to_uah).toFixed(2)})`
    : "";
});

const priceForCopying = computed(() => {
  let priceForCopying = "";

  let mainPart = Math.floor(props.itemInfo.unconverted_price);
  let mainPartLabel =
    props.itemInfo.currency === "UAH"
      ? "грн"
      : props.itemInfo.currency === "USD"
      ? "дол"
      : props.itemInfo.currency === "EUR"
      ? "у.о"
      : "";

  let secondaryPart = (
    (props.itemInfo.unconverted_price - mainPart) *
    100
  ).toFixed(0);
  let showSecondaryPart = secondaryPart == 0 ? false : true;
  secondaryPart = secondaryPart < 10 ? `0${secondaryPart}` : secondaryPart;
  let secondaryPartLabel =
    props.itemInfo.currency === "UAH"
      ? "коп"
      : props.itemInfo.currency === "USD"
      ? "цент"
      : props.itemInfo.currency === "EUR"
      ? "є.ц"
      : "";

  priceForCopying =
    showSecondaryPart === true
      ? `${mainPart} ${mainPartLabel}. ${secondaryPart} ${secondaryPartLabel}.`
      : `${mainPart} ${mainPartLabel}.`;

  return priceForCopying;
});

async function downloadImage(imageSrc) {
  let name = imageSrc.split("/")[imageSrc.split("/").length - 1];
  let url = imageSrc;

  const response = await fetch(url);
  const blob = await response.blob();

  const blobURL = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = blobURL;
  link.download = name;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

async function copyImage(imageSrc) {
  try {
    const response = await fetch(imageSrc);
    const blob = await response.blob();
    await navigator.clipboard.write([
      new ClipboardItem({
        [blob.type]: blob,
      }),
    ]);
    $q.notify({
      position: "top",
      color: "primary",
      message: `Зображення зкопійовано`,
      group: false,
    });
  } catch (err) {
    console.error(err.name, err.message);
  }
}

onUpdated(() => {
  isUpdated.value = props.updated;

  if (isUpdated.value == true) {
    emit("clearUpdatedItemId");
  }
});
</script>
