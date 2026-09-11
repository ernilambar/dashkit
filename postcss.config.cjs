const postcssNested = require("postcss-nested").default;

module.exports = {
	plugins: [postcssNested, require("postcss-preset-env")],
};
