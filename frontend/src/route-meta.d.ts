import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    layout?: 'app' | 'admin' | 'auth' | 'blank' | 'public'
    requiresAuth?: boolean
    requiresSuperAdmin?: boolean
    guest?: boolean
    title?: string
  }
}
