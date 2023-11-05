<template>
  <div class="row q-col-gutter-md q-mt-sm">
    <q-input
      class="col-12 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.colors[props.colorArrayIndex].detail.title
      "
      label="Назва"
    />

    <div class="col-12 q-pt-sm q-mb-md q-wysiwyg">
      <q-editor
        ref="editorRef"
        @paste="onPaste"
        :toolbar="[
          [
            {
              label: 'Вирівнювання',
              icon: $q.iconSet.editor.align,
              fixedLabel: true,
              list: 'only-icons',
              options: ['left', 'center', 'right', 'justify'],
            },
          ],
          [
            {
              label: 'Текст',
              icon: $q.iconSet.editor.bold,
              fixedLabel: true,
              list: 'only-icons',
              options: [
                'bold',
                'italic',
                'strike',
                'underline',
                'subscript',
                'superscript',
              ],
            },
          ],
          [
            {
              label: 'Список',
              icon: $q.iconSet.editor.orderedList,
              fixedLabel: true,
              list: 'only-icons',
              options: ['unordered', 'ordered'],
            },
          ],

          ['removeFormat', 'viewsource'],
        ]"
        outlined
        v-model="
          sectionStore.newMultipleItems.colors[props.colorArrayIndex].detail
            .description
        "
        placeholder="Опис"
      />
    </div>

    <q-input
      class="col-4 q-pt-sm q-mb-md"
      outlined
      label="Ціна"
      type="number"
      step="0.01"
      v-model="
        sectionStore.newMultipleItems.colors[props.colorArrayIndex].detail.price
      "
    />
    <q-select
      hide-dropdown-icon
      outlined
      label="Валюта"
      v-model="
        sectionStore.newMultipleItems.colors[props.colorArrayIndex].detail
          .currency
      "
      :options="['UAH', 'USD', 'EUR']"
      class="col-4 q-pt-sm q-mb-md"
    />
    <q-input
      class="col-4 q-pt-sm q-mb-md"
      outlined
      v-model="
        sectionStore.newMultipleItems.colors[props.colorArrayIndex].detail.lack
      "
      label="Нестача"
      type="number"
    />
  </div>
  <AddImagesComponent :index="props.colorArrayIndex" type="color" />
  <AddAvailableInComponent
    type="colors"
    :index="props.colorArrayIndex"
    v-if="props.lastUsedCharacteristic === 'colors'"
  />
</template>
<script setup>
import { ref } from "vue";
import { useItemStore } from "src/stores/itemStore";
import AddImagesComponent from "./AddImagesComponent.vue";
import AddAvailableInComponent from "./AddAvailableInComponent.vue";
const sectionStore = useItemStore();
const props = defineProps([
  "colorArrayIndex",
  "lastUsedCharacteristic",
  "rules",
]);

const editorRef = ref(null);
function onPaste(evt) {
  if (evt.target.nodeName === "INPUT") return;
  let text, onPasteStripFormattingIEPaste;
  evt.preventDefault();
  evt.stopPropagation();
  if (evt.originalEvent && evt.originalEvent.clipboardData.getData) {
    text = evt.originalEvent.clipboardData.getData("text/plain");
    editorRef.value.runCmd("insertText", text);
  } else if (evt.clipboardData && evt.clipboardData.getData) {
    text = evt.clipboardData.getData("text/plain");
    editorRef.value.runCmd("insertText", text);
  } else if (window.clipboardData && window.clipboardData.getData) {
    if (!onPasteStripFormattingIEPaste) {
      onPasteStripFormattingIEPaste = true;
      editorRef.value.runCmd("ms-pasteTextOnly", text);
    }
    onPasteStripFormattingIEPaste = false;
  }
}
</script>
