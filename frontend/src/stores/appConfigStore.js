import { data } from "autoprefixer";
import { defineStore } from "pinia";

export const useAppConfigStore = defineStore("appConfig", {
  state: () => ({
    errors: {
      reauth: {
        notifications: {},
        dialogs: {
          renewPassword: {
            isShown: false,
            isLoading: false,
          },
          unauthenticated: {
            isShown: false,
            isLoading: false,
          },
        },
        data: {
          attempt: 0,
          attemptsAllowed: 3,
          //seconds to logout while unauthenticated dialog is active
          secondsToLogout: 9,
          changingSecondsToLogout: 0,
        },
      },
    },
    allowenses: {},
    filters: {
      types: {
        width: {
          article: "",
          name: "",
        },
        selectedParams: {
          order: {
            field: "",
            value: "",
            //watcherVariable
            combined: "",
          },
          article: {
            value: "",
            filterMode: {
              label: "Містить",
              value: "include",
              shortName: "LIKE",
              type: "universal",
            },
          },
          name: {
            value: "",
            filterMode: {
              label: "Містить",
              value: "include",
              shortName: "LIKE",
              type: "universal",
            },
          },
        },
      },
      availableParams: {
        items: [
          {
            label: "Містить",
            value: "include",
            shortName: "LIKE",
            type: "universal",
          },
          {
            label: "Не містить",
            value: "exclude",
            shortName: "EXCL",
            type: "universal",
          },
          { label: "Більше", value: "more", shortName: "MORE", type: "number" },
          { label: "Менше", value: "less", shortName: "LESS", type: "number" },
          {
            label: "Дорівнює",
            value: "equal",
            shortName: "EQL",
            type: "universal",
          },
          {
            label: "Не дорівнює",
            value: "notequal",
            shortName: "NEQL",
            type: "universal",
          },
          //...
        ],
      },
    },
    amountOfItemsPerPages: {
      items: 20,
      types: 5,
    },
    currentPages: { items: 0, types: 0 },
    availableAmaountOfItemsPerPage: [2, 5, 20, 50],
  }),
  getters: {
    attemptsLeft: (state) =>
      state.errors.reauth.data.attemptsAllowed -
      state.errors.reauth.data.attempt,
    secondsToLogoutLeft: (state) =>
      state.errors.reauth.data.secondsToLogout -
      state.errors.reauth.data.changingSecondsToLogout,
  },
  actions: {
    catchRequestError(err) {
      // 1) token expired or unauthenticated
      if (
        (err.response.data === "tokenexpired" && err.response.status === 422) ||
        (this.errors.reauth.dialogs.renewPassword.isShown == false &&
          err.response.status == 403)
      ) {
        this.errors.reauth.dialogs.unauthenticated.isShown = true;
        return;
      }

      // 2) user is afk
      if (err.response.data === "userisafk" && err.response.status === 422) {
        this.errors.reauth.dialogs.renewPassword.isShown = true;
        return;
      }

      // 3) auth attempts while renew password window is opened
      if (
        this.errors.reauth.dialogs.renewPassword.isShown == true &&
        (err.response.status === 403 || err.response.status === 422)
      ) {
        this.errors.reauth.data.attempt += 1;
        return;
      }
    },
  },
});
