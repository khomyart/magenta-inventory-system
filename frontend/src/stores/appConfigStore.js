import { defineStore } from "pinia";

export const useAppConfigStore = defineStore("appConfig", {
  state: () => ({
    version: 9,
    //axios has its own config
    // backendUrl: "https://api.inventory.magenta.net.ua",
    // imagesStoreUrl: "https://api.inventory.magenta.net.ua/images",
    backendUrl: "http://localhost/",
    imagesStoreUrl: "http://localhost/images",
    webSocketUrl: "",
    dialogs: {
      settings: {
        isShown: false,
        isLoading: false,
      },
    },
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
          isLogoutThroughtLogoutMethod: false,
          attempt: 0,
          attemptsAllowed: 3,
          //seconds to logout while unauthenticated dialog is active
          secondsToLogout: 9,
          changingSecondsToLogout: 0,
        },
      },
      responses: {
        422: {
          isShown: false,
          text: "",
        },
        403: {
          isShown: false,
          text: "",
        },
      },
      display: {
        isShown: false,
        isHTML: false,
        text: "",
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
              },
            },
          },
        },
        sizes: {
          width: {
            default: {
              value: 150,
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
              },
            },
            description: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
          },
        },
        genders: {
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
              },
            },
          },
        },
        colors: {
          width: {
            default: {
              value: 150,
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
              },
            },
            article: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            description: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            text_color_value: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
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
              },
            },
            city_name: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            address: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            name: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            description: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
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
              },
            },
          },
        },
        units: {
          width: {
            default: {
              name: 150,
              description: 150,
            },
            dynamic: {
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
            name: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            description: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
          },
        },
        items: {
          width: {
            default: {
              group_id: 150,
              article: 150,
              title: 150,
              model: 150,
              price: 150,
              type: 150,
              gender: 150,
              size: 150,
              color: 150,
              amount: 150,
              units: 150,
            },
            dynamic: {
              group_id: 0,
              article: 0,
              title: 0,
              model: 0,
              price: 0,
              type: 0,
              gender: 0,
              size: 0,
              color: 0,
              amount: 0,
              units: 0,
            },
          },
          selectedParams: {
            order: {
              field: "",
              value: "",
              //watcherVariable
              combined: "",
            },
            warehouse: null,
            group_id: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            article: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            title: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            model: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            price: {
              value: "",
              filterMode: {
                label: "Більше",
                value: "more",
                shortName: "MORE",
              },
            },
            type: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            gender: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            size: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            color: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
            amount: {
              value: "",
              filterMode: {
                label: "Більше",
                value: "more",
                shortName: "MORE",
              },
            },
            units: {
              value: "",
              filterMode: {
                label: "Містить",
                value: "include",
                shortName: "LIKE",
              },
            },
          },
        },
      },
      availableParams: {
        minFilterWidth: 60,
        separatorWidth: 11,
        filterButtonXPadding: 32,
        //affected || straight
        resizeMode: "straight",
        items: [
          {
            label: "Містить",
            value: "include",
            shortName: "LIKE",
            type: "text",
          },
          {
            label: "Не містить",
            value: "exclude",
            shortName: "EXCL",
            type: "text",
          },
          { label: "Більше", value: "more", shortName: "MORE", type: "number" },
          { label: "Менше", value: "less", shortName: "LESS", type: "number" },
          {
            label: "Дорівнює",
            value: "equal",
            shortName: "EQL",
            type: "number",
          },
          {
            label: "Не дорівнює",
            value: "notequal",
            shortName: "NEQL",
            type: "number",
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
      units: 10,
    },
    currentPages: {
      items: 0,
      types: 0,
      sizes: 0,
      genders: 0,
      colors: 0,
      warehouses: 0,
      units: 0,
    },
    availableAmaountOfItemsPerPage: [10, 20, 50],
    other: {
      visualTheme: {
        //px
        itemsBorderRadius: {
          types: 7,
          sizes: 7,
          genders: 7,
          colors: 7,
          warehouses: 7,
          units: 7,
          items: 7,
        },
        //px
        gapsBetweenItems: {
          types: 0,
          sizes: 0,
          genders: 0,
          colors: 0,
          warehouses: 0,
          units: 0,
          items: 0,
        },
      },
    },
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
        (err.response.data.message === "tokenexpired" &&
          err.response.status === 422) ||
        (this.errors.reauth.dialogs.renewPassword.isShown == false &&
          err.response.status == 403)
      ) {
        this.errors.reauth.dialogs.unauthenticated.isShown = true;
        return;
      }

      // 2) user is afk
      if (
        err.response.data.message === "userisafk" &&
        err.response.status === 422
      ) {
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

      /**
       * interactive errors (calls dialog window)
       */
      if (err.response.status === 422 || err.response.status === 403) {
        this.showErrorMessage(err.response.data.message, true);
        return;
      }
    },
    showErrorMessage(errorMessage, formatAsHTML = false) {
      this.errors.display.isHTML = formatAsHTML;
      this.errors.display.isShown = true;
      this.errors.display.text = errorMessage;
    },
    updateLocalStorageConfig() {
      console.log("ui stored");

      localStorage.setItem("filters", JSON.stringify(this.filters.data));
      localStorage.setItem("configVersion", JSON.stringify(this.version));
      localStorage.setItem(
        "itemsPerPage",
        JSON.stringify(this.amountOfItemsPerPages)
      );
    },
    setUIdependsOnLocalStorage() {
      console.log("ui renewed");
      //update local store config cache if version does not equal
      if (localStorage.getItem("configVersion") != this.version) {
        this.updateLocalStorageConfig();
      }

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
