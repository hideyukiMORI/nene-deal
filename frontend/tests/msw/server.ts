import { setupServer } from 'msw/node'
import { boardHandlers } from './handlers/board'
import { dealHandlers } from './handlers/deal'
import { forecastHandlers } from './handlers/forecast'
import { stageHandlers } from './handlers/pipeline-stage'

export const mswServer = setupServer(
  ...stageHandlers,
  ...boardHandlers,
  ...forecastHandlers,
  ...dealHandlers,
)
