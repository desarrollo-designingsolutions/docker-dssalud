import { isEmpty, isEmptyArray, isNullOrUndefined } from './helpers'

// 👉 Required Validator
export const requiredValidator = (value: unknown) => {
  if (isNullOrUndefined(value) || isEmptyArray(value) || value === false)
    return 'Este campo es obligatorio'

  return !!String(value).trim().length || 'Este campo es obligatorio'
}

// 👉 Email Validator
export const emailValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  const re = /^(?:[^<>()[\]\\.,;:\s@"]+(?:\.[^<>()[\]\\.,;:\s@"]+)*|".+")@(?:\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\]|(?:[a-z\-\d]+\.)+[a-z]{2,})$/i

  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'El campo de correo electrónico debe ser un correo electrónico válido'

  return re.test(String(value)) || 'El campo de correo electrónico debe ser un correo electrónico válido'
}

// 👉 Password Validator
export const passwordValidator = (password: string) => {
  const regExp = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%&*()]).{8,}/

  const validPassword = regExp.test(password)

  return validPassword || ' El campo debe contener al menos una mayúscula, una minúscula, un carácter especial y un dígito con un mínimo de 8 caracteres.'
}

// 👉 Confirm Password Validator
export const confirmedValidator = (value: string, target: string) =>

  value === target || 'La confirmación del campo Confirmar contraseña no coincide'

// 👉 Between Validator
export const betweenValidator = (value: unknown, min: number, max: number) => {
  const valueAsNumber = Number(value)

  return (Number(min) <= valueAsNumber && Number(max) >= valueAsNumber) || `Introduzca el número entre ${min} y ${max}`
}

// 👉 Integer Validator
export const integerValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  if (Array.isArray(value))
    return value.every(val => /^-?\d+$/.test(String(val))) || 'Este campo debe ser un número entero'

  return /^-?\d+$/.test(String(value)) || 'Este campo debe ser un número entero'
}

// 👉 Regex Validator
export const regexValidator = (value: unknown, regex: RegExp | string): string | boolean => {
  if (isEmpty(value))
    return true

  let regeX = regex
  if (typeof regeX === 'string')
    regeX = new RegExp(regeX)

  if (Array.isArray(value))
    return value.every(val => regexValidator(val, regeX))

  return regeX.test(String(value)) || 'El formato del campo Regex no es válido'
}

// 👉 Alpha Validator
export const alphaValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  return /^[A-Z]*$/i.test(String(value)) || 'El campo sólo puede contener caracteres alfabéticos'
}

// 👉 URL Validator
export const urlValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  const re = /^https?:\/\/[^\s$.?#].\S*$/

  return re.test(String(value)) || 'La URL no es válida'
}

// 👉 Length Validator
export const lengthValidator = (value: unknown, length: number) => {
  if (isEmpty(value))
    return true

  return String(value).length === length || `"La longitud del campo debe ser ${length} caracteres."`
}

// 👉 Alpha-dash Validator
export const alphaDashValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  const valueAsString = String(value)

  return /^[\w-]*$/.test(valueAsString) || 'Todos los caracteres no son válidos'
}


export const maxCharacters = (value: string, max: number) => {
  if (value) {
    return value.length <= max || `Máximo ${max} caracteres`
  } else {
    return false
  }
}

export const minCharacters = (value: string, min: number) => {
  if (value) {

    return value.length >= min || `Mínimo ${min} caracteres`
  } else {
    return false
  }
}

export const positiveNumberValidator = (value: string) => {
  if (value) {
    return /^[0-9]*$/i.test(value) || "El numero no debe ser negativo"
  } else {
    return false
  }
}

export const validateEndTime = (value: string, startTime: string) => {
  if (!startTime || !value) return true // No validamos si no hay ambos valores

  const [startHour, startMin] = startTime.split(':').map(Number)
  const [endHour, endMin] = value.split(':').map(Number)

  const start = startHour * 60 + startMin
  const end = endHour * 60 + endMin

  return end > start || 'La hora fin debe ser mayor a la hora inicio'
}

export const validateStartTime = (startTime: string, endTime: string): true | string => {
  if (!startTime || !endTime) return true

  const [startHour, startMin] = startTime.split(':').map(Number)
  const [endHour, endMin] = endTime.split(':').map(Number)

  const startTotalMinutes = startHour * 60 + startMin
  const endTotalMinutes = endHour * 60 + endMin

  return startTotalMinutes < endTotalMinutes
    ? true
    : 'La hora inicio debe ser menor a la hora fin'
}
