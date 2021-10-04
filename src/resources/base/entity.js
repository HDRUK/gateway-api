import helper from '../utilities/helper.util';

const transform = require('transformobject').transform;

export default class Entity {

    equals (other) {
        if (other instanceof Entity === false) {
            return false;
        }
        return other.id ? this.referenceEquals(other.id) : this === other;
    }
    
    referenceEquals (id) {
        if (!this.id) {
            return this.equals(id);
        }
        const reference = id.toString();
        return this.id.toString() === reference;
    }

    toString () {
        return this.id;
    }

    transformTo(format, {strict} = {strict: false}) {
        return transform(this, format, { strict });
    }

    generateId () {
        return helper.generatedNumericId();
    }
}