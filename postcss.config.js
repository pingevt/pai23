function buildPluginArray(ctx) {

  const plugins = {
    'postcss-easy-import': {
      prefix: false,
      skipDuplicates: false,
      warnOnEmpty: false,
    },
    'postcss-advanced-variables': {},
    'postcss-custom-media': {},
    'postcss-preset-env': {
      features: {
        "nesting-rules": false,
      },
    },
    'postcss-calc': {},
    'postcss-nested': {},
    'postcss-utopia': {
      minWidth: 400, // Default minimum viewport
      maxWidth: 1200, // Default maximum viewport
    },
    'postcss-strip-inline-comments': {},
    'postcss-discard-comments': {},
    'postcss-combine-duplicated-selectors': {},
    'postcss-mixins': {},
  };

  if (ctx.bldrEnv === 'build') {
    plugins.cssnano = {};
    plugins.autoprefixer = {};

    plugins['postcss-pxtorem'] = {
      rootValue: 16,
      propList: [
        'gap',
        'font',
        'font-size',
        'line-height',
        'letter-spacing',
        'border*',
        'min-*',
        'max-*',
        'width',
        'padding',
        'height',
        'top',
        'bottom',
        'left',
        'right',
        'padding-*',
        'margin-*',
      ],
      replace: true,
      mediaQuery: true,
    };
  }

  return plugins
}

export default (ctx) => ({
  plugins: buildPluginArray(ctx)
})
