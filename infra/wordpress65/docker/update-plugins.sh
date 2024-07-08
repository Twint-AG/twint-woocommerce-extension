PLUGIN_FOLDER=/var/www/html/wp-content/plugins

while getopts u:t: flag
do
    case "${flag}" in
        u) 
            GITLAB_USERNAME=${OPTARG};;
        t) 
            GITLAB_TOKEN=${OPTARG};;
        \?) # Invalid option
            echo "Usage: cmd [-u] [-t]";;
    esac
done

if [ -z "$GITLAB_USERNAME" ] || [ -z "$GITLAB_TOKEN" ]; then
  echo "GitLab username and token are required."
  exit 1
fi

# Install Twint woocommerce plugin
mkdir -p $PLUGIN_FOLDER/woocommerce-gateway-twint
git clone -b $TWINT_BRANCH --single-branch https://$GITLAB_USERNAME:$GITLAB_TOKEN@git.nfq.asia/twint-ag/woo-extension.git woocommerce-gateway-twint
cp -rf woocommerce-gateway-twint/. $PLUGIN_FOLDER/woocommerce-gateway-twint
rm -r woocommerce-gateway-twint
cd $PLUGIN_FOLDER/woocommerce-gateway-twint && rm composer.lock && composer install --no-interaction --ignore-platform-req=ext-xsl
