<template>
  <q-dialog v-model="sectionStore.dialogs.amountsWarehouses.isShown">
    <q-card class="card">
      <q-card-section class="row items-center q-pb-md">
        <div class="text-h6 images-dialog-header">
          <q-icon name="apps" color="black" size="md" class="icon-header" />
          <div class="q-ml-sm text-header">
            {{ sectionStore.dialogs.amountsWarehouses.itemTitle }}
          </div>
          <q-btn
            icon="close"
            flat
            round
            dense
            v-close-popup
            class="close-button-header"
          />
        </div>
      </q-card-section>
      <q-card-section class="flex justify-center content"
        ><table style="width: 100%; border-collapse: collapse">
          <template
            v-for="(warehouseAmounts, index) in sectionStore.dialogs
              .amountsWarehouses.content"
            :key="warehouseAmounts.id"
          >
            <tr>
              <td>
                <span
                  :style="{
                    cursor:
                      appStore.filters.data.items.selectedParams.warehouse ==
                        null ||
                      warehouseAmounts.warehouse_id !=
                        appStore.filters.data.items.selectedParams.warehouse.id
                        ? 'pointer'
                        : 'default',
                  }"
                  :class="{
                    'text-primary':
                      appStore.filters.data.items.selectedParams.warehouse ==
                        null ||
                      warehouseAmounts.warehouse_id !=
                        appStore.filters.data.items.selectedParams.warehouse.id,
                  }"
                  @click="
                    if (
                      appStore.filters.data.items.selectedParams.warehouse ==
                        null ||
                      warehouseAmounts.warehouse_id !=
                        appStore.filters.data.items.selectedParams.warehouse.id
                    ) {
                      sectionStore.dialogs.amountsWarehouses.isShown = false;
                      appStore.filters.data.items.selectedParams.warehouse = {
                        id: warehouseAmounts.warehouse_id,
                        name: warehouseAmounts.warehouse_name,
                        address: warehouseAmounts.warehouse_address,
                        city_name: warehouseAmounts.city_name,
                        country_name: warehouseAmounts.country_name,
                        description: warehouseAmounts.warehouse_description,
                      };
                    }
                  "
                >
                  ({{ warehouseAmounts.country_name }},
                  {{ warehouseAmounts.city_name }})
                  {{ warehouseAmounts.warehouse_name }}
                </span>
              </td>
              <td align="right">
                {{ warehouseAmounts.amount }} {{ warehouseAmounts.unit }}
              </td>
            </tr>
            <tr
              v-if="
                sectionStore.dialogs.amountsWarehouses.content.length - 1 !=
                index
              "
            >
              <td style="padding: 5px 0px">
                <q-separator></q-separator>
              </td>
              <td style="padding: 5px 0px">
                <q-separator></q-separator>
              </td>
            </tr>
          </template>
        </table>
      </q-card-section>
      <q-card-section class="flex justify-end">
        <!-- <q-btn
          color="primary"
          @click="copyContent(sectionStore.dialogs.itemDescription.content)"
          >Копіювати</q-btn
        > -->
        <q-btn flat color="black" v-close-popup>Гаразд</q-btn>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { useItemStore } from "src/stores/itemStore";
import { useAppConfigStore } from "src/stores/appConfigStore";

const sectionStore = useItemStore();
const appStore = useAppConfigStore();
</script>

<style scoped>
.images-dialog-header {
  display: flex;
  flex-direction: row;
  width: 100%;
}
.icon-header {
  display: flex;
  flex: 0 0;
  margin-right: 5px;
}
.text-header {
  flex: 1 1 auto;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}
.close-button-header {
  margin-left: 5px;
  display: flex;
  flex: 0 0;
}
.card {
  width: 500px;
  max-width: 95vw;
  max-height: 95vh;
}
.content {
  max-height: 70vh;
  padding: 10px 30px;
}

.content > table {
  width: 100%;
  padding: 0;
}
</style>
