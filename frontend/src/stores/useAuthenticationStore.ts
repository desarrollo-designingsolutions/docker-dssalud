import { defineStore } from "pinia";

import type IAuth from "@/interfaces/Authentication/IAuth";
import type ILogin from "@/interfaces/Authentication/ILogin";
import type IPromise from "@/interfaces/Axios/IPromise";

export const useAuthenticationStore = defineStore("useAuthenticationStore", {
  state: () => ({
    isAuthenticate: false as boolean,
    tokenGoogle: "" as string,
    access_token: "" as string,
    user: {} as IAuth,
    company: { id: null } as object,
    menu: [],
    permissions: [],
    loading: false,
    rememberMe: false,
  }),
  persist: true,
  getters: {
    getMenuData: (state) => {
      if (!isEmpty(state.company.id))
        return state.menu.filter(ele => ele.to.name != 'Company-List')
      else return state.menu.filter(ele => ele.to.name == 'Company-List')
    },
  },
  actions: {
    async logout(): Promise<void> {

      this.$reset();
    },
    async login(formulario: ILogin): Promise<IPromise> {
      this.loading = true;
      const { data, response } = await useApi("/login").post(
        formulario
      );
      this.loading = false;

      if (response.value?.ok && data.value) {
        this.isAuthenticate = true;
        this.user = data.value.user;
        this.company = data.value.company;
        this.menu = data.value.menu;
        this.permissions = data.value.permissions;
        this.access_token = data.value.access_token;
        useCookie("accessToken").value = this.access_token;
      }

      return data.value;
    },

    checkAuthentication() {
      // Implementa la lógica para verificar la autenticación
      const isAuthenticated = !!this.access_token; // Ejemplo: verifica si existe un token en localStorage

      return isAuthenticated && this.rememberMe;
    }
  },
});
