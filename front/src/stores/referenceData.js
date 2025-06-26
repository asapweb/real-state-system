// src/stores/referenceData.js
import { defineStore } from 'pinia'
import axios from '@/services/axios'

export const useReferenceDataStore = defineStore('referenceData', {
  state: () => ({
    countries: null,
    states: [],
    cities: [],
    neighborhoods: [],
    propertyTypes: null,
    documentTypes: null,
    taxConditions: null,
    defaultCountry: null,
    defaultState: null,
    defaultCity: null,
    defaultNeighborhood: null,
    defaultPropertyType: null,
  }),
  actions: {
    async fetchCountries() {
      if (!this.countries) {
        const response = await axios.get('/api/countries')
        this.countries = response.data
        this.defaultCountry = response.data.find((country) => country.default) || null
      }
    },
    async fetchStates(countryId) {
      if (!this.states[countryId]) {
        const response = await axios.get(`/api/states/${countryId}`)
        this.states[countryId] = response.data
        if (!this.defaultState || this.defaultState.country_id === countryId) {
          this.defaultState = response.data.find((state) => state.default) || null
        }
      }
    },
    async fetchCities(stateId) {
      if (!this.cities[stateId]) {
        const response = await axios.get(`/api/cities/${stateId}`)
        this.cities[stateId] = response.data
        if (!this.defaultCity || this.defaultCity.country_id === stateId) {
          this.defaultCity = response.data.find((city) => city.default) || null
        }
      }
    },
    async fetchNeighborhoods(cityId) {
      if (!this.neighborhoods[cityId]) {
        const response = await axios.get(`/api/neighborhoods/${cityId}`)
        this.neighborhoods[cityId] = response.data
        if (!this.defaultNeighborhood || this.defaultNeighborhood.country_id === cityId) {
          this.defaultNeighborhood =
            response.data.find((neighborhood) => neighborhood.default) || null
        }
      }
    },
    async fetchDocumentTypes() {
      if (!this.documentTypes) {
        const response = await axios.get('/api/document-types')
        this.documentTypes = response.data
      }
    },
    async fetchTaxConditions() {
      if (!this.taxConditions) {
        const response = await axios.get('/api/tax-conditions')
        this.taxConditions = response.data
      }
    },
    async fetchPropertyTypes() {
      console.log('Fetching property types')
      if (!this.propertyTypes) {
        const response = await axios.get('/api/property-types')
        this.propertyTypes = response.data
        this.defaultPropertyType = response.data.find((type) => type.default) || null
      }
    },
    getDocumentTypesByClientType(clientType) {
      if (!this.documentTypes) return []
      return this.documentTypes.filter(
        (dt) => dt.applies_to === clientType || dt.applies_to === 'both',
      )
    },
    getCitiesByStateId(stateId) {
      if (!stateId) return []
      return this.cities[stateId] || []
    },
    getNeighborhoodsByCityId(cityId) {
      if (!cityId) return []
      return this.neighborhoods[cityId] || []
    },
  },
})
