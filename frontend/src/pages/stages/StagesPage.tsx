import { StageManagementView, useStageManagementPage } from '@/features/stage-management'

export function StagesPage() {
  const page = useStageManagementPage()
  return <StageManagementView {...page} />
}
