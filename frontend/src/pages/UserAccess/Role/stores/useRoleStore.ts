import { defineStore } from "pinia";

export const useRoleStore = defineStore("useRoleStore", {
  state: () => ({
    arrayFather: ref<Array<object>>([]),
    selectedFather: ref<Array<number | string>>([]),
    selectedElements: ref<Array<number | string>>([]),
    keyIdComparation: ref<string>("id"),
    keyArray: ref<string>("permissions"),
  }),
  getters: {},
  actions: {
    checkPermissionsSelected(data: Array<string | number>) {
      data.forEach(element => {

        if (!this.selectedElements.includes(element)) {
          this.selectedElements.push(element);
        }

      });
    },
    removePermission(data: Array<string | number>) {

      data.forEach(element => {

        if (this.selectedElements.includes(element)) {
          const index = this.selectedElements.indexOf(element);
          if (index > -1) {
            this.selectedElements.splice(index, 1); // Elimina por posición
          }
        }

      });

    },
    clearPermissionsSelected() {
      this.selectedElements = [1]
    },

    handleCardCheckboxChange(cardId: number | string) {
      if (this.selectedFather.includes(cardId)) {

        this.selectAllPermissions(cardId);
      } else {

        this.deselectAllPermissions(cardId)
      }
    },


    // Función recursiva para buscar en los children de un elemento y agregarlo
    searchInChildrenToAdd(parent: any, cardId: number | string) {
      parent["children"].forEach(child => {
        if (child[this.keyIdComparation] === cardId && !this.selectedElements.includes(child.id)) {
          this.selectedElements.push(child.id);
        }
        // Llamar recursivamente para buscar en los hijos de este child si existen
        if (child["children"] && child["children"].length > 0) {
          this.searchInChildrenToAdd(child, cardId);
        }
      });
    },
    selectAllPermissions(cardId: number | string) {
      const group = this.findGroupRecursive(this.arrayFather, cardId); // Busca el grupo en toda la estructura
      if (group && group.permissions) {
        group.permissions.forEach(permission => {
          if (!this.selectedElements.includes(permission.id) && permission.id !== 1) {
            this.selectedElements.push(permission.id);
          }
        });
      }
    },

    deselectAllPermissions(cardId: number | string) {
      const group = this.findGroupRecursive(this.arrayFather, cardId); // Busca el grupo en toda la estructura
      if (group && group.permissions) {
        const permissionIds = group.permissions.map(perm => perm.id);
        this.selectedElements = this.selectedElements.filter(el => !permissionIds.includes(el));
      }
    },

    // Función auxiliar para buscar un grupo por ID en toda la estructura anidada
    findGroupRecursive(groups: any[], targetId: number | string): any {
      for (const group of groups) {
        if (group.id === targetId) return group;
        if (group.children) {
          const found = this.findGroupRecursive(group.children, targetId);
          if (found) return found;
        }
      }
      return null;
    },
  },
});
