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
    allowenses: {
      list: {},
      renew: function () {
        let userData = JSON.parse(sessionStorage.getItem("data"));
        let allowenses = userData.allowenses;

        allowenses.forEach((allowense) => {
          this.list[allowense.section] = {};
        });

        allowenses.forEach((allowense) => {
          this.list[allowense.section][allowense.action] = "";
        });
      },
      isValidFor: function (action, section) {
        if (this.list[section] === undefined) {
          return false;
        }

        return this.list[section][action] != undefined;
      },
      renewAndCheckIsValidFor: function (action, section) {
        this.renew();
        return this.isValidFor(action, section);
      },
    },
    filters: {
      data: {
        types: {
          width: {
            default: {
              article: 300,
              name: 150,
            },
            dynamic: {
              article: 0,
              name: 0,
            },
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
        sizes: {
          width: {
            default: {
              value: 300,
              description: 150,
            },
            dynamic: {
              value: 0,
              description: 0,
            },
          },
          selectedParams: {
            order: {
              field: "",
              value: "",
              //watcherVariable
              combined: "",
            },
            value: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
                type: "universal",
              },
            },
            description: {
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
        genders: {
          width: {
            default: {
              name: 300,
            },
            dynamic: {
              name: 0,
            },
          },
          selectedParams: {
            order: {
              field: "",
              value: "",
              //watcherVariable
              combined: "",
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
        colors: {
          width: {
            default: {
              value: 300,
              article: 150,
              description: 150,
              text_color_value: 150,
            },
            dynamic: {
              value: 0,
              article: 0,
              description: 0,
              text_color_value: 0,
            },
          },
          selectedParams: {
            order: {
              field: "",
              value: "",
              //watcherVariable
              combined: "",
            },
            value: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
                type: "universal",
              },
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
            description: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
                type: "universal",
              },
            },
            text_color_value: {
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
        warehouses: {
          width: {
            default: {
              country_name: 150,
              city_name: 150,
              address: 150,
              name: 150,
              description: 800,
            },
            dynamic: {
              country_name: 0,
              city_name: 0,
              address: 0,
              name: 0,
              description: 0,
            },
          },
          selectedParams: {
            order: {
              field: "",
              value: "",
              //watcherVariable
              combined: "",
            },
            country_name: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
                type: "universal",
              },
            },
            city_name: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
                type: "universal",
              },
            },
            address: {
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
            description: {
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
        countries: {
          width: {
            default: {
              name: 150,
            },
            dynamic: {
              name: 0,
            },
          },
          selectedParams: {
            order: {
              field: "",
              value: "",
              //watcherVariable
              combined: "",
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
      },
      availableParams: {
        minFilterWidth: 140,
        separatorWidth: 11,
        filterButtonXPadding: 32,
        //affected || straight
        resizeMode: "straight",
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
      items: 10,
      types: 10,
      sizes: 10,
      genders: 10,
      colors: 10,
      warehouses: 10,
    },
    currentPages: {
      items: 0,
      types: 0,
      sizes: 0,
      genders: 0,
      colors: 0,
      warehouses: 0,
    },
    availableAmaountOfItemsPerPage: [10, 20, 50],
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
    updateLocalStorageConfig() {
      localStorage.setItem("filters", JSON.stringify(this.filters.data));
      localStorage.setItem(
        "itemsPerPage",
        JSON.stringify(this.amountOfItemsPerPages)
      );
    },
    setUIdependsOnLocalStorage() {
      if (
        localStorage.getItem("filters") != null &&
        localStorage.getItem("filters").length > 30
      ) {
        this.filters.data = JSON.parse(localStorage.getItem("filters"));
      }

      if (
        localStorage.getItem("itemsPerPage") != null &&
        localStorage.getItem("itemsPerPage").length > 10
      ) {
        this.amountOfItemsPerPages = JSON.parse(
          localStorage.getItem("itemsPerPage")
        );
      }
    },
  },
});
