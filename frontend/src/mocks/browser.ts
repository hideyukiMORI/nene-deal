import { setupWorker } from 'msw/browser'
import { authHandlers } from '@tests/msw/handlers/auth'
import { boardHandlers } from '@tests/msw/handlers/board'
import { dealHandlers } from '@tests/msw/handlers/deal'
import { forecastHandlers } from '@tests/msw/handlers/forecast'
import { settingsHandlers } from '@tests/msw/handlers/settings'
import { stageHandlers } from '@tests/msw/handlers/pipeline-stage'
import { userHandlers } from '@tests/msw/handlers/user'

export const worker = setupWorker(
  ...authHandlers,
  ...stageHandlers,
  ...boardHandlers,
  ...forecastHandlers,
  ...dealHandlers,
  ...userHandlers,
  ...settingsHandlers,
)
