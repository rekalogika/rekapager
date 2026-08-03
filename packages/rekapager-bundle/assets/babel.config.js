module.exports = {
    presets: [
        ['@babel/preset-env', {
            "modules": false
        }]
    ],
    // Babel 8 removed preset-env's "loose" option. These are the granular
    // assumptions that "loose": true used to enable, except
    // superIsCallableConstructor, which we keep disabled.
    assumptions: {
        arrayLikeIsIterable: true,
        constantReexports: true,
        enumerableModuleMeta: true,
        ignoreFunctionLength: true,
        ignoreToPrimitiveHint: true,
        iterableIsArray: true,
        mutableTemplateObject: true,
        noClassCalls: true,
        noDocumentAll: true,
        noIncompleteNsImportDetection: true,
        objectRestNoSymbols: true,
        privateFieldsAsProperties: true,
        pureGetters: true,
        setClassMethods: true,
        setComputedProperties: true,
        setPublicClassFields: true,
        setSpreadProperties: true,
        skipForOfIteratorClosing: true,
        superIsCallableConstructor: false,
    },
};
