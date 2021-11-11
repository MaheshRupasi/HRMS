// eslint-disable-next-line @typescript-eslint/no-var-requires
const DumpBuildTimestampPlugin = require('./scripts/plugins/DumpBuildTimestampPlugin');

module.exports = {
  css: {
    loaderOptions: {
      sass: {
        prependData: `@import "@/core/styles/_variables.scss";`,
      },
    },
    extract: true,
  },
  configureWebpack: {
    resolve: {
      alias: {
        '@ohrm/core': '@/core',
        '@ohrm/util': '@/core/util',
        '@ohrm/oxd': '@ohrm/oxd/src',
        '@ohrm/components': '@/core/components',
        assets: '@ohrm/oxd/assets',
      },
    },
    plugins: [new DumpBuildTimestampPlugin()],
  },
  chainWebpack: config => {
    config.plugins.delete('html');
    config.plugins.delete('preload');
    config.plugins.delete('prefetch');
  },
  publicPath: '.',
  filenameHashing: false,
  runtimeCompiler: true,
};
