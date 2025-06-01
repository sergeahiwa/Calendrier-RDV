const path = require('path');
const fs = require('fs');
const CopyWebpackPlugin = require('copy-webpack-plugin');

// Récupérer toutes les langues disponibles
const localesDir = path.join(__dirname, 'assets/locales');
const locales = fs.existsSync(localesDir) 
  ? fs.readdirSync(localesDir).filter(file => 
      fs.statSync(path.join(localesDir, file)).isDirectory()
    )
  : [];

module.exports = {
  mode: 'development',
  devtool: 'source-map',
  resolve: {
    extensions: ['.ts', '.tsx', '.js', '.jsx'],
    alias: {
      '@i18n': path.resolve(__dirname, 'assets/locales/')
    }
  },
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: 'ts-loader',
        exclude: /node_modules/,
      },
      // Règle pour les fichiers de traduction JSON
      {
        type: 'javascript/auto',
        test: /\.json$/,
        include: [
          path.resolve(__dirname, 'assets/locales')
        ],
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[path][name].[ext]',
              outputPath: 'locales/',
              publicPath: '../locales/'
            }
          }
        ]
      }
    ],
  },
  plugins: [
    // Copier les fichiers de traduction dans le dossier de build
    new CopyWebpackPlugin({
      patterns: [
        {
          from: '**/*.json',
          to: 'locales/[path][name][ext]',
          context: 'assets/locales/'
        }
      ]
    })
  ],
  output: {
    filename: 'js/[name].bundle.js',
    chunkFilename: 'js/[name].[chunkhash].js',
    path: path.resolve(__dirname, 'dist'),
    publicPath: '/'
  },
  optimization: {
    splitChunks: {
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors',
          chunks: 'all',
        },
      },
    },
  },
};
