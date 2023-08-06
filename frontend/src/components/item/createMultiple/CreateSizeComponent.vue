<template>
  <div class="q-mt-sm">
    <div
      class="text-h6 q-mb-sm q-mb-sm-sm text-weight-medium text-left q-pl-md flex"
    >
      <span
        v-if="props.genderArrayIndex != -1"
        style="display: flex; align-items: center"
      >
        {{ genderName }}
        <q-icon class="q-mx-sm" size="25px" name="chevron_right"></q-icon>
      </span>
      <span
        v-if="props.colorArrayIndex != -1"
        style="display: flex; align-items: center"
      >
        {{ colorName }}
        <q-icon class="q-mx-sm" size="25px" name="chevron_right"></q-icon>
      </span>
      <span style="display: flex; align-items: center">
        {{ sizeName }}
      </span>
    </div>
    <div class="row q-col-gutter-md">
      <q-select
        autocomplete="false"
        outlined
        use-input
        hide-selected
        v-model="tempSizeHolder"
        label="Введіть розмір"
        input-debounce="400"
        :options="sizeStore.simpleItems"
        @filter="sizeFilter"
        @update:model-value="addSelectedSizeToStore"
        :loading="sizeStore.data.isItemsLoading"
        hide-dropdown-icon
        class="col-12 q-pb-sm"
        :rules="[
          () => getContextSizes().length > 0 || 'Оберіть хоча б один розмір',
        ]"
      >
        <template v-slot:option="scope">
          <q-item v-bind="scope.itemProps" class="flex items-center">
            <span
              :class="{
                'active-item-component': isSizeExistInList(scope.opt.id),
              }"
            >
              {{ scope.opt.value }}
            </span>
          </q-item>
        </template>
      </q-select>
    </div>
  </div>
  <div class="row col-12" v-if="getContextSizes().length > 0">
    <div
      id="colors_container"
      class="col-12 items-wrapper q-px-md q-pt-md q-mb-sm q-mt-sm q-mt-sm-sm"
    >
      <div class="q-gutter-md row">
        <template
          v-for="(item, itemIndex) in sectionStore.newMultipleItems.sizes"
          :key="itemIndex"
        >
          <div
            v-if="
              item.connections.genderArrayIndex === props.genderArrayIndex &&
              item.connections.colorArrayIndex === props.colorArrayIndex
            "
            :class="{
              'selected-item': props.selectedSizeIndex === itemIndex,
            }"
            @click="selectItem(itemIndex)"
            class="item-chip q-py-xs q-pl-md q-pr-sm row d-flex items-center"
          >
            <div class="item-chip-article q-mr-sm">
              {{ item.value }}
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
      <SelectedSizeFormComponent
        :sizeArrayIndex="props.selectedSizeIndex"
        v-if="props.selectedSizeIndex != -1"
        :rules="props.rules"
      />
    </div>
  </div>
  <div id="bottom_of_sizes_container"></div>
</template>
<script setup>
import { computed } from "vue";
import { useItemStore } from "src/stores/itemStore";
import { useSizeStore } from "src/stores/sizeStore";
import SelectedSizeFormComponent from "./SelectedSizeFormComponent.vue";
const sectionStore = useItemStore();
const sizeStore = useSizeStore();
const props = defineProps([
  "genderArrayIndex",
  "colorArrayIndex",
  "selectedSizeIndex",
  "isSizesUsed",
  "rules",
]);
const emits = defineEmits(["selectSize", "removeSize"]);
let tempSizeHolder = null;

function sizeFilter(val, update, abort) {
  update(() => {
    sizeStore.data.isItemsLoading = true;
    sizeStore.simpleItems = [];
    sizeStore.simpleReceive(val);
  });
}

function selectItem(itemIndex) {
  emits("selectSize", itemIndex, "size");
}

function removeItem(itemIndex) {
  emits("removeSize", itemIndex, "size");
}

/**
 * Clones object
 * @param {object} object object
 * @return {object} clone object
 */
function cloneObject(object) {
  let objectClone = {};
  objectClone = JSON.parse(JSON.stringify(object));
  return objectClone;
}

function addSelectedSizeToStore(val) {
  let isValueExist = isSizeExistInList(val.id);
  let bottomOfSizesContainer = document.getElementById(
    "bottom_of_sizes_container"
  );

  if (!isValueExist) {
    let newSizeIndex = sectionStore.newMultipleItems.sizes.length;

    let newSizeTemplate = { ...val };
    if (props.colorArrayIndex != -1) {
      newSizeTemplate.detail = cloneObject(
        sectionStore.newMultipleItems.colors[props.colorArrayIndex].detail
      );
    }
    if (props.colorArrayIndex == -1 && props.genderArrayIndex != -1) {
      newSizeTemplate.detail = cloneObject(
        sectionStore.newMultipleItems.genders[props.genderArrayIndex].detail
      );
    }
    if (props.colorArrayIndex == -1 && props.genderArrayIndex == -1) {
      newSizeTemplate.detail = cloneObject(
        sectionStore.newMultipleItems.main.detail
      );
    }

    newSizeTemplate.connections = {
      genderArrayIndex: props.genderArrayIndex,
      colorArrayIndex: props.colorArrayIndex,
    };
    newSizeTemplate.indexInArray = newSizeIndex;
    sectionStore.newMultipleItems.sizes.push(newSizeTemplate);
    tempSizeHolder = {};

    if (getContextSizes().length === 1) {
      selectItem(newSizeIndex);
      setTimeout(() => {
        bottomOfSizesContainer.scrollIntoView({
          behavior: "smooth",
          block: "end",
        });
      }, 100);
    }
  }
}

function isSizeExistInList(itemId) {
  let items = sectionStore.newMultipleItems.sizes;
  let isExist =
    items.filter(
      (item) =>
        item.id === itemId &&
        item.connections.genderArrayIndex === props.genderArrayIndex &&
        item.connections.colorArrayIndex === props.colorArrayIndex
    ).length > 0
      ? true
      : false;

  if (props.selectedSizeIndex == -1) {
    isExist = false;
  }

  return isExist;
}

/**
 * Gets size of particular context: inside gender, or inside "main"
 */
function getContextSizes() {
  return sectionStore.newMultipleItems.sizes.filter(
    (size) =>
      size.connections.genderArrayIndex === props.genderArrayIndex &&
      size.connections.colorArrayIndex === props.colorArrayIndex
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

let colorName = computed(() => {
  let capitalizedColorName = "";

  if (
    sectionStore.newMultipleItems.colors[props.colorArrayIndex] != undefined
  ) {
    capitalizedColorName =
      sectionStore.newMultipleItems.colors[props.colorArrayIndex].description;
    if (props.genderArrayIndex == -1) {
      capitalizedColorName =
        capitalizedColorName.charAt(0).toUpperCase() +
        capitalizedColorName.slice(1);
    } else {
      capitalizedColorName =
        capitalizedColorName.charAt(0).toLowerCase() +
        capitalizedColorName.slice(1);
    }
  }

  return capitalizedColorName;
});

let sizeName = computed(() => {
  return props.genderArrayIndex == -1 && props.colorArrayIndex == -1
    ? "Розміри"
    : "розміри";
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
