/**
 * Line-icon set for the Calm design system. Each icon inherits `currentColor`
 * and accepts a `className` (the design CSS sizes `.ico`, nav icons, etc.).
 */
import type { SVGProps } from 'react'

type IconProps = SVGProps<SVGSVGElement>

const base = {
  fill: 'none',
  stroke: 'currentColor',
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
} as const

export function IconBoard(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" strokeWidth={1.8} {...base} {...props}>
      <rect x="3" y="4" width="5" height="16" rx="1" />
      <rect x="10" y="4" width="5" height="11" rx="1" />
      <rect x="17" y="4" width="4" height="7" rx="1" />
    </svg>
  )
}

export function IconStages(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" strokeWidth={1.8} {...base} {...props}>
      <path d="M12 3 21 8 12 13 3 8z" />
      <path d="M3 13l9 5 9-5" />
    </svg>
  )
}

export function IconUsers(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" strokeWidth={1.8} {...base} {...props}>
      <circle cx="9" cy="8" r="3.2" />
      <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
      <path d="M16 6.2a3 3 0 0 1 0 5.6" />
      <path d="M18 19a5 5 0 0 0-2.6-4.4" />
    </svg>
  )
}

export function IconInvoice(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" strokeWidth={1.8} {...base} {...props}>
      <path d="M7 3h7l5 5v13H7z" />
      <path d="M13 3v6h6" />
      <path d="M10 14h6M10 17.5h4" />
    </svg>
  )
}

export function IconBack(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="15" height="15" strokeWidth={2} {...base} {...props}>
      <path d="M14 6l-6 6 6 6" />
    </svg>
  )
}

export function IconChevron(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="13" height="13" strokeWidth={2} {...base} {...props}>
      <path d="M9 6l6 6-6 6" />
    </svg>
  )
}

export function IconPlus(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="15" height="15" strokeWidth={2.2} {...base} {...props}>
      <path d="M12 5v14M5 12h14" />
    </svg>
  )
}

export function IconArrowOut(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="15" height="15" strokeWidth={1.9} {...base} {...props}>
      <path d="M7 17 17 7M9 7h8v8" />
    </svg>
  )
}

export function IconCheck(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="14" height="14" strokeWidth={2.2} {...base} {...props}>
      <path d="M5 12l4.5 4.5L19 7" />
    </svg>
  )
}

export function IconShield(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="13" height="13" strokeWidth={1.8} {...base} {...props}>
      <path d="M12 3l7 3v5c0 5-3.5 8-7 10-3.5-2-7-5-7-10V6z" />
    </svg>
  )
}

export function IconLogo(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" strokeWidth={1.8} {...base} {...props}>
      <rect x="3.5" y="3.5" width="17" height="17" rx="4" />
      <path d="M8 16V8l8 8V8" strokeWidth={2} />
    </svg>
  )
}

export function IconGlobe(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="15" height="15" strokeWidth={1.7} {...base} {...props}>
      <circle cx="12" cy="12" r="9" />
      <path d="M3 12h18" />
      <path d="M12 3c2.6 2.4 4 5.6 4 9s-1.4 6.6-4 9c-2.6-2.4-4-5.6-4-9s1.4-6.6 4-9z" />
    </svg>
  )
}

export function IconSun(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="15" height="15" strokeWidth={1.9} {...base} {...props}>
      <circle cx="12" cy="12" r="4" />
      <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
    </svg>
  )
}

export function IconMoon(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="15" height="15" strokeWidth={1.9} {...base} {...props}>
      <path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z" />
    </svg>
  )
}

export function IconClose(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" strokeWidth={2} {...base} {...props}>
      <path d="M6 6l12 12M18 6L6 18" />
    </svg>
  )
}

export function IconAccount(props: IconProps) {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" strokeWidth={1.7} {...base} {...props}>
      <circle cx="12" cy="9" r="3.2" />
      <path d="M5.5 19.5a6.5 6.5 0 0 1 13 0" />
      <circle cx="12" cy="12" r="9.2" />
    </svg>
  )
}
