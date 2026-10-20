import apiFetch from '@wordpress/api-fetch'

let currentNonce = ''
let registered = false

function getNonce() {
  return (
    currentNonce ||
    window.twintExpress?.nonce ||
    window.twintFinalize?.nonce ||
    window.twintNonce ||
    ''
  )
}

/**
 * Normalize a non-2xx error: if caller wants raw Response, throw it as-is.
 * Otherwise parse JSON body and throw the parsed object (apiFetch default).
 */
function rejectLikeFetch(response, callerWantsRaw) {
  if (callerWantsRaw) {
    throw response
  }

  return response
    .json()
    .catch(() => {
      throw response
    })
    .then((body) => {
      throw body
    })
}

const twintNonceMiddleware = (options, next) => {
  const { path, url } = options
  const isTwintRoute =
    (path && path.startsWith('/twint/v1/')) ||
    (url && url.includes('/twint/v1/'))

  if (!isTwintRoute) {
    return next(options)
  }

  const callerWantsRaw = options.parse === false
  const nonce = getNonce()
  const headers = { ...(options.headers || {}), 'X-TWINT-Nonce': nonce }
  const tweaked = { ...options, headers, parse: false }

  return next(tweaked)
    .then((response) => {
      // 2xx success: read fresh nonce, return in caller's expected format
      const fresh = response.headers?.get('X-TWINT-Nonce')
      if (fresh) {
        currentNonce = fresh
      }

      if (callerWantsRaw) {
        return response
      }

      return response.json()
    })
    .catch((err) => {
      // apiFetch with parse:false rejects non-2xx with raw Response
      if (!(err instanceof Response)) {
        throw err
      }

      const fresh = err.headers?.get('X-TWINT-Nonce')
      if (fresh) {
        currentNonce = fresh
      }

      // Retry once on auth failure with fresh nonce (cache/session mismatch)
      // WordPress returns 401 for guests, 403 for logged-in users
      if ((err.status === 401 || err.status === 403) && fresh) {
        const retryHeaders = {
          ...(options.headers || {}),
          'X-TWINT-Nonce': fresh,
        }
        const retryOpts = { ...options, headers: retryHeaders, parse: false }

        return next(retryOpts)
          .then((retryResp) => {
            const retryNonce = retryResp.headers?.get('X-TWINT-Nonce')
            if (retryNonce) {
              currentNonce = retryNonce
            }

            if (callerWantsRaw) {
              return retryResp
            }

            return retryResp.json()
          })
          .catch((retryErr) => {
            // Retry also failed — normalize the error for the caller
            if (retryErr instanceof Response) {
              return rejectLikeFetch(retryErr, callerWantsRaw)
            }

            throw retryErr
          })
      }

      // Non-auth error: normalize for caller
      return rejectLikeFetch(err, callerWantsRaw)
    })
}

/**
 * Register the middleware exactly once, even if multiple bundles call this.
 */
export default function registerTwintNonceMiddleware() {
  if (registered || window.__twintNonceMiddlewareRegistered) {
    return
  }

  registered = true
  window.__twintNonceMiddlewareRegistered = true
  apiFetch.use(twintNonceMiddleware)
}
