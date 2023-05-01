<template>
  <div class="col-3">
    <div class="image-holder">
      <div class="image" :style="`background-image: url(${props.imageUrl});`">
        <div class="buttons-holder">
          <div class="row justify-end">
            <q-btn
              icon="delete"
              size="10px"
              style="width: 20px"
              color="red"
              text-color="white"
              round
              @click="$emit('remove', props.index)"
            ></q-btn>
          </div>
          <div
            class="row"
            :class="{
              'justify-between': props.index != 0,
              'justify-end': props.index == 0,
            }"
          >
            <q-btn
              icon="chevron_left"
              size="10px"
              style="width: 20px"
              color="white"
              text-color="black"
              v-if="props.index != 0"
              @click="$emit('move', props.index, 'left')"
            ></q-btn>
            <q-btn
              icon="chevron_right"
              size="10px"
              style="width: 20px"
              color="white"
              text-color="black"
              v-if="
                props.amountOfImages != 1 &&
                props.amountOfImages != props.index + 1
              "
              @click="$emit('move', props.index, 'right')"
            ></q-btn>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps(["imageUrl", "index", "amountOfImages"]);
const emits = defineEmits(["move", "remove", "show"]);
</script>

<style scoped>
.buttons-holder {
  height: 100%;
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  opacity: 0;
  transition: 0.2s all ease-in-out;
}

.image-holder {
  height: 100px;
  border: 1px solid rgba(0, 0, 0, 0.18);
  border-radius: 3px;
  overflow: hidden;
  background-color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 5px;
  transition: all 0.2s;
  cursor: pointer;
}

.image-holder:hover {
  background-color: rgba(255, 0, 217, 0.088);
}

.buttons-holder:hover {
  opacity: 1;
}

.image {
  height: 100%;
  width: 100%;
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
}
</style>
