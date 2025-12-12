interface IOptionsFilter {
  showFilter?: boolean,
  showBtnSearch: boolean,
  inputGeneral: {
    placeholder: string,
    relationsGeneral: object,
  },
  dialog: {
    cols: number | string
    width: number | string,
    inputs: Array<IItemFilter>,
  }
}
