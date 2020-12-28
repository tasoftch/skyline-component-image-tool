import {GeneralQuery} from "./general-query";
import {targets} from "./targets";

class ChangeQuery extends GeneralQuery {
    get target() {
        return targets.change_action_uri;
    }
}

export class ChangeNameQuery extends ChangeQuery {
    constructor({scope = null, image = null, name = null, description = null}) {
        super({scope, image});
        this.adjustFormData = (fd)=>{
            if(scope)
                fd.append("s", scope);
            if(image)
                fd.append("i", image);
            if(name)
                fd.append("n", name);
            if(description)
                fd.append("d", description);
        }
    }
}

export class ChangeOrderQuery extends ChangeQuery {
    constructor({reference, order}) {
        super({reference});

        if(order instanceof Array && order.length > 0) {
            this.adjustFormData = (fd)=>{
                fd.append("r", reference);
                fd.append("o", JSON.stringify(order));
            }
        } else
            throw new Error("No or wrong order passed. Requires an array with image ids or image slugs");
    }
}

export class ChangeMainQuery extends ChangeQuery {
    constructor({reference, main = null}) {
        super({reference});

        this.adjustFormData = (fd)=>{
            fd.append("r", reference);
            fd.append("m", main);
        }
    }
}

export class DropImageQuery extends ChangeQuery {
    constructor({reference, image = null}) {
        super({reference});

        this.adjustFormData = (fd)=>{
            fd.append("r", reference);
            fd.append("rm", image);
        }
    }
}
