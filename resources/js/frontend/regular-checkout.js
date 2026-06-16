import { __ } from '@wordpress/i18n'
import { registerPaymentMethod } from '@woocommerce/blocks-registry'
import { getSetting } from '@woocommerce/settings'
import { decodeEntities } from '@wordpress/html-entities'
import { useEffect } from '@wordpress/element'
import Modal from './express/modal/modal'
import ModalContent from './express/modal/content'

const settings = getSetting('twint_regular_data', {})

const label =
  decodeEntities(settings.title) || __('TWINT', 'twint-woocommerce-extension')

const description = decodeEntities(settings.description || '')

/**
 * See https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md#payment-methods---registerpaymentmethod-options- * @param eventRegistration
 * @param eventRegistration
 * @param emitResponse
 * @returns {JSX.Element}
 * @constructor
 */
const ModalTwintPayment = ({ eventRegistration, emitResponse }) => {
  const { onCheckoutAfterProcessingWithSuccess } = eventRegistration
  useEffect(() => {
    const unsubscribe = onCheckoutAfterProcessingWithSuccess(
      async ({ processingResponse }) => {
        const details = processingResponse.paymentDetails
        if (details.result === 'success') {
          let modal = new Modal(
            window.twintShadowRoot,
            Modal.TYPE_REGULAR_CHECKOUT,
          )
          modal.setContent(
            new ModalContent(
              window.twintShadowRoot,
              details.pairingToken,
              details.amount,
              details.pairingId,
              false,
            ),
          )
          modal.show()
        }
      },
    )

    return () => unsubscribe()
  }, [
    emitResponse.noticeContexts.PAYMENTS,
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
  ])

  return description ? (
    <div className='wc-block-components-payment-method-description'>
      {description}
    </div>
  ) : (
    <></>
  )
}

const BlockEditorTwintComponent = () => {
  return (
    <div
      onClick={(data, actions) => {
        return false
      }}
    ></div>
  )
}

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
  const { PaymentMethodLabel } = props.components
  return (
    <span class='wc-block-components-payment-method-label TWINT'>
      <span class='twint-accessible-label'>{label}</span>
      <img
        alt=''
        src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNzUiIHZpZXdCb3g9IjAgMCA2OSAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxyZWN0IHdpZHRoPSI2OSIgaGVpZ2h0PSIzMiIgcng9IjYiIGZpbGw9IiMyNjI2MjYiLz4KICAgIDxwYXRoIGQ9Ik0yMy44NDkyIDE5LjY3OUMyMy44NDkyIDE5Ljg4NzIgMjMuNzAxNyAyMC4xMzg4IDIzLjUxOTUgMjAuMjQzTDE3LjE1MDggMjMuOTIxOUMxNi45Njg2IDI0LjAyNiAxNi42NzM1IDI0LjAyNiAxNi40OTEzIDIzLjkyMTlMMTAuMTIyNiAyMC4yNDNDOS45NDAzNSAyMC4xMzg4IDkuNzkyODUgMTkuODg3MiA5Ljc5Mjg1IDE5LjY3OVYxMi4zMjFDOS43OTI4NSAxMi4xMTI4IDkuOTQwMzUgMTEuODYxMiAxMC4xMjI2IDExLjc1NzFMMTYuNDkxMyA4LjA3ODA5QzE2LjY3MzUgNy45NzM5NyAxNi45Njg2IDcuOTczOTcgMTcuMTUwOCA4LjA3ODA5TDIzLjUxOTUgMTEuNzU3MUMyMy43MDE3IDExLjg2MTIgMjMuODQ5MiAxMi4xMTI4IDIzLjg0OTIgMTIuMzIxVjE5LjY3OVoiIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNNTkuMjA3MiAxMi44NTAzSDUzLjUwNjVWMTQuMjEyNkg1NS41NTQyVjIwLjAzNDdINTcuMTY4MVYxNC4yMTI2SDU5LjIwNzJWMTIuODUwM1oiIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNMzMuMzA2OSAxMi44NTAzSDI3LjU5NzZWMTQuMjEyNkgyOS42NDUzVjIwLjAzNDdIMzEuMjU5MlYxNC4yMTI2SDMzLjMwNjlWMTIuODUwM1oiIGZpbGw9IndoaXRlIi8+CiAgICA8cGF0aCBkPSJNNDkuODEwMiAxMi42MjQ3QzQ4LjAxNDEgMTIuNjI0NyA0Ny4wMTYzIDEzLjc3MDEgNDcuMDE2MyAxNS40MjczVjIwLjAzNDdINDguNjEyOFYxNS4zOTI2QzQ4LjYxMjggMTQuNjcyNSA0OS4wMzggMTQuMTE3MSA0OS44Mjc2IDE0LjExNzFDNTAuNjE3MSAxNC4xMTcxIDUxLjAzMzYgMTQuNzc2NiA1MS4wMzM2IDE1LjM5MjZWMjAuMDM0N0g1Mi42MzAyVjE1LjQyNzNDNTIuNjM4OCAxMy43NzAxIDUxLjYwNjMgMTIuNjI0NyA0OS44MTAyIDEyLjYyNDdaIiBmaWxsPSJ3aGl0ZSIvPgogICAgPHBhdGggZD0iTTQzLjc4ODUgMTIuODUwM1YyMC4wMzQ3SDQ1LjM4NVYxMi44NTAzSDQzLjc4ODVaIiBmaWxsPSJ3aGl0ZSIvPgogICAgPHBhdGggZD0iTTM4LjM3NDIgMTUuNjk2M0wzOC40MjYzIDE2LjAxNzRMMzkuOTI3MyAyMC4wMzQ3SDQwLjU3ODFMNDIuNjE3MSAxMi44NTAzSDQxLjA0NjZMNDAuMDc0OCAxNi42MjQ3TDQwLjAxNDEgMTcuMDMyNUwzOS45MzYgMTYuNjI0N0wzOC42MzQ1IDEyLjg1MDNIMzguMTEzOUwzNi44MTI0IDE2LjYyNDdMMzYuNzM0MyAxNy4wMzI1TDM2LjY4MjIgMTYuNjI0N0wzNS43MTA0IDEyLjg1MDNIMzQuMTMxMkwzNi4xNzAzIDIwLjAzNDdIMzYuODIxTDM4LjMyMjEgMTYuMDE3NEwzOC4zNzQyIDE1LjY5NjNaIiBmaWxsPSJ3aGl0ZSIvPgogICAgPHBhdGggZD0iTTIwLjE0NDMgMTYuMDE3NEwxOC40NzgzIDE4LjQ1NTVMMTcuNjI4IDE3LjE1NEwxOC42MDg1IDE1LjY4NzZDMTguNzkwNyAxNS40MjczIDE5LjE4MTEgMTQuNzA3MiAxOC43Mjk5IDEzLjcyNjdDMTguMzY1NSAxMi45Mjg0IDE3LjU3NTkgMTIuNTQ2NiAxNi44ODE4IDEyLjU0NjZDMTYuMTg3NiAxMi41NDY2IDE1LjQzMjggMTIuOTAyNCAxNS4wMjQ5IDEzLjcyNjdDMTQuNTY1MSAxNC42NzI1IDE0Ljk2NDIgMTUuNDEgMTUuMTM3NyAxNS42NjE2QzE1LjEzNzcgMTUuNjYxNiAxNS42ODQ0IDE2LjQ1OTkgMTYuMTM1NiAxNy4xMzY3TDE2Ljg3MzEgMTguMjAzOUwxNy45ODM3IDE5LjkwNDZDMTcuOTkyNCAxOS45MTMyIDE4LjE2NTkgMjAuMTgyMiAxOC40NzgzIDIwLjE4MjJDMTguNzczMyAyMC4xODIyIDE4Ljk1NTUgMTkuOTEzMiAxOC45ODE2IDE5Ljg4NzJMMjEuNTg0NiAxNi4wMzQ3SDIwLjE0NDNWMTYuMDE3NFpNMTYuODczMSAxNi4wNzgxQzE2Ljg3MzEgMTYuMDc4MSAxNi40MzkzIDE1LjQxODcgMTYuMTUyOSAxNC45NTg4QzE1Ljg0OTIgMTQuNDY0MiAxNi4xODc2IDEzLjcyNjcgMTYuODczMSAxMy43MjY3QzE3LjU1ODYgMTMuNzI2NyAxNy44OTcgMTQuNDY0MiAxNy41OTMzIDE0Ljk1ODhDMTcuMzE1NiAxNS40MTg3IDE2Ljg3MzEgMTYuMDc4MSAxNi44NzMxIDE2LjA3ODFaIiBmaWxsPSJ1cmwoI3BhaW50MF9yYWRpYWxfMV84MDQpIi8+CiAgICA8cGF0aCBkPSJNMTUuMjc2NiAxOC4zNzc0TDEzLjY0NTMgMTYuMDg2OEMxMy42NDUzIDE2LjA4NjggMTMuMjExNSAxNS40MjczIDEyLjkyNTIgMTQuOTY3NUMxMi42MjE1IDE0LjQ3MjkgMTIuOTU5OSAxMy43MzU0IDEzLjY0NTMgMTMuNzM1NEMxMy43MzIxIDEzLjczNTQgMTMuODEwMiAxMy43NDQgMTMuODg4MyAxMy43NzAxTDE0LjQ2MSAxMi43MjAyQzE0LjIwMDcgMTIuNjA3NCAxMy45MTQzIDEyLjU0NjYgMTMuNjU0IDEyLjU0NjZDMTIuOTU5OSAxMi41NDY2IDEyLjIwNSAxMi45MDI0IDExLjc5NzIgMTMuNzI2N0MxMS4zMzczIDE0LjY3MjUgMTEuNzM2NCAxNS40MSAxMS45MSAxNS42NjE2TDE0Ljc2NDYgMTkuODg3MkMxNC43OTA3IDE5LjkyMTkgMTQuOTcyOSAyMC4xOTA5IDE1LjI3NjYgMjAuMTkwOUMxNS41ODAzIDIwLjE5MDkgMTUuNzUzOCAxOS45MzA2IDE1Ljc3OTggMTkuODk1OUwxNi42Mzg4IDE4LjU4NTdMMTUuOTAxMyAxNy41MDExTDE1LjI3NjYgMTguMzc3NFoiIGZpbGw9InVybCgjcGFpbnQxX3JhZGlhbF8xXzgwNCkiLz4KICAgIDxkZWZzPgogICAgICAgIDxyYWRpYWxHcmFkaWVudCBpZD0icGFpbnQwX3JhZGlhbF8xXzgwNCIgY3g9IjAiIGN5PSIwIiByPSIxIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgyMC44ODY2IDkuNDAxNTgpIHNjYWxlKDU1Ljk4IDE2LjA3OTMpIj4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iI0ZGQ0MwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuMDkxNTY4NCIgc3RvcC1jb2xvcj0iI0ZGQzgwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuMTczOSIgc3RvcC1jb2xvcj0iI0ZGQkQwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuMjUyOCIgc3RvcC1jb2xvcj0iI0ZGQUIwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuMzI5NSIgc3RvcC1jb2xvcj0iI0ZGOTEwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuNDA0NiIgc3RvcC1jb2xvcj0iI0ZGNzAwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuNDc4NiIgc3RvcC1jb2xvcj0iI0ZGNDcwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuNTUwMyIgc3RvcC1jb2xvcj0iI0ZGMTgwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuNTgyMiIgc3RvcC1jb2xvcj0iI0ZGMDAwMCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiNGRjAwMDAiLz4KICAgICAgICA8L3JhZGlhbEdyYWRpZW50PgogICAgICAgIDxyYWRpYWxHcmFkaWVudCBpZD0icGFpbnQxX3JhZGlhbF8xXzgwNCIgY3g9IjAiIGN5PSIwIiByPSIxIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgxMS43MDYzIDEzLjY1Nykgc2NhbGUoNy4wMzU5KSI+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiMwMEI0RTYiLz4KICAgICAgICAgICAgPHN0b3Agb2Zmc2V0PSIwLjIwMSIgc3RvcC1jb2xvcj0iIzAwQjBFMyIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuMzg5OCIgc3RvcC1jb2xvcj0iIzAxQTVEQiIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuNTczNyIgc3RvcC1jb2xvcj0iIzAyOTJDRCIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuNzU0NiIgc3RvcC1jb2xvcj0iIzAzNzdCQSIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjAuOTMxNiIgc3RvcC1jb2xvcj0iIzA0NTVBMSIvPgogICAgICAgICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMwNTQ2OTYiLz4KICAgICAgICA8L3JhZGlhbEdyYWRpZW50PgogICAgPC9kZWZzPgo8L3N2Zz4K'
      />
    </span>
  )
}

/**
 * Twint Regular payment method config object.
 */
const TwintRegular = {
  name: 'twint_regular',
  label: <Label />,
  content: <ModalTwintPayment />,
  edit: <BlockEditorTwintComponent />,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: ['products', 'refunds', 'captured_payments'],
  },
}

registerPaymentMethod(TwintRegular)
