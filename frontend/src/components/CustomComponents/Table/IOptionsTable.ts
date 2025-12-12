interface IOptionsTable {
  url: null | string
  searchQuery: object
  params: object
  showSelect: boolean
  selected: any[]
  expandOnClick: boolean
  showHeader: boolean
  headers: any[]
  tableData: any[]
  sortBy: any[]
  multiSort: true
  pagination: {
    show: true
    rowPerPage: number
    lastPage: number
    totalData: number
    totalPage: number
    currentPage: number
  }
  actions: {
    styleShow: string,
    changeStatus: {
      show: boolean
      url: null | string
      dbField: string
      btnActive: {
        text: string
        value: number | string
        newFunction: any
      }
      btnInactive: {
        text: string
        value: number | string
        newFunction: any 
      }
    }
    view: {
      show: boolean
      url: null | string
      tooltipText: string
      icon: string
    }
    edit: {
      show: boolean
      url: null | string
      tooltipText: string
      icon: string
    }
    delete: {
      show: boolean
      url: null | string
      tooltipText: string
      icon: string
    }
  }
}
