const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

const wcDepMap = {
    '@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
    '@woocommerce/settings': ['wc', 'wcSettings']
};

const wcHandleMap = {
    '@woocommerce/blocks-registry': 'wc-blocks-registry',
    '@woocommerce/settings': 'wc-settings'
};

const requestToExternal = (request) => {
    if (wcDepMap[request]) {
        return wcDepMap[request];
    }
};

const requestToHandle = (request) => {
    if (wcHandleMap[request]) {
        return wcHandleMap[request];
    }
};

// Export configuration.
module.exports = {
    ...defaultConfig,
    entry: {
        frontend: './resources/scss/style.scss',
        admin: './resources/scss/admin/style.scss',

        'frontend/blocks': '/resources/js/frontend/index.js',
        'frontend/frontstore': '/resources/js/frontstore/CopyToken.js',
        'TwintPaymentIntegration': '/resources/js/frontstore/TwintPaymentIntegration.js',
        'DeviceSwitcher': '/resources/js/frontstore/DeviceSwitcher.js',
        'PaymentStatusRefresh': '/resources/js/frontstore/PaymentStatusRefresh.js',
        'ModalQR': '/resources/js/frontstore/ModalQR.js',
        // Admin
        CredentialSetting: '/resources/js/admin/CredentialSetting.js',
    },
    output: {
        path: path.resolve(__dirname, 'dist'),
    },
    module: {
        rules: [
            {
                test: /\.scss$/,  // Process SCSS files
                use: [
                    MiniCssExtractPlugin.loader,  // Extract CSS to a separate file
                    'css-loader',  // Resolves CSS imports
                    {
                        loader: 'postcss-loader',
                        options: {
                            postcssOptions: (loaderContext) => {
                                if (loaderContext.resourcePath.includes('admin')) {
                                    return require('./postcss.admin.js');
                                } else {
                                    return require('./postcss.frontend.js');
                                }
                            },
                        },
                    },
                    'sass-loader',  // Compiles SCSS to CSS
                ],
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',  // Transpile ES6+ code to ES5
                    options: {
                        presets: ['@babel/preset-env', '@babel/preset-react'],
                    },
                },
            },
            {
                test: /\.(png|jpg|jpeg|gif|svg)$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '[name].[ext]',
                            outputPath: 'images',
                        },
                    },
                ],
            },
        ],
    },
    plugins: [
        ...defaultConfig.plugins.filter(
            (plugin) =>
                plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
        ),
        new WooCommerceDependencyExtractionWebpackPlugin({
            requestToExternal,
            requestToHandle
        }),
        new MiniCssExtractPlugin({
            filename: '[name].css'
        }),
    ],
    optimization: {
        minimize: true,  // Enable minimization
        minimizer: [
            `...`,  // Spread existing minimizes (like Terser for JS)
            new CssMinimizerPlugin(),  // Minimize CSS
        ],
        splitChunks: {
            cacheGroups: {
                frontend: {
                    name: 'frontend',
                    test: /[\\/]resources[\\/]scss[\\/]style\.scss$/,
                    chunks: 'all',
                    enforce: true,
                },
                admin: {
                    name: 'admin',
                    test: /[\\/]resources[\\/]scss[\\/]admin[\\/]style\.scss$/,
                    chunks: 'all',
                    enforce: true,
                },
            },
        },
    },
    mode: 'production'
};
