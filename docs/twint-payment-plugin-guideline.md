<p align="center" style="font-size:150%"><b>TWINT Payment Plugin Guideline</b></p>

## installation

### Download the plugin

- Download the latest plugin `Releases` ZIP file (named `twint-woocommerce-extension-[RELEASE_VERSION].zip`) from the plugin's [Git repository.](https://github.com/Twint-AG/twint-woocommerce-extension/releases)

<img src="./screenshots/twint-woo-releases.png" alt="TWINT Releases for WooCommerce" width="900" height="auto">

### Install the plugin

- Go to the WooCommerce Admin panel and login.
- Go to `Plugins -> Add New`
- Click the `Upload Plugin` button -> `Choose File` -> Browse to the downloaded plugin ZIP file

<img src="./screenshots/twint-install-plugin.png" alt="Install TWINT Plugin" width="900" height="auto">

- Once the Plugin was installed -> Click the `Activate Plugin` button

<img src="./screenshots/twint-plugin-installing.png" alt="TWINT Plugin installed" width="900" height="auto">

- `Plugin activated.` notification should be displayed

<img src="./screenshots/twint-plugin-activated.png" alt="TWINT Plugin activated" width="900" height="auto">

## Configure the plugin

### Enter the Credentials

#### 1. Login to the Admin console panel

#### 2. Go to `TWINT -> Credentials`

- Enter the `Store UUID`.
- Under the `Certificate file` click `Choose file` and browse to the `*.p12` certificate file.
- Enter the `Certificate password`.
- **For test environment:** please select the `Test` option under the `Environment` dropdown.

<img src="./screenshots/twint-credentials.png" alt="TWINT Credentials" width="900" height="auto">

> 🚩 **Note:**
>
> After clicking the `Save changes` button:
>
> - Please wait for the message `Certificate encrypted and stored` to show up in the `Certificate` field.
> - And the flash message `Your certificate is successfully validated` should be displayed above the header tabs.

### Configure the payment methods

#### 1. Go to `TWINT -> TWINT Checkout`

- Ensure that the `Enable TWINT Checkout` checkbox is checked.

<img src="./screenshots/twint-checkout-setting.png" alt="TWINT Checkout setting" width="900" height="auto">

#### 2. Go to `TWINT -> TWINT Express Checkout`

- Ensure that the `Enable TWINT Express Checkout` checkbox is checked.
- Under the `Display Screens` section -> Choose the placement for displaying the `TWINT Express Checkout` button.

<img src="./screenshots/twint-express-checkout-setting.png" alt="TWINT Expesss Checkout setting" width="900" height="auto">
