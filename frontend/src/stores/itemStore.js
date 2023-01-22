import { defineStore } from "pinia";

export const useItemStore = defineStore("item", {
  state: () => ({
    itemsList: [
      {
        id: null,
        article: "3341125",
        name: "Sweet Hoody Test of Rookola",
        images: [
          "/src/assets/magenta-logo.png",
          "/src/assets/magenta-menu-logo.png",
        ],
        type: {
          name: "Худі",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        gender: {
          name: "Чоловіча",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        size: {
          name: "XXL",
          description: "chest 150 sm, etc",
        },
        color: {
          name: "Червоний",
          value: "#eb4034",
          textColor: "#ffffff",
        },
        amount: 3568,
        units: "од",
      },
      {
        id: null,
        article: "3341125",
        name: "Sweet Hoody 2 Test of Rookola",
        images: [
          "/src/assets/magenta-logo.png",
          "/src/assets/magenta-menu-logo.png",
          "/src/assets/magenta-menu-logo.png",
        ],
        type: {
          name: "Пуді",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        gender: {
          name: "Жіноча",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        size: {
          name: "L",
          description: "chest 5 sm, etc",
        },
        color: {
          name: "Зелений",
          value: "#27db21",
          textColor: "#000000",
        },
        amount: 60999,
        units: "м3",
      },
      {
        id: null,
        article: "3341125",
        name: "Poodatty",
        images: ["/src/assets/magenta-logo.png"],
        type: {
          name: "Пуді",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        gender: {
          name: "Жіноча",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        size: {
          name: "L",
          description: "chest 5 sm, etc",
        },
        color: {
          name: "Зелений",
          value: "#27db21",
          textColor: "#000000",
        },
        amount: 60999,
        units: "кг",
      },
      {
        id: null,
        article: "3341125",
        name: "Poodatty",
        images: [],
        type: {
          name: "Пуді",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        gender: {
          name: "Жіноча",
          icon: "/src/assets/magenta-menu-logo.png",
        },
        size: {
          name: "L",
          description: "chest 5 sm, etc",
        },
        color: {
          name: "Зелений",
          value: "#27db21",
          textColor: "#000000",
        },
        amount: 60999,
        units: "л",
      },
    ],
  }),
  getters: {},
  actions: {},
});
