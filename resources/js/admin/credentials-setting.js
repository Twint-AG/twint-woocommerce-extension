import axios from 'axios'
import jquery from 'jquery'
import { __ } from '@wordpress/i18n'

document.addEventListener('DOMContentLoaded', function (event) {
  class CredentialsSetting {
    constructor() {
      this.state = {
        plugin_twint_test_mode: false,
        plugin_twint_settings_store_uuid: null,
        plugin_twint_settings_certificate: null,
        plugin_twint_settings_certificate_password: null,
        openUploadCertificateArea: false,
        showNotifyValidCredentials: false,
      }

      this.options = {
        adminUrl: twint_api.admin_url,
        testModeSelector: '#plugin_twint_test_mode',
        storeUuidSelector: '#plugin_twint_settings_store_uuid',
        certificateFileSelector: 'twint-file-upload',
        deleteCertificateSelector: 'kwt-file__delete',
        passwordSelector: '#plugin_twint_settings_certificate_password',
        uploadNewCertificateSelector: '#upload-new-certificate',
        closeUploadNewCertificateSelector: '#close-new-certificate',
        btnSelector: '#js_twint_button_save',
        noticeSuccess: '#notice-admin-success',
        noticeError: '#notice-admin-error',
        configurationNoticeSuccess: '#notice_success_configuration_settings',
        configurationNoticeError: '#notice_error_configuration_settings',
      }
    }

    init() {
      this.storeUuidInput = document.querySelector(
        this.options.storeUuidSelector,
      )
      if (this.storeUuidInput) {
        this.state.plugin_twint_settings_store_uuid = this.storeUuidInput.value
        this.storeUuidInput.addEventListener(
          'input',
          this.onChangeStoreUuid.bind(this),
        )
      }

      this.button = document.querySelector(this.options.btnSelector)
      if (this.button) {
        this.button.addEventListener('click', this.onClick.bind(this))
      }

      this.configNoticeSuccess = document.querySelector(
        this.options.configurationNoticeSuccess,
      )
      this.configNoticeError = document.querySelector(
        this.options.configurationNoticeError,
      )

      // Wait 500ms for the input to be rendered on DOM.
      setTimeout(() => {
        this.certificateFile = document.getElementsByClassName(
          this.options.certificateFileSelector,
        )
        const $this = this
        for (let i = 0; i < this.certificateFile.length; ++i) {
          ;(function (index) {
            $this.certificateFile[index].addEventListener(
              'change',
              $this.onChangeCertificateFile.bind($this),
            )
          })(i)
        }

        this.deleteCertificateFile = document.getElementsByClassName(
          this.options.deleteCertificateSelector,
        )
        for (let i = 0; i < this.deleteCertificateFile.length; ++i) {
          ;(function (index) {
            $this.deleteCertificateFile[index].addEventListener(
              'click',
              $this.onDeleteCertificateFile.bind($this),
            )
          })(i)
        }
      }, 500)

      this.testMode = document.querySelector(this.options.testModeSelector)
      if (this.testMode) {
        // Init test mode value
        this.state.plugin_twint_test_mode = this.testMode.value

        this.testMode.addEventListener(
          'change',
          this.onChangeTestMode.bind(this),
        )
      }

      this.passwordInput = document.querySelector(this.options.passwordSelector)
      if (this.passwordInput) {
        this.passwordInput.addEventListener(
          'change',
          this.onCertificatePasswordChange.bind(this),
        )
      }

      this.uploadNewCertificateButton = document.querySelector(
        this.options.uploadNewCertificateSelector,
      )
      if (this.uploadNewCertificateButton) {
        this.uploadNewCertificateButton.addEventListener(
          'click',
          this.uploadNewCertificate.bind(this),
        )
      }
      this.closeUploadNewCertificateButton = document.querySelector(
        this.options.closeUploadNewCertificateSelector,
      )
      if (this.closeUploadNewCertificateButton) {
        this.closeUploadNewCertificateButton.addEventListener(
          'click',
          this.toggleUploadNewCertBtn.bind(this),
        )
      }

      this.$noticeSuccess = document.querySelector(this.options.noticeSuccess)
      this.$noticeError = document.querySelector(this.options.noticeError)
    }

    toggleCertificateArea() {
      const $certificate = jQuery('tr.plugin_twint_settings_certificate')
      if ($certificate.hasClass('hidden')) {
        $certificate.removeClass('hidden')
      } else {
        $certificate.addClass('hidden')
      }
      const $certificatePassword = jQuery(
        'tr.plugin_twint_settings_certificate_password',
      )
      if ($certificatePassword.hasClass('hidden')) {
        $certificatePassword.removeClass('hidden')
      } else {
        $certificatePassword.addClass('hidden')
      }
    }

    toggleLoadingButton() {
      if (this.button?.classList?.contains('button-loading')) {
        this.button.innerHTML = __('Save changes', 'woocommerce-gateway-twint')
        this.button.disabled = false
        this.button?.classList?.remove('button-loading')
      } else {
        this.button.innerHTML = __(
          'Verifying the credentials...',
          'woocommerce-gateway-twint',
        )
        this.button.disabled = true
        this.button?.classList?.add('button-loading')
      }
    }

    hideUploadCertificateArea() {
      const $certificate = jQuery('tr.plugin_twint_settings_certificate')
      $certificate.addClass('hidden')

      const $certificatePassword = jQuery(
        'tr.plugin_twint_settings_certificate_password',
      )
      $certificatePassword.addClass('hidden')

      this.closeUploadNewCertificateButton?.classList?.add('hidden')
      this.uploadNewCertificateButton?.classList?.remove('hidden')
    }

    showUploadCertificateArea() {
      const $certificate = jQuery('tr.plugin_twint_settings_certificate')
      $certificate.removeClass('hidden')

      const $certificatePassword = jQuery(
        'tr.plugin_twint_settings_certificate_password',
      )
      $certificatePassword.removeClass('hidden')

      this.closeUploadNewCertificateButton?.classList?.remove('hidden')
      this.uploadNewCertificateButton?.classList?.add('hidden')
    }

    uploadNewCertificate() {
      this.toggleCertificateArea()

      this.uploadNewCertificateButton?.classList?.add('hidden')
      this.closeUploadNewCertificateButton?.classList?.remove('hidden')
    }

    toggleUploadNewCertBtn() {
      this.toggleCertificateArea()

      this.closeUploadNewCertificateButton?.classList?.add('hidden')
      this.uploadNewCertificateButton?.classList?.remove('hidden')
    }

    showNoticeSuccess(msg = null) {
      this.$noticeSuccess?.classList?.remove('hidden')
    }

    hideNoticeSuccess() {
      this.$noticeSuccess?.classList?.add('hidden')
    }

    appendHtml(el, msg) {
      let div = document.createElement('div') //container to append to
      div.innerHTML = msg
      el.innerHTML = ''
      el.appendChild(div.children[0])
    }

    showNoticeError(msg) {
      const html = `<p>${msg}</p>`
      this.appendHtml(this.$noticeError, html)

      this.$noticeError?.classList?.remove('hidden')
    }

    hideNoticeError() {
      this.$noticeError?.classList?.add('hidden')
    }

    resetErrorNotice() {
      jQuery('.kwt-file__drop-area').removeClass('has-error')
      this.passwordInput?.classList?.remove('has-error')
      this.storeUuidInput?.classList?.remove('has-error')
      this.hideNoticeSuccess()
      this.hideNoticeError()
    }

    // On submit the form
    onClick(evt) {
      evt.preventDefault()
      this.toggleLoadingButton()
      const formData = new FormData()

      this.resetErrorNotice()

      if (!this.checkStoreUuidField()) {
        this.toggleLoadingButton()

        return
      }

      if (!this.checkCertificateFileField()) {
        this.toggleLoadingButton()

        return
      }

      if (!this.checkCertificatePasswordField()) {
        this.toggleLoadingButton()

        return
      }

      if (
        this.state.plugin_twint_settings_certificate === null &&
        this.state.plugin_twint_settings_certificate_password === null
      ) {
        const $certificate = jQuery('tr.plugin_twint_settings_certificate')
        $certificate.addClass('hidden')
        const $certificatePassword = jQuery(
          'tr.plugin_twint_settings_certificate_password',
        )
        $certificatePassword.addClass('hidden')
      }

      formData.append('action', 'store_twint_settings')
      formData.append(
        'plugin_twint_settings_store_uuid',
        this.state.plugin_twint_settings_store_uuid,
      )
      formData.append(
        'plugin_twint_test_mode',
        this.state.plugin_twint_test_mode,
      )
      formData.append(
        'plugin_twint_settings_certificate',
        this.state.plugin_twint_settings_certificate,
      )
      formData.append(
        'plugin_twint_settings_certificate_password',
        this.state.plugin_twint_settings_certificate_password,
      )
      formData.append(
        'nonce',
        document.querySelector('input#twint_wp_nonce').value,
      )

      axios
        .post(this.options.adminUrl, formData)
        .then((response) => {
          console.log(response.data)
          const { status } = response.data
          if (status === true) {
            this.showNoticeSuccess(response.data.message)

            this.configNoticeError?.classList?.add('hidden')

            this.configNoticeError?.classList?.remove('hidden')
            if (this.state.plugin_twint_settings_certificate !== null) {
              this.configNoticeSuccess?.classList?.remove('hidden')
            }
            this.hideUploadCertificateArea()
            this.hideNoticeError()
            setTimeout(() => {
              location.reload()
            }, 1000)
          } else {
            const { flag_credentials, error_type } = response.data
            if (flag_credentials === false) {
              const { message } = response.data

              if (error_type !== null && error_type === 'upload_cert') {
                this.passwordInput?.classList?.add('has-error')
                const passwordInputErrorState = document.getElementById(
                  'error-state_plugin_twint_settings_certificate_password',
                )
                if (passwordInputErrorState) {
                  passwordInputErrorState.innerHTML = message
                  passwordInputErrorState.classList?.remove('hidden')
                }
              } else {
                this.showNoticeError(message)
                this.configNoticeError?.classList?.remove('hidden')
                if (this.state.plugin_twint_settings_certificate !== null) {
                  this.configNoticeSuccess?.classList?.remove('hidden')
                  this.showUploadCertificateArea()
                }
              }
            }
          }
          this.toggleLoadingButton()
        })
        .catch((error) => {
          console.log(error)
        })
    }

    onChangeTestMode(e) {
      console.log(e.target.checked, this.state.plugin_twint_test_mode)
      this.state.plugin_twint_test_mode = e.target.checked ? 'on' : ''
    }

    onChangeStoreUuid(e) {
      const storeUuid = e.target.value

      // Checkout Valid or not UUIDv4
      const isValidUUIDv4 = this.isValidUUIDv4(storeUuid)
      this.state.plugin_twint_settings_store_uuid = storeUuid
      if (!isValidUUIDv4) {
        e.target?.classList?.add('has-error')
        return
      }

      e.target?.classList?.remove('has-error')
    }

    onChangeCertificateFile(e) {
      const uploadedFile = e.target.files[0]
      if (uploadedFile.type !== 'application/x-pkcs12') {
        e.target.parentNode?.classList?.add('has-error')
      } else {
        e.target.parentNode?.classList?.remove('has-error')
      }

      this.state.plugin_twint_settings_certificate = uploadedFile
    }

    onCertificatePasswordChange(e) {
      if (e.target.value?.length > 0) {
        this.state.plugin_twint_settings_certificate_password = e.target.value
      } else {
        this.state.plugin_twint_settings_certificate_password = null

        // if the file is null -> then remove the has-error if it does appear.
        if (this.state.plugin_twint_settings_certificate === null) {
          jQuery('.kwt-file__drop-area').removeClass('has-error')
        }
      }
    }

    isValidUUIDv4(uuid) {
      // Regular expression to match UUID v4 format
      const uuidRegex =
        /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i

      // Check if the string matches the UUID v4 format
      return uuidRegex.test(uuid)
    }

    checkCertificatePasswordField() {
      const {
        plugin_twint_settings_certificate,
        plugin_twint_settings_certificate_password,
      } = this.state

      if (plugin_twint_settings_certificate !== null) {
        if (
          plugin_twint_settings_certificate_password?.length === undefined ||
          plugin_twint_settings_certificate_password === null
        ) {
          return false
        }
      }

      return true
    }

    checkStoreUuidField() {
      console.log(this.state.plugin_twint_settings_store_uuid)
      if (!this.isValidUUIDv4(this.state.plugin_twint_settings_store_uuid)) {
        this.storeUuidInput?.classList?.add('has-error')
        const uuidStateError = document.getElementById(
          'error-state_plugin_twint_settings_store_uuid',
        )
        if (uuidStateError) {
          uuidStateError.classList?.remove('hidden')

          this.clearValidationErrorState(uuidStateError)
        }
        return false
      }

      if (this.state.plugin_twint_settings_store_uuid === null) {
        this.storeUuidInput?.classList?.add('has-error')
        return false
      }

      return true
    }

    checkCertificateFileField() {
      const {
        plugin_twint_settings_certificate,
        plugin_twint_settings_certificate_password,
      } = this.state
      let checked = true
      const certificateIsNull = plugin_twint_settings_certificate === null
      const certificateIsIncorrectType =
        !certificateIsNull &&
        plugin_twint_settings_certificate?.type !== 'application/x-pkcs12'
      const passwordIsProvided =
        plugin_twint_settings_certificate_password?.length > 0
      const passwordIsNull = plugin_twint_settings_certificate_password === null
      const passwordIsEmpty =
        plugin_twint_settings_certificate_password === null

      const addErrorToDropArea = () =>
        jQuery('.kwt-file__drop-area').addClass('has-error')
      const addErrorToPasswordInput = () => {
        this.passwordInput?.classList?.add('has-error')
        const passwordInputErrorState = document.getElementById(
          'error-state_plugin_twint_settings_certificate_password',
        )
        if (passwordInputErrorState) {
          if (passwordIsEmpty) {
            passwordInputErrorState.innerHTML = __(
              'Certificate password is required',
              'woocommerce-gateway-twint',
            )

            this.clearValidationErrorState(passwordInputErrorState)
          }

          passwordInputErrorState.classList?.remove('hidden')
        }
      }

      if (
        plugin_twint_settings_certificate_password?.length > 0 &&
        certificateIsNull
      ) {
        addErrorToDropArea()
        checked = false
      }

      if (plugin_twint_settings_certificate !== null) {
        if (certificateIsIncorrectType) {
          addErrorToDropArea()
          checked = false
          if (passwordIsProvided) {
            // File existed, but wrong ext and password provided.
          } else if (passwordIsNull) {
            // File existed, but wrong ext and no password.
            addErrorToPasswordInput()
          }
        } else if (passwordIsEmpty) {
          // File existed, correct ext and no password.
          addErrorToPasswordInput()
          checked = false
        }
      }

      return checked
    }

    clearValidationErrorState(ele) {
      setTimeout(() => {
        ele.classList?.add('hidden')
      }, 3000)
    }

    onDeleteCertificateFile() {
      this.state.plugin_twint_settings_certificate = null
      jQuery('.kwt-file__drop-area').removeClass('has-error')
    }
  }

  new CredentialsSetting().init()
})
