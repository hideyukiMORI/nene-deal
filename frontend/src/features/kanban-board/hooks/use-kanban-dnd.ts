import { useRef, type PointerEvent as ReactPointerEvent, type RefObject } from 'react'

export interface KanbanDnd {
  boardRef: RefObject<HTMLDivElement | null>
  onPointerDown: (event: ReactPointerEvent<HTMLDivElement>) => void
}

/**
 * Pointer-based kanban drag & drop matching the design spec: a lifted clone
 * follows the cursor, the source card leaves a dashed ghost, the target column
 * highlights (`drop-ok`) and an insertion marker shows the drop position.
 *
 * Pointer events + `touch-action: none` on `.deal` make this work identically
 * with mouse and touch, so there is no separate mobile interaction.
 *
 * `onMove` is only called for cross-stage drops — board ordering is derived
 * server-side, so same-stage reordering is visual-only and not persisted.
 */
export function useKanbanDnd(
  onMove: (dealId: string, fromStageSlug: string, toStageSlug: string) => void,
): KanbanDnd {
  const boardRef = useRef<HTMLDivElement>(null)

  function onPointerDown(event: ReactPointerEvent<HTMLDivElement>): void {
    if (event.button !== 0) return
    const origin = event.target as HTMLElement
    const card = origin.closest<HTMLElement>('.deal[data-deal]')
    if (card === null) return
    if (origin.closest('.details-link') !== null) return // let the details link click through
    const boardEl = boardRef.current
    if (boardEl === null) return

    const startX = event.clientX
    const startY = event.clientY
    const rect = card.getBoundingClientRect()
    const offX = startX - rect.left
    const offY = startY - rect.top
    const dealId = card.getAttribute('data-deal') ?? ''
    const fromStage =
      card.closest<HTMLElement>('.col[data-stage]')?.getAttribute('data-stage') ?? null

    let started = false
    let clone: HTMLElement | null = null
    let marker: HTMLElement | null = null
    let targetStage: string | null = null

    function begin(): void {
      // boardEl/card are already checked non-null above; nested closures don't
      // retain that narrowing, so re-guard here (never actually null at runtime).
      if (boardEl === null || card === null) return
      started = true
      // Drop any text selection that began before the drag threshold was met.
      window.getSelection()?.removeAllRanges()
      boardEl.classList.add('is-dragging')
      clone = card.cloneNode(true) as HTMLElement
      clone.classList.add('deal-drag')
      clone.style.width = `${String(rect.width)}px`
      clone.style.height = `${String(rect.height)}px`
      document.body.appendChild(clone)
      card.classList.add('is-ghost')
      marker = document.createElement('div')
      marker.className = 'drop-marker'
    }

    function moveClone(x: number, y: number): void {
      if (clone === null) return
      clone.style.transform = `translate(${String(x - offX)}px, ${String(y - offY)}px) rotate(-1.5deg) scale(1.025)`
    }

    function updateTarget(x: number, y: number): void {
      // boardEl is already checked non-null above; see note in begin().
      if (boardEl === null) return
      let colEl: HTMLElement | null = null
      for (const el of document.elementsFromPoint(x, y)) {
        if (el instanceof HTMLElement && el.classList.contains('col')) {
          colEl = el
          break
        }
      }
      boardEl.querySelectorAll('.col.drop-ok').forEach((c) => {
        if (c !== colEl) c.classList.remove('drop-ok')
      })
      if (colEl === null || marker === null) {
        targetStage = null
        marker?.remove()
        return
      }
      colEl.classList.add('drop-ok')
      targetStage = colEl.getAttribute('data-stage')
      const body = colEl.querySelector('.col-body')
      if (body === null) return
      let before: Element | null = null
      for (const c of body.querySelectorAll('.deal:not(.is-ghost)')) {
        const r = c.getBoundingClientRect()
        if (y < r.top + r.height / 2) {
          before = c
          break
        }
      }
      body.insertBefore(marker, before ?? body.querySelector('.col-empty'))
    }

    function cleanup(): void {
      document.removeEventListener('pointermove', onPointerMove)
      document.removeEventListener('pointerup', onPointerUp)
      document.removeEventListener('pointercancel', onPointerUp)
      clone?.remove()
      marker?.remove()
      // boardEl/card are already checked non-null above; see note in begin().
      if (boardEl === null || card === null) return
      boardEl.classList.remove('is-dragging')
      boardEl.querySelectorAll('.col.drop-ok').forEach((c) => {
        c.classList.remove('drop-ok')
      })
      card.classList.remove('is-ghost')
    }

    function onPointerMove(ev: PointerEvent): void {
      if (!started) {
        if (Math.abs(ev.clientX - startX) + Math.abs(ev.clientY - startY) < 6) return
        begin()
      }
      ev.preventDefault()
      moveClone(ev.clientX, ev.clientY)
      updateTarget(ev.clientX, ev.clientY)
    }

    function onPointerUp(): void {
      const dropped = started
      const to = targetStage
      cleanup()
      if (dropped && to !== null && fromStage !== null && to !== fromStage) {
        onMove(dealId, fromStage, to)
      }
    }

    document.addEventListener('pointermove', onPointerMove)
    document.addEventListener('pointerup', onPointerUp)
    document.addEventListener('pointercancel', onPointerUp)
  }

  return { boardRef, onPointerDown }
}
