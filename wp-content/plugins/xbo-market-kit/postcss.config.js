module.exports = (ctx) => ({
  plugins: {
    'postcss-import': {},
    'postcss-nesting': {},
    ...(ctx.env === 'production' ? { cssnano: { preset: 'default' } } : {}),
  },
});
