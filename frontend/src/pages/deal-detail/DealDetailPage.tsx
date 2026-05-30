import { useNavigate, useParams } from 'react-router-dom'
import { DealDetailView, useDealDetailPage } from '@/features/deal-detail'

export function DealDetailPage() {
  const { dealId } = useParams()
  const navigate = useNavigate()
  const page = useDealDetailPage(dealId)

  return (
    <DealDetailView
      {...page}
      onBack={() => {
        void navigate('/')
      }}
    />
  )
}
