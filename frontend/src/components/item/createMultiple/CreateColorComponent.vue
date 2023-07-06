<template>
  <div class="q-mt-sm">
    <q-separator class="q-mb-md" />
    <div
      class="text-h6 q-mb-sm q-mb-sm-sm text-weight-medium text-left q-pl-md"
    >
      <span
        v-if="props.genderArrayIndex != -1"
        style="display: flex; align-items: center"
      >
        {{ genderName }}
        <q-icon class="q-mx-sm" size="25px" name="chevron_right"></q-icon>
        кольори</span
      >
      <span v-else>Кольори</span>
    </div>
    <div class="row q-col-gutter-md">
      <q-select
        autocomplete="false"
        outlined
        use-input
        hide-selected
        v-model="tempColorHolder"
        label="Введіть назву, артикль(!) або значення(#) кольору"
        input-debounce="400"
        :options="colorStore.simpleItems"
        @filter="colorFilter"
        @update:model-value="addSelectedColorToStore"
        :loading="colorStore.data.isItemsLoading"
        hide-dropdown-icon
        class="col-12 q-pb-sm"
        :rules="[() => true]"
      >
        <template v-slot:option="scope">
          <q-item v-bind="scope.itemProps" class="flex items-center">
            <div
              class="color q-mr-sm"
              :style="{ backgroundColor: scope.opt.value }"
            ></div>
            <span
              :class="{
                'active-item-component': isColorExistInList(scope.opt.id),
              }"
            >
              {{ isColorExistInList(scope.opt.id) }} |
              {{ scope.opt.description }} (!{{ scope.opt.article }},
              {{ scope.opt.value }})
            </span>
          </q-item>
        </template>
      </q-select>
    </div>
  </div>
  <div class="row col-12" v-if="getContextColors().length > 0">
    <div
      id="colors_container"
      class="col-12 items-wrapper q-px-md q-pt-md q-pb-md q-mb-sm q-mt-sm q-mt-sm-sm"
    >
      <div class="q-gutter-md row">
        <template
          v-for="(item, itemIndex) in sectionStore.newMultipleItems.colors"
          :key="item.id"
        >
          <div
            v-if="item.connections.genderArrayIndex === props.genderArrayIndex"
            :class="{
              'selected-item': props.selectedColorIndex === itemIndex,
            }"
            @click="selectItem(itemIndex)"
            class="item-chip q-py-xs q-pl-md q-pr-sm row d-flex items-center"
          >
            <div
              class="item-chip-color q-mr-sm"
              :style="{ backgroundColor: item.value }"
            ></div>
            <div class="item-chip-article q-mr-sm">
              {{ item.description }}
            </div>
            <q-btn
              round
              color="grey"
              flat
              size="8px"
              icon="close"
              @click.stop="removeItem(itemIndex)"
            />
          </div>
        </template>
      </div>
      <q-separator class="q-mt-md" />
      <SelectedColorFormComponent :colorArrayIndex="props.selectedColorIndex" />
    </div>
  </div>
  <div id="bottom_of_colors_container"></div>
</template>
<script setup>
import { computed } from "vue";
import { useItemStore } from "src/stores/itemStore";
import { useColorStore } from "src/stores/colorStore";
import SelectedColorFormComponent from "./SelectedColorFormComponent.vue";
const sectionStore = useItemStore();
const colorStore = useColorStore();
const props = defineProps([
  "genderArrayIndex",
  "selectedColorIndex",
  "isColorsUsed",
]);
const emits = defineEmits(["selectColor", "removeColor"]);
let tempColorHolder = null;

function colorFilter(val, update, abort) {
  update(() => {
    colorStore.data.isItemsLoading = true;
    colorStore.simpleItems = [];
    colorStore.simpleReceive(val);
  });
}

function selectItem(itemIndex) {
  emits("selectColor", itemIndex, "color");
}

function removeItem(itemIndex) {
  emits("removeColor", itemIndex, "color");
}

function addSelectedColorToStore(val) {
  let isValueExist = isColorExistInList(val.id);
  let bottomOfColorContainer = document.getElementById(
    "bottom_of_colors_container"
  );

  if (!isValueExist) {
    let newColorIndex = sectionStore.newMultipleItems.colors.length;

    let newColorTemplate = { ...val };
    newColorTemplate.detail =
      props.genderArrayIndex != -1
        ? {
            ...sectionStore.newMultipleItems.genders[props.genderArrayIndex]
              .detail,
          }
        : {
            ...sectionStore.newMultipleItems.main.detail,
          };

    newColorTemplate.connections = {
      genderArrayIndex: props.genderArrayIndex,
    };
    newColorTemplate.indexInArray = newColorIndex;
    sectionStore.newMultipleItems.colors.push(newColorTemplate);
    tempColorHolder = {};

    if (getContextColors().length === 1) {
      selectItem(newColorIndex);
      setTimeout(() => {
        bottomOfColorContainer.scrollIntoView({
          behavior: "smooth",
          block: "end",
        });
      }, 100);
    }
  }
}

function isColorExistInList(itemId) {
  let items = sectionStore.newMultipleItems.colors;
  let isExist =
    items.filter(
      (item) =>
        item.id === itemId &&
        item.connections.genderArrayIndex === props.genderArrayIndex
    ).length > 0
      ? true
      : false;

  if (props.selectedColorIndex == -1) {
    isExist = false;
  }

  return isExist;
}

/**
 * Gets color of particular context: inside gender, or inside "main"
 */
function getContextColors() {
  return sectionStore.newMultipleItems.colors.filter(
    (color) => color.connections.genderArrayIndex === props.genderArrayIndex
  );
}

let genderName = computed(() => {
  let capitalizedGenderName =
    sectionStore.newMultipleItems.genders[props.genderArrayIndex].name;
  capitalizedGenderName =
    capitalizedGenderName.charAt(0).toUpperCase() +
    capitalizedGenderName.slice(1);
  return capitalizedGenderName;
});
</script>
<style scoped>
.active-item-component {
  color: #a32cc7;
}
.color {
  width: 30px;
  height: 30px;
  border-radius: 5px;
  border: 1px solid rgba(0, 0, 0, 0.18);
}
.list-item-body {
  display: flex;
  align-items: center;
}
.items-wrapper {
  border: 1px solid rgba(96, 0, 92, 0.18);
  border-radius: 4px;
}
.item-chip {
  width: fit-content;
  height: fit-content;
  border-radius: 20px;
  border: 1px solid rgba(96, 0, 92, 0.18);
  cursor: pointer;
  transition: all 0.1s ease-in-out;
}
.item-chip-color {
  width: 20px;
  height: 20px;
  border-radius: 20px;
  margin-left: -5px;
}
.selected-item {
  border: 1px solid #a32cc7;
  background-color: #a32cc709;
}
</style>
