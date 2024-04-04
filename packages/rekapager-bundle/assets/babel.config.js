module.exports = {
    presets: [
        ['@babel/preset-env', {
            "loose": true,
            "modules": false
        }]
    ],
    assumptions: {
        superIsCallableConstructor: false,
    },
};