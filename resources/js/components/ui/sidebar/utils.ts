import type { ComputedRef, Ref } from "vue"
import { createContext } from "reka-ui"

// Nama cookie baru: nilai lama (sebelum sidebar gaya Filament) tidak dipakai lagi
export const SIDEBAR_COOKIE_NAME = "sidebar_open"
export const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 365
// Gaya Filament: ikon 24px + label; kepala sidebar memuat logo + nama aplikasi utuh
export const SIDEBAR_WIDTH = "18rem"
export const SIDEBAR_WIDTH_MOBILE = "18rem"
// Saat diciutkan: deretan ikon 40px dengan jarak 12px di kiri-kanan
export const SIDEBAR_WIDTH_ICON = "4rem"
export const SIDEBAR_KEYBOARD_SHORTCUT = "b"

export const [useSidebar, provideSidebarContext] = createContext<{
  state: ComputedRef<"expanded" | "collapsed">
  open: Ref<boolean>
  setOpen: (value: boolean) => void
  isMobile: Ref<boolean>
  openMobile: Ref<boolean>
  setOpenMobile: (value: boolean) => void
  toggleSidebar: () => void
}>("Sidebar")
