import { data } from "autoprefixer";
import { defineStore } from "pinia";

export const useAppConfigStore = defineStore("appConfig", {
  state: () => ({
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
    allowenses: {},
    filtersWidths: {
      //px
      types: {},
    },
    amountOfItemsPerPages: {
      items: 20,
      types: 2,
    },
    currentPages: { items: 0, types: 0 },
    availableAmaountOfItemsPerPage: [2, 5, 20, 50],
  }),
  getters: {
    attemptsLeft: (state) =>
      state.reauth.data.attemptsAllowed - state.reauth.data.attempt,
    secondsToLogoutLeft: (state) =>
      state.reauth.data.secondsToLogout -
      state.reauth.data.changingSecondsToLogout,
  },
  actions: {
    catchRequestError(err) {
      // 1) token expired or unauthenticated
      if (
        (err.response.data === "tokenexpired" && err.response.status === 422) ||
        (this.reauth.dialogs.renewPassword.isShown == false &&
          err.response.status == 403)
      ) {
        this.reauth.dialogs.unauthenticated.isShown = true;
        return;
      }

      // 2) user is afk
      if (err.response.data === "userisafk" && err.response.status === 422) {
        this.reauth.dialogs.renewPassword.isShown = true;
        return;
      }

      // 3) auth attempts while renew password window is opened
      if (
        this.reauth.dialogs.renewPassword.isShown == true &&
        (err.response.status === 403 || err.response.status === 422)
      ) {
        this.reauth.data.attempt += 1;
        return;
      }
    },
  },
});
