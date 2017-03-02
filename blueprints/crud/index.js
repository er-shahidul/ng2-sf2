var stringUtils = require('ember-cli-string-utils');
var dynamicPathParser = require('angular-cli/utilities/dynamic-path-parser');
module.exports = {
    description: '',
    anonymousOptions: [
        '<class-type>'
    ],
    normalizeEntityName: function (entityName) {
        console.log(this.project);
        console.log(entityName);
        var parsedPath = dynamicPathParser(this.project, entityName);
        this.dynamicPath = parsedPath;
        return parsedPath.name;
    },
    locals: function (options) {
        var classType = options.args[2];
        this.fileName = stringUtils.dasherize(options.entity.name);
        if (classType) {
            this.fileName += '.' + classType;
        }
        return {
            dynamicPath: this.dynamicPath.dir,
            flat: options.flat,
            fileName: this.fileName
        };
    },
    fileMapTokens: function () {
        var _this = this;
        // Return custom template variables here.
        return {
            __path__: function () {
                _this.generatePath = _this.dynamicPath.dir;
                return _this.generatePath;
            },
            __name__: function () {
                return _this.fileName;
            }
        };
    }
};
//# sourceMappingURL=index.js.map